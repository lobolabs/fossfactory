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
<?php
$id = scrub($_REQUEST["id"]);

list($rc,$memberinfo) = ff_getmemberinfo( $id);
if( $rc == 2) {
    print "No such member: $id";
    softexit();
}
if( $rc) {
    print "Database error";
    softexit();
}

list($rc,$projects) = ff_getleadprojects($id);
if( $rc) {
    print "Database error";
    softexit();
}

include_once("getduties.php");
list($rc,$duties) = getduties($id);
if( $rc) {
    print "Database error";
    softexit();
}

apply_template("Member: $memberinfo[name]",array(
    array("name"=>"Member: $memberinfo[name]",
        "href"=>"member.php?id=$id"),
),'',false,true);
?>
<h1>Member Information</h1>

<p>
Username: <b><?=$id?></b><br>
Name: <b><?=htmlentities($memberinfo["name"])?></b><br>
</p>
<script src="folder.js"></script>

<? if( sizeof($projects)) { ?>
<h2>Projects that <?=$id?> leads:</h2>
<table cellpadding=3 cellspacing=0 class=leadprojects>
    <tr>
        <th>&nbsp;</th><th align=left>Project Name</th><th align=right>&nbsp;&nbsp;Bounty</th>
    </tr>
<?
    include_once("formattext.php");
    $row = -1;
    foreach( $projects as $project) {
        $row++;
        if(($row%2)==0) $background = "class=oddrow";
        else $background = "";
?>
        <tr <?=$background?>>
            <td valign=top><img class=arrow id="proj-<?=$project["id"]?>-arrow" src="arrow-right.gif" onClick="folder('proj-<?=$project["id"]?>')"></td>
            <td valign=top><a class=folder href="javascript:folder('proj-<?=$project["id"]?>')"><?=htmlentities($project["name"])?></a>&nbsp;&nbsp;<a href="<?=projurl($project["id"])?>" style="text-decoration:none">[go]</a>
                <div id="proj-<?=$project["id"]?>-div" class=folded>
                    <p><?=formatText(ereg_replace("\n.*","",$project["reqmts"]))?></p>
                </div>
            </td>
            <td align=right valign=top>&nbsp;&nbsp;<nobr><?=convert_money($project["bounty"],"CAD")?></nobr></td>
        </tr>
    <? } ?>
</table><br><br>
<? } else { ?>
<p>
This member is not a project lead.
</p>
<? } ?>
<? if( sizeof($duties)) { ?>
<h2>Pending Duties:</h2>
<table width=100% cellpadding=3 cellspacing=0 class=duties>
    <tr>
        <th align=left>Subject</th><th align=right colspan=2>Deadline</th>
    <tr>
<?
        include_once("formattext.php");
        foreach ($duties as $key => $duty) {
            if (($key%2)==0) $background = "class=oddrow";
            else $background='';
    ?>
    <tr <?=$background?>>
        <td valign=top><a class=folder href="<?=projurl($duty["projectid"])?>"><?=htmlentities($duty['subject'])?></a></td>
        <td align=center valign=top><nobr>&nbsp;&nbsp;<tt><?=$duty["deadline"]?date('M d, H:i:s&\\n\\b\\s\\p;T',$duty['deadline']):"-"?></tt></nobr></td>
    </tr>
<?
        }
?>
</table><br><br>
<? } else { ?>
<p>
This member has no pending duties.
</p>
<? } ?>
