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
// Never call this script directly.
// It is intended to be included from project.php
if( basename($_SERVER["SCRIPT_NAME"]) == 'sponsor.php') exit;

list($rc,$currencies) = ff_currencies();
if($rc) {
    print "Error $rc: $currencies";
    exit;
}
$prefcurrency = $currencies[$GLOBALS["pref_currency"]];
$reserve = $memberinfo["reserve"];

$num_currencies = 0;
$one_currency = false;
foreach( $currencies as $code => $currency) {
    $amount = currency_value($reserve,$code);
    if( !ereg("[1-9]",$amount)) continue;
    $num_currencies ++;
    if( $one_currency === false || $code === $GLOBALS["pref_currency"])
        $one_currency = $currency;
}
if( $one_currency === false)
    $one_currency = $currencies[$GLOBALS["pref_currency"]];

?>
<!--
<p>
Once you've sponsored this project, your money will be placed as a
bounty on this project and on any subprojects.  Your sponsorship can
be withdrawn at any time, except for portions for which solutions
have been submitted.
</p>
-->

<script>

function setSponsorshipCurrency(code) {
    var cur_prefix = [];
    var reserve_amt = [];
<? foreach( $currencies as $code => $currency) { ?>
    cur_prefix['<?=$code?>'] = '<?=jsencode(htmlentities($currency["prefix"]))?>';
    reserve_amt['<?=$code?>'] = '<?=jsencode(htmlentities(format_money(currency_value($reserve,$code).$code,$code)))?>';
<? } ?>
    document.getElementById('currency_prefix').innerHTML = cur_prefix[code];
    document.getElementById('reserve_amount').innerHTML = reserve_amt[code];
}

function checkReserveSponsorshipForm() {
    var n_reserve_amt = [];
    var amt_regex = [];
    var cur_name = [];
    var toobig_regex = [];
    var max_amt = [];
<? foreach( $currencies as $code => $currency) { ?>
    n_reserve_amt['<?=$code?>'] = <?=currency_value($reserve,$code)/$currency["multiplier"]?>;
    amt_regex['<?=$code?>'] = /^[0-9]*(\.<?=str_repeat("[0-9]",$currency["decimal_places"])?>)?$/;
    cur_name['<?=$code?>'] = '<?=jsencode($currency["name"])?>';
    toobig_regex['<?=$code?>'] = /[0-9]{<?=9-$currency["decimal_places"]?>}/;
    max_amt['<?=$code?>'] = '<?=jsencode(format_money("999999999$code"))?>';
<? } ?>
    var amt = document.getElementById('sponsor_amt').value;
    var c = document.getElementById('s_currency').value;
    if( amt == '' || Number(amt) == 0 ||
        !amt.match( amt_regex[c]) || ''+Number(amt) == 'NaN') {
        alert('Please enter the sponsorship amount in '+cur_name[c]+'s.');
        return false;
    }
    if( Number(amt) > n_reserve_amt[c]) {
        alert('You don\'t have enough funds in your reserve.\n'+
            'Please enter a smaller amount, or sponsor using PayPal.');
        return false;
    }
    if( amt.match( toobig_regex[c])) {
        alert('Due to system limitations, the largest sponsorship '+
            'we accept is '+max_amt[c]+'.');
        return false;
    }
    return true;
}
</script>

<form method=post action="<?=$GLOBALS["SECURE_URL"].projurl($id)?>" onSubmit="return checkReserveSponsorshipForm()" id="reserve-form">
<h2 class="title">Sponsor from Your Reserve</h2>
    <input type=hidden name=tab value="<?=scrub($_REQUEST["tab"])?>">
