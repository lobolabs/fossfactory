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

// TESTING
//$memberinfo["subscription_amount"] = "";

list($rc,$currencies) = ff_currencies();
if( $rc) {
    print "Error $rc: $currencies";
    exit;
}
$currency = $currencies[$GLOBALS["pref_currency"]];

?>
<h1>Monthly Sponsorship Settings</h1>
<? if( $_REQUEST["err"] === 'paypalerr') { ?>
<div class=error>Due to a problem with PayPal's servers, your
monthly sponsorship information is not yet available.  Please check this
page again in a little while.</div>
<? } else if($_REQUEST["err"] === 'syserr') { ?>
<div class=error>Due to a temporary system error, your
changes may not be visible.  Please check again later.</div>
<? } ?>

<script src="folder.js"></script>

<? if(!ereg("[1-9]",$memberinfo["subscription_amount"])) { ?>

<div class="important" style="float:right">
<b>Why should I be a monthly sponsor?</b><br>
We offer big incentives for monthly sponsors!
For programmers, <i>being a monthly sponsor lets you sidestep
the <a href="overview.php#communitydeduction">community deduction</a></i>!
<a href="overview.php#funding">More&nbsp;info...</a>
</div>

<p>
<i>You are not currently a monthly sponsor.</i>
This tab lets you control your monthly sponsorship settings.
You can use this page to control the amount to sponsor each of your favourite
projects.  You can also set an amount to contribute to a randomly selected
featured project.
</p>
<? if( strpos(getenv("HTTP_USER_AGENT"),"MSIE") === false &&
        strpos(getenv("HTTP_USER_AGENT"),"Internet Explorer") === false) { ?>
<img class=arrow id="subscriptioninfo-arrow" src="arrow-right.gif"
    onClick="folder('subscriptioninfo')">&nbsp;<a 
    href="javascript:folder('subscriptioninfo')">Become a monthly sponsor...</a>
<div id="subscriptioninfo-div" class="folded">
<? } else { ?>
<h2>Become a monthly sponsor...</h2>
<div id="subscriptioninfo-div" class="unfolded">



<? } ?>
<? } ?>

<?
$myprojects = my_projects($username);

// Remove completed projects from the list
foreach( array_keys($myprojects) as $key) {
    if( $myprojects[$key]["status"] === 'complete')
        unset( $myprojects[$key]);
}

?>
<? if (!sizeof($myprojects)) { ?>
<p class="help" style="background-color:#a0ffa0">
To sponsor one or more projects on a monthly basis,
<a href="browse.php">visit each project</a>
and click the eye icon.  Then return to this page to set up your sponsorship
amounts.
</p>
<? } ?>

<style>
#subscriptionform.static .newvalue {
    display: none;
}
#subscriptionform.modify .oldvalue {
    display: none;
}
</style>
<form id="subscriptionform" action="<?=htmlentities($GLOBALS["SECURE_URL"])?>account.php" class='<?=ereg("[1-9]",$memberinfo["subscription_amount"])?'static':'modify'?>' method="post" autocomplete=off style="clear:both">
<input type=hidden name=c value="<?=$currency["code"]?>">
<input type=hidden id=subscribe name=subscribe value="">

<script>
var projects = ['reserve'<?
foreach( $myprojects as $myproject) {
    print ",'$myproject[id]'";
}
?>];

