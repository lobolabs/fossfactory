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
$rc = ff_fixfactors();
print "Fixing credit factors: $rc[0]: $rc[1]<br>\n";

$rc = al_queuenotifications();
print "Queueing notifications: $rc[0]: $rc[1]<br>\n";

$rc = al_sendnotifications();
print "Sending notifications: $rc[0]: $rc[1]<br>\n";

$rc = ff_enforcedutydeadlines();
print "Enforcing duty deadlines: $rc[0]: $rc[1]<br>\n";

$rc = ff_checkpaymentarrival();
print "Performing scheduled payouts: $rc[0]: $rc[1]<br>\n";

$rc = ff_releaseholds();
print "Releasing holds on rejected submissions: $rc[0]: $rc[1]<br>\n";

$rc = ff_distributecommunitypot();
print "Distributing the community pot: $rc[0]: $rc[1]<br>\n";

$rc = ff_distributemonthlysponsorships();
print "Distributing monthly sponsorships: $rc[0]: $rc[1]<br>\n";

$rc = ff_deleteprojects();
print "Deleting projects: $rc[0]: $rc[1]<br>\n";

if( is_dir("$GLOBALS[DATADIR]/fake-paypal")) {
    list($rc,$currencies) = ff_currencies();

    // Schedule IPNs for payments
    if( $dh = opendir("$GLOBALS[DATADIR]/fake-paypal/subscriptions")) {
        while(($file = readdir($dh)) !== false) {
            if( substr($file,0,1) === '.') continue;
            $file = "$GLOBALS[DATADIR]/fake-paypal/subscriptions/$file";
            $lines = explode("\n",file_get_contents($file));
            $details = array();
            foreach( $lines as $line) {
                if( ereg( "^([^=]*)=([^=]*)$", $line, $parts))
                $details[urldecode($parts[1])] = urldecode($parts[2]);
            }

            // If this payment isn't due yet, then skip it.
            if( intval($details["due"]) > time()) continue;

            $currency = $currencies[$details["currency"]];

            // Schedule an IPN
            $txn_id = "fake-paypal-".scrub(microtime());
            @mkdir( "$GLOBALS[DATADIR]/fake-paypal/ipn");
            $ipnfile = tempnam("$GLOBALS[DATADIR]/fake-paypal/ipn/","ipn");
            chmod( $ipnfile, 0644);
            $IPN = array(
                "txn_type" => "subscr_payment",
                "payment_date" => date("H:i:s M d, Y T"),
                "subscr_id" => $details["subscr_id"],
                "last_name" => $details["last_name"],
                "residence_county" => "CA",
                "item_name" => $details["item_name"],
                "payment_gross" => '',
                "mc_currency" => $details["currency"],
                "business" => $details["business"],
                "payer_email" => $details["payer_email"],
                "txn_id" => $txn_id,
                "receiver_email" => $details["business"],
                "first_name" => $details["first_name"],
                "payment_status" => "Cleared",
                "mc_gross" => $details["amount"],
                "mc_fee" => format_for_entryfield( max(100,
                    round($details["amount"]*0.05*$currency["multiplier"])),
                    $currency["code"]),
                "custom" => $details["custom"],
                "charset" => "windows-1252",
                "notify_version" => 2.4,
                );

            $out = fopen($ipnfile,"w");
            foreach( $IPN as $key => $value) {
                fwrite($out,urlencode($key)."=".urlencode($value)."\n");
            }
            fclose($out);

            // Update the transaction ID and due date
            // For the sake of testing, we send the payment every 10 minutes.
            $details["due"] = intval($details["due"]) + 60*10;
            $details["txn_id"] = $txn_id;
            $out = fopen($file,"w");
            foreach( $details as $key => $value) {
                fwrite($out,urlencode($key)."=".urlencode($value)."\n");
            }
            fclose($out);
        }
    }

    $num_ipns = 0;
    // Send the fake paypal IPNs
    if( $dh = opendir("$GLOBALS[DATADIR]/fake-paypal/ipn")) {
        while(($file = readdir($dh)) !== false) {
            if( substr($file,0,1) === '.') continue;
            $file = "$GLOBALS[DATADIR]/fake-paypal/ipn/$file";
            $ipn = str_replace("\n","&",trim(file_get_contents($file)));
            $cmd = "wget -q -O - --post-data ".
                escapeshellarg($ipn)." $GLOBALS[SITE_URL]paypal-ipn.php";
            `$cmd`;
            unlink($file);
            $num_ipns ++;
        }
        closedir($dh);
    }
    print "Successfully sent $num_ipns fake-paypal IPNs<br>\n";
}
?>
