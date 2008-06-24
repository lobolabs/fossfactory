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

list($rc,$projinfo) = ff_getprojectinfo($parentid);

//get subprojects of project 
list($rc,$subprojects) = ff_getsubprojects($parentid);
if( $rc) {
    print "Internal error: $rc $subprojects";
    exit;
}


list($rc,$subprojects) = ff_getsubprojects($parentid);
$totsubprojallot = 0;
//the total amount of allotment for the subprojects of the parent
foreach($subprojects as $subproject) $totsubprojallot +=$subproject['allotment'];


//set the allotment
$total = 0;
foreach($subprojects as $subproject) {  
    //allotment submitted by user
    $allotment = round($_REQUEST["sub".$subproject['id']]*10);
    $total += $alloment;
    if( $allotment < 0) exit;
}
if( $total > 1000) exit;


foreach($subprojects as $subproject) {  
    $allotment = round($_REQUEST["sub".$subproject['id']]*10);
    list($rc,$err) = ff_setallotment($username,$parentid,$subproject['id'],$allotment);
}

header( "Location: project.php?p=$parentid&tab=subprojects");
exit;
?>
