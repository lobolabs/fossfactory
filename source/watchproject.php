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
$id = scrub($_REQUEST["id"]);
$tab = scrub($_REQUEST["tab"]);
$stop = intval($_REQUEST["stop"]);

if( $GLOBALS["username"]) {
    // Get a list of the current watches
    list($rc,$watches) = al_getwatches( $GLOBALS["username"], "$id-news");
    if( !$rc) {
        if( $stop) {
            foreach( $watches as $watch) {
                al_destroywatch( $watch["watchid"]);
            }
        } else if( sizeof( $watches) == 0) {
            al_createwatch( "$id-news", $GLOBALS["username"]);
        }
    }
}

header( "Location: project.php?p=$id".($tab?"&tab=$tab":""));
?>
