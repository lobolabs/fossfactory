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

list($rc,$currencies) = ff_currencies();
if( $rc) exit;

$myprojects = my_projects($username);

?>
<table border=0><tr>
<td width="100%" style="padding-bottom:0.5em">
<h1>My Projects</h1>
<?
    if( $_REQUEST["err"] == 1) {
        print "<div class=error>Your changes failed due to an internal ".
            "system error.</div>\n";
    } else if( $_REQUEST["err"] == 2) {
        print "<div class=error>You entered a non-existent username: '".
            htmlentities($_REQUEST["b"])."'.</div>\n";
    } else if( $_REQUEST["err"]=="success")  {
        print "<div class=results>Your credits were assigned".
            htmlentities($_REQUEST["b"])."</div>\n";
    } else {
?>
<p><i>This tab lists projects that you have sponsored, or are watching, or are the lead for.  To add a project to this list, <a href="browse.php">navigate</a> to that project and click on the eye icon.</i></p>
<?
    }
?></td>
<td valign=bottom><nobr>Total Sponsorships: <b><?=format_money($memberinfo["current_sponsorships"])?></b></nobr></td>
</tr></table>
<?
    if (sizeof($myprojects)) {
?>
<script src="folder.js"></script>
<script>
function money(val) {
    var prefix = '$';
    if( val < 0) {
        val = -val;
        prefix = "$-";
    }
    var result = "."+("00"+(val%100)).substr(-2);
    val = Math.floor(val/100);
    while( val > 1000) {
        result = ","+("000"+(val%1000)).substr(-3)+result;
        val = Math.floor(val/1000);
    }
    return prefix+val+result;
}

</script>
<script>
function handleassignment(obj,orig) {
    if( obj.value != '') return true;
    var name = prompt('Please enter a username to assign your sponsorship to.');
    if( typeof(name) != 'string' || name.match( /^ *$/)) {
        obj.value = orig;
        return true;
    }
    var option = document.createElement('option');
    option.value = name;
    option.innerHTML = name;
    obj.insertBefore(option,obj.firstChild);
    obj.value = name;
    return true;
}
</script>
<form method="post" autocomplete=off id="myprojects">
<table width=100% cellpadding=3 cellspacing=0 class=myprojects>
    <tr>
        <th>&nbsp;</th><th align=left>Name</th><th align=right>&nbsp;&nbsp;My&nbsp;Sponsorship</th><th align=right>&nbsp;&nbsp;Project&nbsp;Credits</th><th align=right colspan=2>&nbsp;&nbsp;Credits&nbsp;Assigned&nbsp;To</th>
    </tr>
    <tr>
<?
include_once("formattext.php");
        $row = -1;
        foreach ($myprojects as $myproject) {
            $row ++;
            if (($row%2)==0) $background = "class=oddrow";
            else $background='';
    ?>
    <tr <?=$background?>>
        <td valign="top"><img class=arrow id="proj-<?=$myproject["id"]?>-arrow" src="arrow-right.gif" onClick="folder('proj-<?=$myproject["id"]?>')"></td>
        <td><a class=folder href="javascript:folder('proj-<?=$myproject["id"]?>')"><?=htmlentities($myproject['name'])?></a>&nbsp;&nbsp;<a href="project.php?p=<?=$myproject["id"]?>" style="text-decoration:none">[go]</a>
        <div id="proj-<?=$myproject["id"]?>-div" class=folded>
	<p style="font-size:small">
        <b>Project Lead:</b> <?=htmlentities($myproject["lead"])?><br>
        <b>Bounty:</b> <?=convert_money($myproject["bounty"])?>
	</p>
        <?=formatText(ereg_replace("\n.*","",$myproject["reqmts"]))?><br><br></div></td>
        <td align=right valign=top><nobr>&nbsp;&nbsp;<?=format_money($memberdonations[$myproject["id"]]["amount"])?></nobr></td>

        <td align=right valign=top>&nbsp;&nbsp;<?=intval($memberdonations[$myproject["id"]]["credits"])?></td>

        <td align=right valign=top><nobr>&nbsp;<span id="assignspan<?=$myproject["id"]?>"><select id="assign<?=$myproject["id"]?>" name="a_<?=$myproject["id"]?>" style="width:7em;font-size:small" onChange="return handleassignment(this,'<?=$memberdonations[$myproject["id"]]["assignee"]?>')"<?=$memberdonations[$myproject["id"]]["credits"]?"":" disabled"?>>
            <option value="<?=htmlentities($username)?>"<?=(!isset($memberdonations[$myproject["id"]]) || $memberdonations[$myproject["id"]]["assignee"]===$username)?" selected":""?>><?=htmlentities($username)?></option>
<? if( $myproject["lead"] && $myproject["lead"] !== $username) { ?>
            <option value="<?=htmlentities($myproject["lead"])?>"<?=$memberdonations[$myproject["id"]]["assignee"]==$myproject["lead"]?" selected":""?>><?=htmlentities($myproject["lead"])?></option>
<? } ?>
<?
            list($rc,$candidates) = ff_leadcandidates($myproject["id"]);
            if( $rc) $candidates = array();
            sort($candidates);
            foreach( $candidates as $candidate) {
                if( $candidate === $myproject["lead"] ||
                    $candidate === $username) continue;
?>
            <option value="<?=htmlentities($candidate)?>"<?=$memberdonations[$myproject["id"]]["assignee"]==$candidate?" selected":""?>><?=htmlentities($candidate)?></option>
<?
            }
?>
            <option value="">Other...</option>
        </select></span></nobr></td>
    </tr>
<?
        }
?>
</table>
<div align=right>
<input type="hidden" name="assigncredits" value='Update Assignments'>
<a href="" onclick="document.getElementById('myprojects').submit();return false" class="normal-button">Update Assignments</a>

</form>
<br>
<?
    }
?>
