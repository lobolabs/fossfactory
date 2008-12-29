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
$origin = "$_REQUEST[origin]";
$comments = "$_REQUEST[comments]";

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

$files = array(
    array( "pathname" => $_FILES['patch']['tmp_name'],
        "filename" => "$projectinfo[id].patch",
        "description" => ""));

// Create the submission
list($rc,$subid) = ff_submitcode( $memberinfo["username"],
    $files, $comments, $projectinfo["id"]);
if( $rc) {
    print "Error creating the submission: $rc $error\n";
    exit;
}

if( ereg( '^git@[-._a-z]+:([-_a-z0-9]+)(\.git)?$', $origin, $regs)) {
    $origin = "/home/git/$regs[1].git";

    // Clone a similar repository, and rewind it back to the beginning.
    // We do this in order to have hard-linked copies of the objects.
    system("sudo -u git git clone --bare $origin /home/git/s$subid.git >/dev/null");
    system("sudo -u git git --git-dir=/home/git/s$subid.git push /home/git/s$subid.git :master >/dev/null");
} else {
    // Create the repository
    system("sudo -u git mkdir /home/git/s$subid.git");
    system("sudo -u git git --bare --git-dir=/home/git/s$subid.git init >/dev/null");
}
system("sudo -u git ln -sf ".escapeshellarg(realpath("update-hook")).
    " /home/git/s$subid.git/hooks/update");
system("echo ".
    escapeshellarg("Submission to $projectinfo[id] by $memberinfo[username]").
    " | sudo -u git tee /home/git/s$subid.git/description ".
    "> /dev/null");

print "git@$hostname:s$subid\n";
?>
