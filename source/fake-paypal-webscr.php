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
// This script simulates a subset of the behaviour of PayPal's webscr script.

function webscr_parse_file( $filename) {
    $info = array();
    $lines = explode("\n",file_get_contents($filename));
    foreach( $lines as $line) {
        if( ereg( "^([^=]*)=([^=]*)$", $line, $parts))
            $info[urldecode($parts[1])] = urldecode($parts[2]);
    }
    return $info;
}

function webscr_write_array( $filename, $info) {
    $out = fopen($filename, "w");
    foreach( $info as $key => $value) {
        fwrite( $out, urlencode($key)."=".urlencode($value)."\n");
    }
    fclose($out);
}

if( $_REQUEST["cmd"] === "_notify-synch") {
    $txn_id = scrub($_REQUEST["tx"]);
    $txfile = "$GLOBALS[DATADIR]/fake-paypal/pdt/$txn_id";
    if( !file_exists($txfile)) {
        print "FAIL\n";
        exit;
    }
    print "SUCCESS\n";
    readfile($txfile);
    exit;
} else if( $_REQUEST["cmd"] === "_notify-validate") {
    print "VERIFIED\n";
    exit;
}

if( isset($_REQUEST["really_do_it"])) {
    if( $_REQUEST["cmd"] !== "_subscr-find") {
        list($rc,$currencies) = ff_currencies();
        if( !isset($currencies[$_REQUEST["currency_code"]])) {
            print "FAIL\n";
            print_r( $_REQUEST);
            exit;
        }
        $currency = $currencies[$_REQUEST["currency_code"]];
    }

    $txn_id = "fake-paypal-".scrub(microtime());

    $PDT = false;
    $IPN = false;
    $extraIPN = array();
    if( $_REQUEST["cmd"] === "_xclick-subscriptions") {
        $subscr_file = "$GLOBALS[DATADIR]/fake-paypal/subscriptions/".urlencode(ereg_replace("/.*","",$_REQUEST["custom"]));
        @mkdir("$GLOBALS[DATADIR]/fake-paypal");
        @mkdir("$GLOBALS[DATADIR]/fake-paypal/subscriptions");

        if( $_REQUEST["modify"] == 2) {
            if( !file_exists( $subscr_file)) {
                print "Modifying a non-existent subscription.";
                exit;
            }

            $details = webscr_parse_file($subscr_file);
            $details["currency"] = $_REQUEST["currency_code"];
            $details["amount"] = $_REQUEST["a3"];

            $IPN = $PDT = array(
                "txn_type" => "subscr_modify",
                "recurring" => "1",
                "mc_currency" => $_REQUEST["currency_code"],
                "custom" => $_REQUEST["custom"],
                "charset" => "windows-1252",
                "notify_version" => 2.4,
                "period3" => "1 M",
                "mc_amount3" => $_REQUEST["a3"]);
        } else {
            if( file_exists( $subscr_file)) {
                print "Subscription already exists: $subscr_file";
                exit;
            }

            $now = time();

            $details = array(
                "currency" => $_REQUEST["currency_code"],
                "amount" => $_REQUEST["a3"],
                "custom" => $_REQUEST["custom"],
                "business" => $_REQUEST["business"],
                "txn_id" => $txn_id,
                "payer_email" => "richman@fossfactory.org",
                "period3" => "1 M",
                "first_name" => "Richard",
                "last_name" => "Mann",
                "item_name" => $_REQUEST["item_name"],
                "item_number" => $_REQUEST["item_number"],
                "subscr_date" => date("H:i:s M d, Y T",$now),
                "delay" => isset($_REQUEST["delay"])?'yes':'no',
                "due" => $now);

            $PDT = array(
                "txn_type" => "subscr_payment",
                "payment_date" => date("H:i:s M d, Y T",$now),
                "subscr_id" => "S-".scrub(microtime()),
                "last_name" => "Mann",
                "residence_county" => "CA",
                "item_name" => $_REQUEST["item_name"],
                "payment_gross" => '',
                "mc_currency" => $_REQUEST["currency_code"],
                "business" => $_REQUEST["business"],
                "payer_email" => "richman@fossfactory.org",
                "txn_id" => $txn_id,
                "receiver_email" => $_REQUEST["business"],
                "first_name" => "Richard",
                "payment_status" => "Cleared",
                "mc_gross" => $_REQUEST["a3"],
                "mc_fee" => format_for_entryfield( max(100,
                    round($_REQUEST["a3"]*0.05*$currency["multiplier"])),
                    $currency["code"]),
                "custom" => $_REQUEST["custom"],
                "charset" => "windows-1252",
                "notify_version" => 2.4,
                );

            $IPN = array(
                "txn_type" => "subscr_signup",
                "subscr_id" => "S-".scrub(microtime()),
                "last_name" => "Mann",
                "residence_county" => "CA",
                "mc_currency" => $_REQUEST["currency_code"],
                "item_name" => $_REQUEST["item_name"],
                "business" => $_REQUEST["business"],
                "recurring" => "1",
                "payer_email" => "richman@fossfactory.org",
                "first_name" => "Richard",
                "receiver_email" => $_REQUEST["business"],
                "item_number" => $_REQUEST["item_number"],
                "subscr_date" => date("H:i:s M d, Y T",$now),
                "custom" => $_REQUEST["custom"],
                "charset" => "windows-1252",
                "notify_version" => 2.4,
                "period3" => "1 M",
                "mc_amount3" => $_REQUEST["a3"],
                );
        }

        // Write the subscription details
        webscr_write_array( $subscr_file, $details);
    } else if( $_REQUEST["cmd"] === "_subscr-find") {
        $subscr_file = "$GLOBALS[DATADIR]/fake-paypal/subscriptions/".
            urlencode($GLOBALS["username"]);

        if( !file_exists( $subscr_file)) {
            print "Trying to cancel a non-existent subscription.";
            exit;
        }

        $details = webscr_parse_file($subscr_file);
        unlink( $subscr_file);

        // This is for cancelling a subscription.
        $IPN = array(
            "txn_type" => "subscr_cancel",
            "last_name" => $details["last_name"],
            "residence_county" => "CA",
            "mc_currency" => "CAD",
            "item_name" => $details["item_name"],
            "business" => $_REQUEST["alias"],
            "recurring" => "1",
            "payer_email" => $details["payer_email"],
            "first_name" => $details["first_name"],
            "receiver_email" => $_REQUEST["alias"],
            "item_number" => $details["item_number"],
            "custom" => $details["custom"],
            "charset" => "windows-1252",
            "notify_version" => "2.4",
            "period3" => $details["period3"],
            "mc_amount3" => $details["amount"],
            );
    } else if( $_REQUEST["cmd"] === "_xclick") {
        $IPN = $PDT = array(
            "txn_type" => "web_accept",
            "payment_date" => date("H:i:s M d, Y T"),
            "last_name" => "Mann",
            "residence_county" => "CA",
            "item_name" => $_REQUEST["item_name"],
            "payment_gross" => '',
            "mc_currency" => $_REQUEST["currency_code"],
            "business" => $_REQUEST["business"],
            "tax" => format_for_entryfield("0",$currency["code"]),
            "payer_email" => "richman@fossfactory.org",
            "txn_id" => $txn_id,
            "quantity" => "1",
            "receiver_email" => $_REQUEST["business"],
            "first_name" => "Richard",
            "payment_status" => "Cleared",
            "shipping" => format_for_entryfield("0",$currency["code"]),
            "mc_gross" => $_REQUEST["amount"],
            "mc_fee" => format_for_entryfield( max(100,
                round($_REQUEST["amount"]*0.05*$currency["multiplier"])),
                $currency["code"]),
            "custom" => $_REQUEST["custom"],
            "charset" => "windows-1252",
            );
    }

    if( is_array( $IPN)) {
        // Save the IPN data into a file.
        @mkdir( "$GLOBALS[DATADIR]/fake-paypal");
        @mkdir( "$GLOBALS[DATADIR]/fake-paypal/ipn");
        $ipnfile = tempnam("$GLOBALS[DATADIR]/fake-paypal/ipn/","ipn");
        chmod( $ipnfile, 0644);
        webscr_write_array($ipnfile,$IPN);
    }

    if( is_array( $PDT)) {
        // Save the PDT data into a file.
        $txfile = "$GLOBALS[DATADIR]/fake-paypal/pdt/$txn_id";
        @mkdir( "$GLOBALS[DATADIR]/fake-paypal");
        @mkdir( "$GLOBALS[DATADIR]/fake-paypal/pdt");
        webscr_write_array($txfile,$PDT);

        $return = $_REQUEST["return"];
        $return .= (strpos($return,"?") === false)?"?":"&";
        $return .= "tx=".urlencode($txn_id);

        header("Location: $return");
        exit;
    }

    header("Location: ./");
    exit;
}
?>
<html>
<head>
</head>
<body>
<form>
<? if( $_REQUEST["cmd"] === "_xclick-subscriptions" &&
    $_REQUEST["modify"] == 2) { ?>
Are you sure you want to modify your subscription?
<? } else if( $_REQUEST["cmd"] === "_xclick-subscriptions") { ?>
<input type=checkbox name="delay" value="1"> Delay payment (delayed by 10 days in real life, 10 minutes in this simulation)<br />
Are you sure you want to create a subscription for <?=$_REQUEST["a3"]?>
<?=$_REQUEST["currency_code"]?> per month?
<? } else if( $_REQUEST["cmd"] === "_subscr-find") { ?>
Are you sure you want to cancel your subscription?
<? } else if( $_REQUEST["cmd"] === "_xclick") { ?>
Are you sure you want to transfer <?=$_REQUEST["amount"]?>
<?=$_REQUEST["currency_code"]?> from your fake PayPal account?
<? } ?>
<? foreach( $_REQUEST as $key => $value) { ?>
<input type=hidden name="<?=htmlentities($key)?>" value="<?=htmlentities($value)?>">
<? } ?>
<input type=submit name="really_do_it" value="Yes">
<input type=submit value="No" onClick="document.location='<?=jsencode($_REQUEST["cancel_return"])?>';return false">
</form>
</body>
</html>
