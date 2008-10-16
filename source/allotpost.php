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
$parentid = scrub($_REQUEST["id"]);

//get subprojects of project
list($rc,$subprojects) = ff_getsubprojects($parentid);
if( $rc) {
    print "Internal error: $rc $subprojects";
    exit;
}

foreach($subprojects as $subproject) {
    $allotment = round($_REQUEST["sub$subproject[id]"]*10);
    if( isset( $_REQUEST["sub$subproject[id]"]) &&
        $allotment >= 0 && $allotment <= 1000 &&
        (!$subproject["allotted"] || $allotment != $subproject["allotment"]))
        ff_setallotment( $username, $parentid, $subproject['id'], $allotment);

    $priority = scrub($_REQUEST["pri$subproject[id]"]);
    if( $priority !== $subproject["priority"])
        ff_setpriority( $username, $parentid, $subproject['id'], $priority);
}

header( "Location: project.php?p=$parentid&tab=subprojects");
exit;
?>
