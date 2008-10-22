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
// This script handles Paypal PDT requests.

if( isset( $_REQUEST["tx"])) {
    $tx = $_REQUEST["tx"];

    $cmd = "wget -q -O - --post-data ".
        escapeshellarg("cmd=_notify-synch&tx=".urlencode($tx).
        "&at=".urlencode(conf("paypal_at"))).
        " ".escapeshellarg(conf("paypal_webscr"));

    $response = `$cmd`;

    if( "$response" === "") {
        print "Error communicating with PayPal.";
        exit;
    }

    $lines = explode("\n",$response);

    if( $lines[0] === 'FAIL') {
        print "PayPal PDT returned a FAIL response. tx=".htmlentities($tx);
        exit;
    }

    $info = array();

    if( $lines[0] !== 'SUCCESS') {
        header( "Location: account.php?tab=subscription&err=paypalerr");
        exit;
    }

    // Parse the transaction details
    foreach( $lines as $line) {
        if( ereg( "^([^=]*)=([^=]*)$", $line, $parts)) {
            $info[urldecode($parts[1])] = urldecode($parts[2]);
        }
    }
} else {
    $info = array();
}

include_once("paypal-handle-info.php");

$no_transfer = 0;

// Handle the transaction (in case the IPN hasn't arrived yet)
list($rc,$err) = paypal_handle_info( $info);
if( $rc == 8) {
    // The payment succeeded, but it wasn't transferred into the requested
    // project.
    $no_transfer = 1;
} else if( $rc) {
    $dump = print_r( $info, TRUE);
    error_log(date("Y-m-d H:i:s ")."PDT Error: $rc $err;\n$dump\n",
        3, "$GLOBALS[DATADIR]/ipn-errors.log");
    print "Error $rc: $err";
    exit;
}

$dump = print_r( $info, TRUE);
error_log(date("Y-m-d H:i:s ").
    "Successful PDT: $info[txn_id] $info[txn_type] $err\n$dump\n",
    3, "$GLOBALS[DATADIR]/ipn-errors.log");

$custom = explode("/",$info["custom"]);

if( $info["txn_type"] === 'subscr_payment') {
    if( $custom[4]) {
        header( "Location: ".projurl(urlencode($custom[4])));
    } else {
        header( "Location: account.php?tab=subscription");
    }
    exit;
}

list($rc,$currencies) = ff_currencies();
if( $rc) {
    print "Error fetching currencies: $rc $currencies";
    exit;
}

$code = $info["mc_currency"];
$mult = $currencies[$code]["multiplier"];
$gross = round($info["mc_gross"]*$mult);
$fee = round($info["mc_fee"]*$mult);

if( $err !== 'Success' && $err !== 'Repeated transaction') {
    // It was a project creation
    header( "Location: createdproject.php?p=".scrub($err)."&amt=$gross$code");
    exit;
}

if( sizeof($custom) == 1) {
    // The transaction was a direct reserve deposit
    header("Location: account.php?tab=reserve&err=deposit".
        "&currency=$code&gross=$gross&fee=$fee");
    exit;
}

header("Location: ".projurl($custom[1],"pp_err=$no_transfer".
    "&currency=$code&gross=$gross&fee=$fee"));
exit;
