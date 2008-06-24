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
function error() {
    sql_exec( "rollback");
    exit;
}

$qu = sql_exec( "begin");
if( $qu === false) exit;

$qu = sql_exec( "select version from version for update");
if( $qu === false) error();

$row = sql_fetch_array( $qu, 0);
$version = intval($row["version"]);

$lines = file( realpath(realpath(".")."/../schema/structure"));

$exec = 0;
foreach( $lines as $line) {
    if( $exec) {
        $qu = sql_exec( trim($line));
        if( $qu === false) {
            print "Query failed: $line";
            error();
        }
    }
    else if( trim($line) == "update version set version=$version;") $exec = 1;
}

$qu = sql_exec( "select version from version");
if( $qu === false) error();
$row = sql_fetch_array( $qu, 0);
$newversion = intval($row["version"]);

$qu = sql_exec( "commit");
if( $qu === false) error();

if( $version < $newversion) {
    print "Updated from version $version to version $newversion.";
    exit;
}

if( $exec) {
    print "Already at version $version";
    exit;
}
?>
Upgrade failed.
