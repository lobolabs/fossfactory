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
$origin = "$_REQUEST[c]";
$use_dir = intval($_REQUEST["e"]);

$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

list($rc,$projectinfo) = ff_getprojectinfo( "$_REQUEST[id]");
if( $rc) {
    print ($rc == 2 ? "No such project: $_REQUEST[id]\n" : "System Error.\n");
    exit;
}

if( is_dir( "/home/git/$projectinfo[id].git")) {
    print "Repository git@$hostname:$projectinfo[id] already exists.\n";
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

// Make sure the person is the project lead
if( $projectinfo["lead"] !== $userid) {
    print "Only the project lead can create the repository.\n";
    exit;
}

if( $use_dir === 1) {
    // Create an empty git repository
    system("sudo -u git mkdir /home/git/$projectinfo[id].git");
    system("sudo -u git git --bare --git-dir=/home/git/$projectinfo[id].git init >/dev/null");
} else {
    // Handle local repos using the local protocol.
    $origin = ereg_replace( '^git@[-._a-z]+:([-_a-z0-9]+)(\.git)?$',
        '/home/git/\1.git',$origin);

    // Clone the given repo
    system("sudo -u git git clone --bare ".escapeshellarg($origin)." /home/git/$projectinfo[id].git >/dev/null", $rc);
    if( $rc != 0) {
        print "Git clone returned with error code $rc\n";
        exit;
    }
}

system("sudo -u git ln -sf ".escapeshellarg(realpath("update-hook")).
    " /home/git/$projectinfo[id].git/hooks/update");
system("echo ".escapeshellarg($projectinfo["name"]).
    " | sudo -u git tee /home/git/$projectinfo[id].git/description ".
    "> /dev/null");

print "git@$hostname:$projectinfo[id]\n";

?>
