<? /*
Copyright 2008 John-Paul Gignac
Copyright 2008 FOSS Factory Inc.

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
<?php
// This script is supposed to be invoked within account.php.
// Make sure it isn't being invoked directly.
if( !function_exists("my_projects")) exit;

list($rc,$memberinfo) = ff_getmemberinfo($username);
if( $rc) exit();

list($rc,$currencies) = ff_currencies();
if( $rc) exit();

$reserve = $memberinfo["reserve"];

?>
<h1>My Reserve</h1>

<? if( $_REQUEST["err"] === 'syserr') { ?>
<div class=error>Your withdrawal failed due to a temporary system error.
Please try again.</div>
<? } else if( $_REQUEST["err"] === 'toomuch') { ?>
<div class=error>You do not have enough funds in your reserve.</div>
<? } else if( $_REQUEST["err"] === 'bademail') { ?>
<div class=error>You provided an invalid email address.</div>
<? } else if( $_REQUEST["err"] === 'success') { ?>
<div class=error>Your withdrawal of <?=format_money($_REQUEST["amount"])?> will be sent by PayPal email payment within one business day.  If the transaction fails, the money will be returned to your reserve within a few days.</div>
<? } else if( $_REQUEST["err"] === 'deposit') { ?>
<div class=error>
<? if( ereg("[1-9]",$_REQUEST["fee"])) { ?>
Your deposit of <?=format_money("$_REQUEST[gross]$_REQUEST[currency]")?> has been received.  Note that the PayPal transaction fee of <?=format_money("$_REQUEST[fee]$_REQUEST[currency]")?> was deducted.  <?=format_money(($_REQUEST["gross"]-$_REQUEST["fee"]).$_REQUEST["currency"])?> has been deposited into your reserve.
<? } else { ?>
<?=format_money("$_REQUEST[gross]$_REQUEST[currency]")?> has been deposited into your reserve.
<? } ?>
A receipt has been emailed to you.  You may log
into your account at <a href="http://www.paypal.com/">www.paypal.com</a>
to view details of this transaction.</div>
<? } ?>

<?
if( !ereg("[1-9]",$reserve)) {
?>
<p><em>You have no money in your reserve.</em></p>
<p>
This tab lets you manage the money in your FOSS Factory reserve.
</p>
<?
    $one_currency = $currencies[$GLOBALS["pref_currency"]];
} else {
    $num_currencies = 0;
    foreach( $currencies as $code => $currency) {
        $amount = currency_value($reserve,$code);
        if( !ereg("[1-9]",$amount)) continue;
        $num_currencies ++;
        $one_currency = $currency;
    }

    if( $num_currencies > 1) {
?>
<p>
Your reserve contains funds in <?=$num_currencies?> different currencies.
</p>
<p>
The current value in <?=htmlentities($currencies[$GLOBALS["pref_currency"]]["name"]."s")?> is approximately <?=convert_money($reserve)?>.
</p>
<?
    }
?>
<table cellspacing=0 cellpadding=5 id="currency_table">
    <tr><th align=left>Currency</th><th align=right>Amount</th></tr>
<?
    foreach( $currencies as $code => $currency) {
        $amount = currency_value($reserve,$code);
        if( !ereg("[1-9]",$amount)) continue;
?>
    <tr>
        <td align=left><?=$currency["name"]?></td>
        <td align=right><?=format_money($amount.$code,$code)?></td>
    </tr>
<?
    }
?>
</table>
<?
}
?>

<hr size="1" />
<h2>Add Funds</h2>
<p>
Money in your reserve can be used to sponsor projects.
A PayPal transaction fee may apply.
</p>
<script>
function checkDepositForm() {
    var amt = document.getElementById('deposit_amount').value;
    if( amt == '' || !amt.match( /^[0-9]*(\.<?=str_repeat("[0-9]",$currencies[$GLOBALS["pref_currency"]]["decimal_places"])?>)?$/) || amt.match(/^0+[1-9]/)) {
        alert("Please enter the deposit amount in <?=htmlentities($currencies[$GLOBALS["pref_currency"]]["name"]."s")?>.");
        return false;
    }
    return true;
}
</script>
<form action="<?=conf("paypal_webscr")?>" method="post" onSubmit="return checkDepositForm()" id="addfunds_form">
<input type=hidden name="cmd" value="_xclick">
<input type="hidden" name="business" value="<?=conf("paypal_business")?>">
<input type="hidden" name="item_name" value="Reserve Deposit">
<input type="hidden" name="currency_code" value="<?=$GLOBALS["pref_currency"]?>">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="tax" value="0">
<input type="hidden" name="bn" value="PP-SponsorshipsBF">
<input type="hidden" name="return" value="<?=htmlentities($GLOBALS["SITE_URL"])?>paypal-return.php">
<input type="hidden" name="cancel_return" value="<?=htmlentities($GLOBALS["SITE_URL"])?>account.php?tab=reserve">
<input type="hidden" name="notify_url" value="<?=htmlentities($GLOBALS["SITE_URL"])?>paypal-ipn.php">
<input type=hidden name="custom" value="<?=htmlentities($username)?>">
<div>Amount to deposit: <?=htmlentities($currencies[$GLOBALS["pref_currency"]]["prefix"])?>

<input id="deposit_amount" name="amount" value="" size=7> 
<a href="" class="normal-button" onclick="if(checkDepositForm())document.getElementById('addfunds_form').submit();return false" href="">Deposit</a>
</div>
</form>

<hr / size="1">
<h2>Withdraw Funds</h2>
<p class="withdraw_funds">
The money will be sent by PayPal email payment within one business day.
<? if( $num_currencies > 1) { ?>
To withdraw money from more than one currency, you need to withdraw each
currency one by one.
<? } ?>
</p>
<form id="withdraw_form" action="<?=$GLOBALS["SECURE_URL"]?>account.php">
<div>Email Address: <input name=email value="<?=$memberinfo["email"]?>"></div>
<? if( $num_currencies > 1) { ?>
    <script>
        cur_prefix = [];
<? foreach( $currencies as $code => $currency) { ?>
        cur_prefix['<?=$code?>'] = '<?=jsencode(htmlentities($currency["prefix"]))?>';
<? } ?>
    </script>
    <div>Currency:
    <select name=currency onChange="document.getElementById('currency_prefix').innerHTML = cur_prefix[this.value]">
<? foreach( $currencies as $code => $currency) { ?>
        <option value="<?=$code?>"<?=$code===$GLOBALS["pref_currency"]?" selected":""?>><?=htmlentities("$currency[name]")?></option>
<? } ?>
    </select></div>
    <div>Amount to withdraw: <span id=currency_prefix><?=htmlentities($currencies[$GLOBALS["pref_currency"]]["prefix"])?></span><input name=withdraw value="" size=7>
    <a href="" class="normal-button" onclick="document.getElementById('withdraw_form').submit();return false">Withdraw</a></div>
<? } else { ?>
    <input name=currency type=hidden value="<?=$one_currency["code"]?>">
    <div>Amount to withdraw: <?=htmlentities($one_currency["prefix"])?><input name=withdraw value="" size=7>
    <a href="" class="normal-button" onclick="document.getElementById('withdraw_form').submit();return false">Withdraw</a>
    </div>
<? } ?>
</form>
