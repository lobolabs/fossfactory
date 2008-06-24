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
function cmp_projects($a,$b) {
    if( strtolower($a["name"]) < strtolower($b["name"])) return -1;
    if( strtolower($a["name"]) > strtolower($b["name"])) return 1;
    if( $a["id"] < $b["id"]) return -1;
    if( $a["id"] > $b["id"]) return 1;
    return 0;
}

function my_projects($username) {
    list($rc,$cooprojects) = ff_getleadprojects($username);
    if( $rc) $cooprojects = array();

    $myprojectids = array();

    // This allows an extra project to be specified in the URL
    if( $_REQUEST["p"]) $myprojectids[] = $_REQUEST["p"];

    // Get the list of the projects which this user is watching.
    list($rc,$watches) = al_getwatches($username);
    if( !$rc) {
        foreach( $watches as $watch) {
            if( ereg("^(p[0-9]+)-news$",$watch["eventid"],$pieces)) {
                $myprojectids[] = $pieces[1];
            }
        }
    }

    // Get the list of the projects which this user has sponsored.
    global $memberdonations;
    list($rc,$memberdonations) = ff_memberdonations($username,false);
    if( $rc) $memberdonations = array();
    $myprojectids = array_merge( $myprojectids, array_keys($memberdonations));

    // Get the list of projects which this user is subscribed to
    global $subscriptions;
    list($rc,$subscriptions) = ff_showsubscriptions($username);
    if( $rc) $subscriptions = array();
    $myprojectids = array_merge( $myprojectids, array_keys($subscriptions));

    list($rc,$myprojects) = ff_getprojectinfo($myprojectids);
    if( $rc) $myprojects = array();

    $myprojects =  array_merge( $cooprojects, $myprojects);

    uasort( $myprojects, cmp_projects);

    return $myprojects;
}
?>
