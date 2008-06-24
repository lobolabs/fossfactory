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
if( $auth !== 'admin') {
    print "Not Authorized.";
    exit;
}

if( isset($_REQUEST["group"])) {
    admin_groupwithdrawals();
    header("Location: withdrawals.php");
    exit;
}

if( isset($_REQUEST["f"])) {
    list($rc,$withdrawals) = admin_getwithdrawals($_REQUEST["f"]);
    if( $rc) {
        header("Location: withdrawals.php");
        exit;
    }

    header("Content-type: text/plain");

    foreach( $withdrawals as $withdrawal) {
        $code = ereg_replace("[^A-Z]","",$withdrawal["amount"]);
        $amount = format_for_entryfield(
            ereg_replace("[A-Z]","",$withdrawal["amount"]), $code);
        print "$withdrawal[email]\t$amount\t$code\t$withdrawal[username]\n";
    }

    exit;
}

if( isset($_REQUEST["miny"])) {
    $min = "$_REQUEST[miny]$_REQUEST[minm]$_REQUEST[mind]";
    $max = "$_REQUEST[maxy]$_REQUEST[maxm]$_REQUEST[maxd]";
} else {
    $min = date("Ymd");
    $max = false;
}

apply_template("Withdrawals",array(
    array("name"=>"Administration","href"=>"admin.php"),
    array("name"=>"Withdrawals","href"=>"withdrawals.php"),
));
?>
<h1>Withdrawals</h1>

<p>
The following files are formatted for use with PayPal's mass payment feature.
Use the Withdrawals Log Book to physically record which payment files
have been processed.
</p>
<?

list($rc,$filenames) = admin_getwithdrawalfilenames($min,$max);

foreach( $filenames as $filename) {
?>
<div><a href="withdrawals.php?f=<?=urlencode($filename)?>"><?=htmlentities($filename)?></a></div>
<?
}
?>
<hr>
<p>
<a href="withdrawals.php?group=1">Gather New Withdrawals</a> (Only do this once per day or you will make more work for yourself.)
</p>