function recalculateTotal() {
    var sponsorships = '';

    var error = 0;
    var totalObj = document.getElementById('totalsubscription');
    var total = 0;
    for( var i in projects) {
        var id = projects[i];
        var val = document.getElementById('d'+id).value;
        var amt = parseFloat(val);
        if( val == '' || !val.match( /^[0-9]*(\.<?=str_repeat("[0-9]",$currency["decimal_places"])?>)?$/) || isNaN(amt)) {
            error = 1;
            break;
        }
        if( val.match( /^[1-9][0-9]{<?=9-$currency["decimal_places"]?>}/)) {
            error = 2;
            break;
        }
        total += Math.round(amt * <?=$currency["multiplier"]?>);
        if( total > 999999999) {
            error = 2;
            break;
        }
        if( id != 'reserve' && amt > 0) {
            if( sponsorships != '') sponsorships += '&';
            sponsorships += id+'='+Math.round(amt*<?=$currency["multiplier"]?>);
        }
    }

    var newHTML = '';
    if( error == 0) {
        if( total == 0)
            error = <?=ereg("[1-9]",$memberinfo["subscription_amount"])?4:3?>;
        else if( total+'<?=$currency["code"]?>' == "<?=$memberinfo["subscription_amount"]?>") error = -1;  // Don't go through PayPal
        else if( total < <?=$currency["mincontrib"]?>) error = 3;
        total=Math.floor(total/<?=$currency["multiplier"]?>)+'.'+('000000000'+total).substr((''+total).length+<?=9-$currency["decimal_places"]?>);
        newHTML='<?=jsencode($currency["prefix"])?>'+total+" per month";
        if( total != document.getElementById('a3').value) {
            document.getElementById('a3').value = total;
            document.getElementById('subscribe').value = total;
        }
        var custom = '<?=jsencode($username)?>///'+sponsorships+'/<?=jsencode($draftid)?>';
        if( custom != document.getElementById('custom').value) {
            document.getElementById('custom').value = custom;
        }
    }

    if( newHTML != totalObj.innerHTML) {
        totalObj.innerHTML = newHTML;
    }

    return error;
}

function recalcLoop() {
    recalculateTotal();
    window.setTimeout('recalcLoop()',200);
}

function doSubmit() {
    var err = recalculateTotal();
    if( err == 0) {
        document.getElementById('subscribeform').submit();
    } else if( err == -1) {
        document.getElementById('subscriptionform').submit();
    } else if( err == 1) {
        alert('One or more of the values you entered is invalid.\n'+
            'Please check your numbers and try again.');
    } else if( err == 2) {
        alert('Due to system limitations, the largest monthly amount\n'+
            'we can accept is <?=format_money("999999999$currency[code]")?>.');
    } else if( err == 3) {
        alert('Due to transaction fees, the minimum monthly amount is <?=format_money("$currency[mincontrib]$currency[code]")?>.');
    } else if( err == 4) {
        alert('Due to transaction fees, the minimum monthly amount is <?=format_money("$currency[mincontrib]$currency[code]")?>.\nTo cancel your monthly sponsorship, click on \'Cancel monthly sponsorship\', below.');
    }
    return false;
}
</script>
<br>
<table width=100% cellpadding=3 cellspacing=0 class=myprojects>
    <tr>
        <th width="0%">&nbsp;</th><th align=left width="100%">Project / Service</th><th align=right width="0%">Monthly Amount*</th>
    </tr>
<?
include_once("formattext.php");
        $row = -1;
        $totalsubscription = 0;
        $reservedeposit = converted_value($memberinfo["subscription_amount"]);
        foreach ($myprojects as $myproject) {
            $row ++;
            if (($row%2)==0) $background = "class=oddrow";
            else $background='';

            $amount = converted_value(
                $subscriptions[$myproject["id"]]["amount"]);

            $reservedeposit -= $amount;
            if( $reservedeposit < 0) $reservedeposit = 0;

            $totalsubscription += $amount;
    ?>
    <tr <?=$background?>>
        <td valign=top width="0%"><img class=arrow id="proj-<?=$myproject["id"]?>-arrow" src="arrow-right.gif" onClick="folder('proj-<?=$myproject["id"]?>')"></td>
        <td valign=top width="100%"><a class=folder href="javascript:folder('proj-<?=$myproject["id"]?>')"><?=htmlentities($myproject['name'])?></a>&nbsp;&nbsp;<a href="project.php?p=<?=$myproject["id"]?>" style="text-decoration:none">[go]</a>
        <div id="proj-<?=$myproject["id"]?>-div" class=folded>
	<p style="font-size:small">
        <b>Project Lead:</b> <?=htmlentities($myproject["lead"])?><br>
        <b>Bounty:</b> <?=convert_money($myproject["bounty"])?>
	</p>
        <?=formatText(ereg_replace("\n.*","",$myproject["reqmts"]))?><br><br></div></td>
        <td align=right valign=top width="0%"><nobr>&nbsp;&nbsp;<span class=oldvalue><?=htmlentities(convert_money($subscriptions[$myproject["id"]]["amount"]))?></span><span class=newvalue><?=htmlentities($currency["prefix"])?> <input name="amount_<?=$myproject["id"]?>" id="d<?=$myproject["id"]?>" size=5 maxLength=10 style="text-align:right;font-size:small" onChange="recalculateTotal()" value="<?=format_for_entryfield($amount)?>"></span></nobr></td>
    </tr>
<?
        }
        $totalsubscription += $reservedeposit;
