<? /*
Copyright 2008 John-Paul Gignac

This file is part of Fossfactory-src.

Fossfactory-src is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Fossfactory-src is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Fossfactory-src.  If not, see <http://www.gnu.org/licenses/>.
*/ ?>
<?
// Make sure the provided PayPal info has already been verified before
// invoking this function.
//
// If this function returns error code 1, then an internal system error
// occurred.  In the case of an IPN, we should return an HTTP error code
// to instruct PayPal to try again later.
//
// If this function returns error code 8, then the money was successfully
// recorded in the database, but it was not successfully transferred into
// the target project.
function paypal_handle_info( $info)
{
    list($rc,$currencies) = ff_currencies();
    if( $rc) return array(1,$currencies);

    // Make sure the currency is supported.
    if( !isset( $currencies[$info["mc_currency"]]))
        return array(4,"Unsupported currency: $info[mc_currency]");

    $currency = $currencies[$info["mc_currency"]];

    $custom = explode("/",$info["custom"]);
    $username = scrub("$custom[0]");
    $subscr_distribution = "$custom[3]";

    if( $username !== '') {
        list($rc,$memberinfo) = ff_getmemberinfo($username);
        if($rc) return array($rc,$memberinfo);
    }

    if( $info["txn_type"] === 'subscr_payment' ||
        $info["txn_type"] === 'subscr_signup' ||
        $info["txn_type"] === 'subscr_modify') {

        // The person is setting up a subscription.
        // Make sure that everything is as we expect.
        if( $info["txn_type"] !== 'subscr_payment' &&
            ($info["recurring"] !== '1' ||
            $info["period3"] !== '1 M' ||
            $info["period1"] || $info["period2"]))
            return array(4,"Invalid subscription settings.");

        $gross = ($info["txn_type"] === 'subscr_payment' ?
                $info["mc_gross"] : $info["mc_amount3"]);

        $amount = round($gross*$currency["multiplier"]).$currency["code"];

        if( $amount !== $memberinfo["subscription_fee"]) {
            // This is the first we've heard of this.
            // Note that we might get two messages at the same time.  So
            // we have to be careful to make sure that nothing bad happens
            // in that case.

            // It's very important that we only do this part on the
            // *very first* time that this payment amount arrives.
            // Otherwise, if the user ever rearranges his sponsorships,
            // the values will be overridden the next time a payment arrives.
            $sponsorships = false;
            if( $subscr_distribution !== '') {
                $subscr_distribution = explode("&",$subscr_distribution);
                $sponsorships = array();
                foreach( $subscr_distribution as $key_value) {
                    if( !ereg("^([^=]*)=([^=]*)$",$key_value,$parts)) continue;
                    $key = $parts[1];
                    if( $key === '') {
                        // Some other process took care of it for us.
                        $sponsorships = false;
                        break;
                    }
                    $sponsorships[$key]=$parts[2].$currency["code"];
                }
            }

            $rc = ff_setsubscription( $username, $amount,
                "monthly", $sponsorships);
            if( $rc[0]) return $rc;
        }

        if( $info["txn_type"] !== 'subscr_payment')
            return array(0,"Success");
    }

    if( $info["txn_type"] === 'subscr_cancel') {
        if( $memberinfo["subscription_amount"]) {
            return ff_cancelsubscription($username);
        }
        return array(0,"Subscription already cancelled.");
    }

    if( $info["txn_type"] === 'subscr_failed' ||
        $info["txn_type"] === 'subscr_eot') {
        // Ignore IPNs we don't know what to do with.
        return array(0,"Huh?");
    }

    // We must reject pending payments because they don't necessarily
    // include the transaction fee.
    if( $info["payment_status"] === 'Pending')
        return array(4,"Payment not complete");

    // The message is a verified transfer of funds.  Now let's make sure
    // it's a valid sponsorship.

    // Make sure the payment is directed to us
    if( $info["receiver_email"] !== conf("paypal_business"))
        return array(4, "Wrong recipient: $info[receiver_email]");

    // Make sure it's not old.  This is because old sponsorship records may be
    // moved out of the database and archived, so they can't be compared
    // against to see if the current transaction is a repeat.
    if( strtotime( $info["payment_date"]) < time() - 3*3600*24*7)
        return array(7,"IPN too old, probably a repeat: $info[payment_date]");

    $multiplier = intval("1".str_repeat("0",$currency["decimal_places"]));

    $amount = intval(round(floatval($info["mc_gross"])*$multiplier));
    if( $amount >= 2000000000)
        return array(4,"Amount too large to handle: $info[mc_gross]");

    $fee = intval(round(floatval($info["mc_fee"])*$multiplier));
    if( $fee >= $amount)
        return array(4,"Fee too big: $info[mc_fee] >= $info[mc_gross]");

    if( $amount < 0 || $fee < 0)
        return array(4,"Negative money: $info[mc_fee] $info[mc_gross]");

    // Compute the net amount after deducting the transaction fee.
    $netamount = $amount - $fee;

    $projectid = "$custom[1]";

    if( $username !== '') {
        list($rc,$err) = ff_receivefunds( $username,
            "$netamount$currency[code]", "paypal-$info[txn_id]",
            "$fee$currency[code]", $info["txn_type"]==='subscr_payment',
            "$info[first_name] $info[last_name]", $info["payer_email"],
            $info["residence_country"], $info["address_zip"]);
        if( $rc == 7) return array(0,"Repeated transaction");
        if( $rc) return array(1,$err);
    }

    $retval = "Success";

    if( $projectid !== '') {
        if( ereg("[1-9]","$netamount")) {
            // Direct the sponsorship to the specified project.
            list($rc,$err) = ff_setsponsorship(
                $projectid, $username, "$netamount$currency[code]", true);
            if( $rc) return array(8,$err);
        }
    }

    return array(0,$retval);
}
?>
