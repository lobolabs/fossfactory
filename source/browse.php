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
$searchkeyword = scrub($_REQUEST['q']);
$sort = scrub($_REQUEST['sort']);
if ($_REQUEST['limit']!='') $limit = intval($_REQUEST['limit']);
if (isset($_REQUEST['offset'])) $offset = intval($_REQUEST['offset']);

$id = scrub("$_REQUEST[id]");
$amount = "$_REQUEST[amount]";


apply_template("Browse Projects",array(
    array("name"=>"Projects", "href"=>"browse.php"),
),'',array('style','header-style','footer-style','browse-style'));

include_once("formattext.php");

?>
<h1>Browse Projects</h1>
<script src="folder.js"></script>
<script>
function set_showpoor() {
    document.getElementById('browse_table').className =
        document.getElementById('showpoor').checked ? '' : 'hidepoor';
}
</script>
<style>
#browse_table.hidepoor .nobounty {
    display: none;
}
</style>
<div style="float: right;">
    <a href="rss.php?src=projects" title="New projects feed"><img src="images/rss.png" style="border: 0;" alt="RSS" /></a>
</div>
<form>
<input id=showpoor type=checkbox onClick="set_showpoor()"> <label for="showpoor">Show projects with no bounty</label>
</form>

<? list($rc,$projects) = ff_findprojects($searchkeyword,$sort,$limit,$offset);
$i = 0;
if ($rc ==2) {?>
<p>
No projects match your search criteria.
</p>
<? } else { ?>
<table id=browse_table class='hidepoor' cellspacing=0>
    <tr>
        <th>&nbsp;</th>
        <th class=project>Project&nbsp;Name</th>
        <th class=lead>Lead</th>
        <th class=bounty>Bounty</th>
    </tr>
<?    foreach ($projects as $project) { $i++; ?>
    <tr class="<?=($i&1)?'oddrow':'evenrow'?><?=ereg("[1-9]",$project["bounty"])?"":" nobounty"?>">
        <td><img class=arrow id="browse-<?=$project["id"]?>-arrow" src="arrow-right.gif" onClick="folder('browse-<?=$project["id"]?>')"></td>
        <td class=project>
            <span>
                <nobr><b onClick="folder('browse-<?=$project["id"]?>')"><?=htmlentities($project['name'])?></b> <a href="project.php?p=<?=$project["id"]?>">[go]</a></nobr>
                <div id="browse-<?=$project["id"]?>-div" class=folded>
                    <?=formatText(ereg_replace("\n.*","",$project["reqmts"]))?>
                    <p>
                    <b>Bounty:</b> <nobr><?=htmlentities(format_money($project["bounty"]))?></nobr>
                    <? if( ereg("[1-9]",$project["bounty"]) && !ereg("^[0-9]*$GLOBALS[pref_currency]$",$project["bounty"])) { ?><nobr>(Approx. <?=htmlentities(convert_money($project["bounty"]))?>)</nobr><?}?>
                    </p>
                    <a href="project.php?p=<?=$project["id"]?>">project&nbsp;page</a>
                </div>
            </span></td>
        <td class=lead><?=$project['lead']===''?"<i>&lt;none&gt;</i>":htmlentities($project['lead'])?></td>
        <td class=bounty><nobr><?=htmlentities(convert_money($project["bounty"]))?></nobr></td>
    </tr>
<?     } ?>
</table>
<script>
set_showpoor();
</script>
<? } ?>
