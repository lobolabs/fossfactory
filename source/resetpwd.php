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
$u = $_REQUEST["u"];
$c = $_REQUEST["c"];
$go = $_REQUEST["go"];
$pwd = $_REQUEST["pwd"];
$pwd2 = $_REQUEST["pwd2"];
$err = $_REQUEST["err"];

if( $err !== "Success") {
    list($rc,$e) = ff_checkresetcode( $u, $c);
    if( $rc) {
        $err = "Invalid reset code.  The code may have expired.";
        header( "Location: $GLOBALS[SITE_URL]forgotpwd.php?userid=".
            urlencode($u)."&err=".urlencode($err));
        exit;
    }
}

if( $go) {
    $err = '';
    if( $pwd !== $pwd2) {
        $err = "The passwords you entered don't match.";
    } else if( !$pwd) {
        $err = "You forgot to enter a new password.";
    } else {
        // Reset the password
        list($rc,$e) = ff_setmemberinfo( $u, false, false, $pwd);
        if( $rc) $err = "$rc $e";
    }

    if( !$err) {
        $err = "Success";

        // Log the person in
        include_once("loginlogic.php");
        list($rc,$err) = log_in( $u, $sid);
        if( !$rc) {
            header( "Location: $GLOBALS[SITE_URL]resetpwd.php?u=".
                urlencode($u)."&err=Success");
            exit;
        }
    }

    header( "Location: $GLOBALS[SECURE_URL]resetpwd.php?u=".urlencode($u).
        "&c=".urlencode($c)."&err=".urlencode($err));
    exit;
}

apply_template("Password Reset",array(
    array("name"=>"Password Reset",
        "href"=>"$GLOBALS[SECURE_URL]resetpwd.php?u=".
        urlencode($u)."&c=".urlencode($c)),
));

if( $err === "Success") {
?>
<p>
Your password has been successfully reset.
</p>
<a href="account.php">Continue</a>
<?
    softexit();
}

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}
?>
<p>
Please enter your new password.  Make sure to type it exactly the same
in both entry fields.
</p>
<form method=post action="<?=$GLOBALS["SECURE_URL"]?>resetpwd.php">
<table border=0><tr>
<td align=right>New Password:</td>
<td><input type=password name=pwd value="<?=htmlentities($pwd)?>"></td>
</tr><tr>
<td align=right>Re-enter New Password:</td>
<td><input type=password name=pwd2 value="<?=htmlentities($pwd2)?>"></td>
</tr><tr>
<td>&nbsp;</td>
<td><input type=submit name=go value="Set Password"></td>
</tr></table>
<input type=hidden name=u value="<?=htmlentities($u)?>">
<input type=hidden name=c value="<?=htmlentities($c)?>">
</form>
