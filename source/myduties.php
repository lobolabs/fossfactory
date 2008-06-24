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

include_once("getduties.php");

list($rc,$duties) = getduties($username);
if( $rc) $duties = array();

?>
<table border=0 width="100%">
<tr><td width="50%" valign=top>
<h1>My Duties</h1>
<p>
<? if (sizeof($duties)) { ?>
<i>This tab lists all of your current duties.  Use the [go] links to perform the corresponding tasks.</i>
<? } else { ?>
<i>You have no current duties.</i>  This tab lists all of your current duties,
either as project lead or otherwise.  It's a good idea to check
back here periodically.
<? } ?>
</p>
</td>
<td width="50%" valign=top>
<div class="help">
<b>Important:</b> If you miss a deadline, you will be automatically
removed from the position of project lead.  This leaves the
position open for anyone interested.  In many cases you will be able
to reclaim the position when you return.
<a href="faq.php#deadlines">More&nbsp;info</a>
</div>
</td>
</tr></table><br>
<?
    if (sizeof($duties)) {
?>
<script src="folder.js"></script>
<table width=100% cellpadding=3 cellspacing=0 class=duties>
    <tr>
        <th>&nbsp;</th><th align=left>Subject</th><th align=right colspan=2>Deadline</th>
    <tr>
<?
include_once("formattext.php");
        foreach ($duties as $key => $duty) {
            if (($key%2)==0) $background = "class=oddrow";
            else $background='';
    ?>
    <tr <?=$background?>>
        <td valign=top><img class=arrow id="i-<?=$duty["guid"]?>-arrow" src="arrow-right.gif" onClick="folder('i-<?=$duty["guid"]?>')"></td>
        <td valign=top><a class=folder href="javascript:folder('i-<?=$duty["guid"]?>')"><?=htmlentities($duty['subject'])?></a>&nbsp;&nbsp;<a href="<?=htmlentities($duty["link"])?>" style="text-decoration:none">[go]</a>
        <div id="i-<?=$duty["guid"]?>-div" class=folded><br><?=formatText($duty["body"])?><br><br></div></td>
        <td align=center valign=top><nobr>&nbsp;&nbsp;<tt><?=$duty["deadline"]?date('M d, H:i:s&\\n\\b\\s\\p;T',$duty['deadline']):"-"?></tt></nobr></td>
    </tr>
<?
        }
?>
</table>
<?
    } else {
?>
<h2>What kind of duties might appear here?</h2>
<p>
If you are a project lead, there are several types of duties that arise:
</p>
<ul>
<li>To accept or reject requirement change proposals.</li>
<li>To allot a portion of the bounty to newly created subprojects.</li>
<li>To review code submissions and decide whether they satisfy all project requirements.</li>
<li>To respond to complaints that are filed against you.</li>
</ul>
</p>
<p>
For all users:
</p>
<ul>
<li>If you file a complaint, this page will
show a duty whenever it's your turn to respond.</li>
</ul>
<p>
You will also be notified (usually by email) whenever a new duty appears.
</p>
<?
    }
?>
<div align="right" style="margin:0.5em;margin-right:0em">
<a href="duties.php?u=<?=$username?>"><img src='images/rss.png' border=0></a>
</div>
