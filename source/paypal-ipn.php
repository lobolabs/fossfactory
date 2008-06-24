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
// This script handles Paypal IPN requests.

// Figure out whether the HTTP method was GET or POST.
// Note that it would be dangerous to accept both methods simultaneously
// because one type might take precedence when asking PayPal to validate,
// while the other might take precedence when this script interprets the
// data.

if( sizeof($_POST) > 0) $info = $_POST;
else $info = $_GET;

$vars = "cmd=_notify-validate";
foreach( $info as $name => $value) {
    $vars .= "&".urlencode($name)."=".urlencode($value);
}

$cmd = "wget -q -O - --post-data ".
    escapeshellarg($vars)." ".
    escapeshellarg(conf("paypal_webscr"));

$response = `$cmd`;

if( "$response" === "") {
    error_log(date("Y-m-d H:i:s ").
        "Error communicating with PayPal.\n",3,
        "$GLOBALS[DATADIR]/ipn-errors.log");
    exit;
} else if( trim($response) === "INVALID") {
    error_log(date("Y-m-d H:i:s ").
        "PayPal IPN-verification returned INVALID.\n",3,
        "$GLOBALS[DATADIR]/ipn-errors.log");
    exit;
} else if( trim($response) !== "VERIFIED") {
    error_log(date("Y-m-d H:i:s ").
        "Unexpected response from PayPal: $response\n",3,
        "$GLOBALS[DATADIR]/ipn-errors.log");
    exit;
}

include_once("paypal-handle-info.php");

list($rc,$err) = paypal_handle_info( $info);
if( $rc === 1) {
    // Something went wrong.  Ask PayPal to retry later.
    $dump = print_r( $info, TRUE);
    error_log(date("Y-m-d H:i:s ")."Error: $rc $err;\n$dump\n",
        3, "$GLOBALS[DATADIR]/ipn-errors.log");
    header("HTTP/1.1 500 Internal Server Error");
    exit;
} else if( $rc) {
    // There was something invalid about the IPN.  There's no point
    // in asking PayPal to resend.  We'll just log the info for analysis.
    $dump = print_r( $info, TRUE);
    error_log(date("Y-m-d H:i:s ")."Error: $rc $err;\n$dump\n",
        3, "$GLOBALS[DATADIR]/ipn-errors.log");
} else {
    $dump = print_r( $info, TRUE);
    error_log(date("Y-m-d H:i:s ").
        "Successful IPN: $info[txn_id] $info[txn_type] $err\n$dump\n",
        3, "$GLOBALS[DATADIR]/ipn-errors.log");
}
?>
SUCCESS
