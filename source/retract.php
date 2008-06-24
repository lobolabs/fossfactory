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
if( basename($_SERVER["SCRIPT_NAME"]) == 'retract.php') exit;

list($rc,$currencies) = ff_currencies();
if($rc) {
    print "Error $rc: $currencies";
    exit;
}
$prefcurrency = $currencies[$GLOBALS["pref_currency"]];
$sponsorship = $donations[$id]["amount"];

$num_currencies = 0;
$one_currency = false;
foreach( $currencies as $code => $currency) {
    $amount = currency_value($sponsorship,$code);
    if( !ereg("[1-9]",$amount)) continue;
    $num_currencies ++;
    if( $one_currency === false || $code === $GLOBALS["pref_currency"])
        $one_currency = $currency;
}
if( $one_currency === false)
    $one_currency = $currencies[$GLOBALS["pref_currency"]];

?>

<script>
function setRetractCurrency(code) {
    var cur_prefix = [];
    var sponsorship_amt = [];
<? foreach( $currencies as $code => $currency) { ?>
    cur_prefix['<?=$code?>'] = '<?=jsencode(htmlentities($currency["prefix"]))?>';
    sponsorship_amt['<?=$code?>'] = '<?=jsencode(htmlentities(format_money(currency_value($sponsorship,$code).$code,$code)))?>';
<? } ?>
    document.getElementById('retract_currency_prefix').innerHTML = cur_prefix[code];
    document.getElementById('retract_sponsorship_amount').innerHTML = sponsorship_amt[code];
}

function checkRetractForm() {
    var n_sponsorship_amt = [];
    var amt_regex = [];
    var cur_name = [];
    var toobig_regex = [];
    var max_amt = [];
<? foreach( $currencies as $code => $currency) { ?>
    n_sponsorship_amt['<?=$code?>'] = <?=currency_value($sponsorship,$code)/$currency["multiplier"]?>;
    amt_regex['<?=$code?>'] = /^[0-9]*(\.<?=str_repeat("[0-9]",$currency["decimal_places"])?>)?$/;
    cur_name['<?=$code?>'] = '<?=jsencode($currency["name"])?>';
    toobig_regex['<?=$code?>'] = /[0-9]{<?=9-$currency["decimal_places"]?>}/;
    max_amt['<?=$code?>'] = '<?=jsencode(format_money("999999999$code"))?>';
<? } ?>
    var amt = document.getElementById('retract_sponsor_amt').value;
    var c = document.getElementById('r_currency').value;
    if( amt == '' || Number(amt) == 0 ||
        !amt.match( amt_regex[c]) || ''+Number(amt) == 'NaN') {
        alert('Please enter the retraction amount in '+cur_name[c]+'s.');
        return false;
    }
    if( Number(amt) > n_sponsorship_amt[c]) {
        alert('You have requested more funds than are available to retract.\n'+
            'Please enter a smaller amount.');
        return false;
    }
    if( amt.match( toobig_regex[c])) {
        alert('Due to system limitations, the largest amount '+
            'we can handle is '+max_amt[c]+'.');
        return false;
    }
    return true;
}
</script>

<form method=post action="project.php" onSubmit="return checkRetractForm()" id="retract-form">
    <input type=hidden name=p value=<?=$id?>>
    <input type=hidden name=tab value="<?=scrub($_REQUEST["tab"])?>">
<p>
Current sponsorship: <?=format_money($sponsorship)?>
</p>
<? if( $num_currencies > 1) { ?>
    <div>
        Currency:
        <select id=r_currency name=currency onChange="setRetractCurrency(this.value)">
<?    foreach( $currencies as $code => $currency) { ?>
            <option value="<?=$code?>"<?=$code===$one_currency["code"]?" selected":""?>><?=htmlentities($currency["name"])?></option>
<?    } ?>
        </select>
    </div>
<? } else { ?>
    <input type=hidden id=r_currency name=currency value=<?=$one_currency["code"]?>>
<? } ?>
    <div>
    Remove Amount: <span id=retract_currency_prefix><?=htmlentities($one_currency["prefix"])?></span><input id=retract_sponsor_amt name=remove_amount value="" size=7> (<span id=retract_sponsorship_amount><?=htmlentities(format_money(currency_value($sponsorship,$one_currency["code"]).$one_currency["code"],$one_currency["code"]))?></span> available to remove)
    </div>
<div class="bottom">
    <a href="" onClick="if(checkRetractForm())document.getElementById('retract-form').submit();return false" class="normal-button" style="float:right;">Submit</a>
</div>

</form>
