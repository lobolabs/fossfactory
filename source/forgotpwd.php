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
$userid = $_REQUEST["userid"];
$err = $_REQUEST["err"];
$go = $_REQUEST["go"];

if( $go) {
    $err = "";

    list($rc,$resetcode) = ff_getresetcode( $userid);
    if( $rc == 2) $err = "No such username or email address: $userid";
    else if( $rc) $err = "System error: $rc $resetcode";

    if( !$err) {
        list($rc,$memberinfo) = ff_getmemberinfo( $userid);
        if( $rc) $err = "System error: $rc $memberinfo";
    }

    if( !$err) {
        list($rc,$e) = al_notifynow( $memberinfo["username"],
            "login.php?userid=".urlencode($userid), "resetpwd",
            array( "link" => "$GLOBALS[SECURE_URL]resetpwd.php?u=".
                urlencode($userid)."&c=".urlencode($resetcode)), "email");
        if( $rc) $err = "System error: $rc $e";
    }

    if( !$err) $err = "Success";

    header( "Location: forgotpwd.php?err=".urlencode($err).
        "&userid=".urlencode($userid));
    exit;
}

apply_template("Password Reset",array(
    array("name"=>"Password Reset", "href"=>"forgotpwd.php"),
));

if( $err === "Success") {
?>
<p>
A password reset email has been sent to user '<?=htmlentities($userid)?>'.
</p>
<?
    softexit();
}

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}
?>
<p>
Please enter your username or email address.  An email will be sent that will
help you to reset your password.
</p>
<form>
<table border=0><tr>
<td>Username or Email:</td>
<td><input name=userid value="<?=htmlentities($userid)?>"></td>
</tr><tr>
<td>&nbsp;</td>
<td><input type=submit name=go value="Send Email"></td>
</tr></table>
</form>
