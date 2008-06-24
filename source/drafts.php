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

list($rc,$drafts) = ff_getprojectdrafts($username);
if( $rc) exit();

?>
<h1>Project Drafts</h1>

<? if( !sizeof($drafts)) { ?>
<p><em>You have no project drafts.</em></p>
<p>
This tab lists projects that you started to create, but saved for later.
</p>
<? } else { ?>
<p>
This tab lists projects that you started to create, but saved for later.
</p>
<table cellspacing=0 cellpadding=5 id="drafts_table">
<tr>
    <th class="name">Name</th>
    <th class="modified">Last updated</th>
    <th class="action">Actions</th>
</tr>
<?    $i=0;
      foreach( $drafts as $draftid => $draft) {
          $i++;
?>
<tr class="<?=($i&1)?"oddrow":"evenrow"?>">
    <td class="name"><a href="newproject.php?draft=<?=urlencode($draftid)?>"><?=htmlentities($draft["name"])?></a></td>
    <td class="modified"><?=date("Y-m-d @ H:i T",$draft["modified"])?></td>
    <td class="actions">
        <a href="newproject.php?draft=<?=urlencode($draftid)?>">Edit</a> /
        <a href="account.php?deldraft=<?=urlencode($draftid)?>" onClick="return confirm('Are you sure you want to delete this draft?')">Delete</a>
    </td>
<?    } ?>
</table>
<? } ?>
