<? /*
Copyright 2011 John-Paul Gignac

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
function delmember_header() {
    apply_template("Bookkeeping",array(
        array("name"=>"Administration","href"=>"admin.php"),
        array("name"=>"Delete Member","href"=>"deletemember.php"),
    ));
?>
<h1>Delete Member</h1>
<?
}

if( $auth !== 'admin') {
    print "Not Authorized.";
    exit;
}

if( isset($_REQUEST["deluser"])) {
    if( isset( $_REQUEST["sure"])) {
        list($rc,$err) = admin_deletemember($_REQUEST["deluser"]);
        if( $rc) {
            delmember_header();
?>
<p>
Error: <?=$rc?> <?=htmlentities($err)?>
</p>
<?
        } else {
            delmember_header();
?>
<p>
Member <?=htmlentities($_REQUEST["deluser"])?> was deleted.
</p>
<?
        }
    } else if( isset($_REQUEST["notsure"])) {
        header("Location: admin.php");
        exit;
    } else {
        list($rc,$memberinfo) = ff_getmemberinfo($_REQUEST["deluser"]);
        if( $rc) {
            delmember_header();
?>
<p>
Error: <?=$rc?> <?=htmlentities($memberinfo)?>
</p>
<?
        } else {
            delmember_header();
?>
<form method=get action="deletemember.php">
<p>
Are you sure you want to delete this member?
</p>
<table>
<tr><td>Username:</td><td><?=htmlentities($memberinfo["username"])?></td></tr>
<tr><td>Name:</td><td><?=htmlentities($memberinfo["name"])?></td></tr>
<tr><td>Email:</td><td><?=htmlentities($memberinfo["email"])?></td></tr>
<tr><td>Sponsorships:</td><td><?=htmlentities(format_money($memberinfo["total_sponsorships"]))?></td></tr>
<tr><td>Reserve:</td><td><?=htmlentities(format_money($memberinfo["reserve"]))?></td></tr>
</table>
<p>
<input name=deluser type=hidden value="<?=htmlentities($_REQUEST["deluser"])?>">
<input name=sure type=submit value="Delete Member">
<input name=notsure type=submit value="Cancel">
</p>
</form>
<?
        }
    }
} else {
    delmember_header();
?>
<form method=get action="deletemember.php">
<p>
Please enter the username of the member that you would like to delete:
</p>
<p>
<input name=deluser>
<input type=submit>
</p>
</form>
<?
}
?>
