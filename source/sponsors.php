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
$id = scrub($id);

list($rc,$sponsors) = ff_getsponsors( $id);

if( $rc) {
?>
<p>
<b>System error.  Please try again later.</b>
</p>
<?
} else if( sizeof($sponsors) == 0) {
?>
<p>
<b>No sponsorships have been made for this project.</b>
</p>
<?
} else {
?>
<table id="sponsors_table" cellspacing=0>
<tr><th class=username>Username</th><th align=right class=sponsorship>Sponsorship</th><th align=right class=credits>Credits</th><th class=assignee>Assigned To</th></tr>
<?
$rownum = 0;
foreach ($sponsors as $uname => $sponsor) {
    $rownum++;
?>
<tr<?=($rownum&1)?" class=oddrow":""?>>
    <td class=username><a href="member.php?id=<?=urlencode($uname)?>"><?=htmlentities($uname)?></a></td>
    <td align=right class=sponsorship><?=htmlentities(format_money($sponsor["amount"]))?></td>
    <td align=right class=credits><?=$sponsor["credits"]?></td>
    <? if( $sponsor["assignee"] === $uname) { ?>
    <td class=assignee><i>self</i></td>
    <? } else { ?>
    <td class=assignee><a href="member.php?id=<?=urlencode($sponsor["assignee"])?>"><?=htmlentities($sponsor["assignee"])?></a></td>
    <? } ?>
</tr>
<?
}
?>
</table>
<?
}
if( $projinfo["parent"]) {
?>
<p>
<b>Total Bounty:</b> <?=htmlentities(format_money($projinfo["bounty"]))?><br>
<b>Inherited from Parent:</b> <?=htmlentities(format_money($projinfo["indirect_bounty"]))?>
</p>
<?
}
?>