<? if( $num_currencies > 1) { ?>
    <div class="reserve-sponsorship">
        Currency:
        <select id=s_currency name=currency onChange="setSponsorshipCurrency(this.value)">
<?    foreach( $currencies as $code => $currency) { ?>
            <option value="<?=$code?>"<?=$code===$one_currency["code"]?" selected":""?>><?=htmlentities($currency["name"])?></option>
<?    } ?>
        </select>
    </div>
<? } else { ?>
    <input type=hidden id=s_currency name=currency value="<?=$one_currency["code"]?>">
<? } ?>
    <div class="reserve-sponsorship">
    Sponsorship Amount: <span id=currency_prefix><?=htmlentities($one_currency["prefix"])?></span><input id=sponsor_amt name=sponsor_amount value="" size=7> (<span id=reserve_amount><?=htmlentities(format_money(currency_value($reserve,$one_currency["code"]).$one_currency["code"],$one_currency["code"]))?></span> in your reserve, <a href="account.php?tab=reserve">Add funds</a>)
    </div>
    <div class="bottom">
    <a href="" onClick="if(checkReserveSponsorshipForm())document.getElementById('reserve-form').submit();return false" class="normal-button" style="float:right;">Sponsor from Reserve</a>
    </div>
</form>

<script>
function checkPayPalSponsorshipForm() {
    var amt = document.getElementById('paypal_amt').value;
    if( amt == '' || !amt.match( /^[0-9]*(\.<?=str_repeat("[0-9]",$prefcurrency["decimal_places"])?>)?$/) ||
        ''+Number(amt) == 'NaN') {
        alert('Please enter the sponsorship amount in '+
            '<?=jsencode($prefcurrency["name"])?>s.');
        return false;
    }

    if( amt.match( /[0-9]{<?=9-$prefcurrency["decimal_places"]?>}/)) {
        alert('Due to system limitations, the largest sponsorship '+
            'we accept is <?=format_money("999999999$prefcurrency[code]")?>.');
        return false;
    }

    if( Number(amt) * <?=$prefcurrency["multiplier"]?> < <?=$prefcurrency["mincontrib"]?>) {
        alert('Due to transaction fees, the minimum sponsorship is <?=format_money("$prefcurrency[mincontrib]$prefcurrency[code]")?>.');
        return false;
    }

    return true;
}
</script>






<form action="<?=conf("paypal_webscr")?>" method="post" onSubmit="return checkPayPalSponsorshipForm()" id="paypal-form">
<h2 class="title">..or sponsor using PayPal</h2>
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="business" value="<?=conf("paypal_business")?>">
    <input type="hidden" name="item_name" value="Sponsorship for &quot;<?=htmlentities($projinfo["name"])?>&quot;">
    <input type="hidden" name="currency_code" value="<?=$GLOBALS["pref_currency"]?>">
    <input type="hidden" name="no_note" value="1">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="tax" value="0">
    <input type="hidden" name="bn" value="PP-SponsorshipsBF">
    <input type="hidden" name="return" value="<?=htmlentities($GLOBALS["SITE_URL"])?>paypal-return.php">
    <input type="hidden" name="cancel_return" value="<?=htmlentities($GLOBALS["SITE_URL"]).projurl($id,"tab=".scrub($_REQUEST["tab"]))?>">
    <input type="hidden" name="notify_url" value="<?=htmlentities($GLOBALS["SITE_URL"])?>paypal-ipn.php">
    <input type="hidden" name="custom" value="<?=htmlentities($username)?>/<?=$id?>">
    <div>
    Sponsorship Amount: <?=htmlentities($currencies[$GLOBALS["pref_currency"]]["prefix"])?><input id=paypal_amt name=amount value="" size=7> (<?=format_money($currencies[$GLOBALS["pref_currency"]]["mincontrib"].$GLOBALS["pref_currency"])?> minimum, <a href="faq.php#paypalfees">paypal charges</a> may apply)
    </div>
    <!--<div class="smallprint">A PayPal transaction fee may be deducted from your contribution.  For this reason, we enforce a minimum contribution of <?=format_money($currencies[$GLOBALS["pref_currency"]]["mincontrib"].$GLOBALS["pref_currency"])?>.  To avoid transaction fees on multiple sponsorships, consider <a href="account.php?tab=reserve">depositing into your reserve</a>, then sponsoring projects from your reserve.</div>-->
    <div class="bottom">
    <a href="" onClick="if(checkPayPalSponsorshipForm())document.getElementById('paypal-form').submit();return false" title="Make payments with PayPal - it's fast, free and secure!" class="normal-button" style="float:right;">Sponsor using PayPal</a>
    </div>
</form>