?>
    <tr class='feerow'>
        <td valign=top width="0%">&nbsp;</td>
        <td valign=top width="100%">Randomly Selected Featured Project</td>
        <td align=right valign=top width="0"><nobr>&nbsp;&nbsp;<span class=oldvalue><?=htmlentities(convert_money($reservedeposit.$currency["code"]))?></span><span class=newvalue><?=htmlentities($currency["prefix"])?> <input name="reserve_deposit" id="dreserve" value="<?=format_for_entryfield($reservedeposit)?>" size=5 maxLength=10 style="text-align:right;font-size:small"></span></nobr></td>
    </tr>
    <tr class='extrarow'>
        <td valign=top width="0%">&nbsp;</td>
        <td valign=top width="100%"><b>Total Monthly Sponsorship Amount</b></td>
        <td align=right valign=top width="0%"><nobr>&nbsp;&nbsp;<b class=oldvalue><?=format_money($totalsubscription.$currency["code"])?> per month</b><b class=newvalue id="totalsubscription"><?=format_money($totalsubscription.$currency["code"])?> per month</b></nobr></td>
    </tr>
</table>
<div style="font-size:x-small;float:left">*Deposited amounts may be smaller due to PayPal transaction fees.</div>
<div align=right style="margin-top:0.2em">
<a href="" class="oldvalue normal-button" onClick="document.getElementById('subscriptionform').className='modify'; return false">Modify...</a>

<a href="" class="newvalue normal-button"  title="Make payments with PayPal - it's fast, free and secure!" onClick="return doSubmit()"><?=ereg("[1-9]",$memberinfo["subscription_amount"])?'Apply Changes':'Authorize via PayPal'?></a>


<?if(ereg("[1-9]",$memberinfo["subscription_amount"])){?><a href="" class="newvalue normal-button" onClick="document.getElementById('subscriptionform').className='static';return false">Cancel</a>

<?}?>

</div>
</form>


<? if(ereg("[1-9]",$memberinfo["subscription_amount"])) { ?>
<p>
    <? if(ereg("[1-9]",$memberinfo["last_subscr_time"])) { ?>
Your last payment occurred on <?=date("D M j, Y",$memberinfo["last_subscr_time"])?>.<br>
    <? } ?>
Your next payment is scheduled for <?=date("D M j, Y",$memberinfo["payment_due"])?>.
</p>

<a href="<?=conf("paypal_webscr")?>?cmd=_subscr-find&alias=<?=urlencode(conf("paypal_business"))?>" onClick="return confirm('Are you sure you want to cancel your monthly sponsorship?')">Cancel monthly sponsorship</a>
<? } ?>

<form id="subscribeform" action="<?=conf("paypal_webscr")?>" method="post">
<input type="hidden" name="cmd" value="_xclick-subscriptions">
<input type="hidden" id="custom" name="custom" value="">
<input type="hidden" name="business" value="<?=conf("paypal_business")?>">
<input type="hidden" name="item_name" value="FOSS Factory Monthly Sponsorship">
<input type="hidden" name="item_number" value="subscription">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="no_note" value="1">
<input type="hidden" name="currency_code" value="<?=$currency["code"]?>">
<input type="hidden" name="bn" value="PP-SubscriptionsBF">
<input type="hidden" name="p3" value="1">
<input type="hidden" id="return" name="return" value="<?=htmlentities($GLOBALS["SECURE_URL"])?>paypal-return.php">
<input type="hidden" id="a3" name="a3" value="">
<input type="hidden" name="t3" value="M">
<input type="hidden" name="src" value="1">
<input type="hidden" name="sra" value="1">
<input type="hidden" name="modify" value="<?=ereg("[1-9]",$memberinfo["subscription_amount"])?'2':'1'?>">
<input type="hidden" name="cancel_return" value="<?=htmlentities($GLOBALS["SITE_URL"])?>account.php?tab=subscription">
</form>

<? if(!ereg("[1-9]",$memberinfo["subscription_amount"])) { ?>
</div>
<? } ?>
<script>
recalcLoop();
</script>
