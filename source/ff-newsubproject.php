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
$userid = "$_REQUEST[u]";
$password = "$_REQUEST[p]";
$name = "$_REQUEST[name]";
$reqmts = "$_REQUEST[reqmts]";

$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

list($rc,$projectinfo) = ff_getprojectinfo( "$_REQUEST[id]");
if( $rc) {
    print ($rc == 2 ? "No such project: $_REQUEST[id]\n" : "System Error.\n");
    exit;
}

// Do some basic validation
list($rc,$memberinfo) = ff_getmemberinfo( $userid);
if( $rc) {
    print ($rc == 2 ? "Login Incorrect.\n" : "System Error.\n");
    exit;
}

list($rc,$err) = ff_checkpassword($memberinfo["encpwd"], $password);
if( $rc) {
    print ($rc == 5 ? "Login Incorrect.\n" : "System Error.\n");
    exit;
}

// Create the subproject
list($rc,$id) = ff_createproject( $memberinfo["username"],
    $name, $reqmts, $projectinfo['id']);
if( $rc) {
    print "Error creating the subproject: $rc $error\n";
    exit;
}

print "$id\n";
?>
