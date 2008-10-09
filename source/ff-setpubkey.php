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

$err = '';

// Do some basic validation
list($rc,$memberinfo) = ff_getmemberinfo( $userid);
if( $rc) {
    print ($rc == 2 ? "unauthorized" : "syserr");
    exit;
}

list($rc,$err) = ff_checkpassword($memberinfo["encpwd"], $password);
if( $rc) {
    print ($rc == 5 ? "unauthorized" : "syserr");
    exit;
}

// Format the public key properly
$key = ereg_replace("^.*(ssh-dss|ssh-rsa) +([+/a-zA-Z0-9]+=?=?).*$",
    "\\1 \\2",$_REQUEST["k"]);
if( !ereg("^ssh-(dss|rsa) [+/a-zA-Z0-9]+=?=?$", $key)) {
    print "invalidkey";
    exit;
}

$keyfile = "/home/git/.ssh/authorized_keys";

// Add the public key to the git user's authorized_keys file
if( !is_dir(dirname($keyfile))) {
    system("sudo -u git mkdir -p '".dirname($keyfile)."'");
}
system("grep -q ' $key$' '$keyfile' || echo 'environment=\"FFMEMBER=$userid\" $key' | sudo -u git tee -a '$keyfile' >/dev/null");

?>
