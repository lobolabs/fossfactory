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
// This script is supposed to be invoked within account.php.
// Make sure it isn't being invoked directly.
if( !function_exists("my_projects")) exit;

?>
<h1>Account Settings</h1>
<?
    if( $_REQUEST["err"] == 1) {
        print "<div class=error>Your changes failed due to an internal ".
            "system error.</div>\n";
    }

    list($rc,$news) = al_getwatches( $username, "news");
    if( $rc) exit;

    list($rc,$promos) = al_getwatches( $username, "promos");
    if( $rc) exit;
?>
<form method=post>
<p>
<b>Name:</b> <?=htmlentities($memberinfo["name"])?><br>
<b>Email:</b> <?=htmlentities($memberinfo["email"])?><br>
</p>
<b>Email Preferences:</b>
<p>
<input type=checkbox name=news value=1 id=news<?=sizeof($news)?' checked':''?>> <label for=news>Send me FOSS Factory news and updates</label><br>
<input type=checkbox name=promos value=1 id=promos<?=sizeof($promos)?' checked':''?>> <label for=promos>Notify me of special offers or other promotions</label>
</p>
<table border=0 cellpadding=0 cellspacing=0><tr>
<td valign=top>
<b>Preferred Charity:</b>
<p class="note" style="width:20em">When applicable, this option controls how to direct the
<a href="overview.php#communitydeduction">community deduction</a>.
See <a href="charities.php">Sponsored Charities</a>
for details about each organization.
</p>
</td>
<td valign=top><nobr>
<input type=radio name="prefcharity" id="charity0"<?=$memberinfo["prefcharity"]==0?" checked":""?> value=0><label for="charity0">None</label><br>
<?
list($rc,$charities) = ff_getcharities();
if($rc) $charities = array();

foreach( $charities as $charity) {
?>
<input type=radio name="prefcharity" id="charity<?=$charity["id"]?>"<?=$memberinfo["prefcharity"]==$charity["id"]?" checked":""?> value=<?=$charity["id"]?>><label for="charity<?=$charity["id"]?>"><?=htmlentities($charity["name"])?></label><br>
<?
}
?>
</div>
</nobr></td>
</tr></table><br>
<input type=submit name=setprefs id="setprefs" value="Apply">
</form>
