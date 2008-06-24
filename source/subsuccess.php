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
$id = scrub($_REQUEST['id']);
if ($GLOBALS['username']=='') {
    print "sorry, must login first";
    softexit();
}

// Get the project info
list($rc,$projinfo) = ff_getprojectinfo( $id);
if( $rc == 2) {
    print "No such project: $id";
    softexit();
}

apply_template($projinfo["name"],array(
    array("name"=>"Projects", "href"=>"browse.php"),
    array("name"=>$projinfo["name"], "href"=>"project.php?p=$id"),
    array("name"=>"submit code","href"=>"submission.php?id=$id")
));
?>
<table width=100% cellpadding=0 cellspacing=0>
<tr>
    <td>
Thank you for submitting the code.  The project lead will be notified immediately of your submission.
    </td>
</tr>
<tr><td align='center'><a href="project.php?p=<?=$id?>">go back to project</a></td></tr>
</table>
