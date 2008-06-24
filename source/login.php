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
$userid = "$_REQUEST[userid]"?"$_REQUEST[userid]":"$_REQUEST[u]";
$password = "$_REQUEST[password]";
$remember = "$_REQUEST[remember]";
$err = "$_REQUEST[err]";

// This option indicates that a particular user is required
$requser = "$_REQUEST[requser]";

if( isset($_REQUEST["url"])) {
    $url = "$_REQUEST[url]";
} else {
    $url = '';
    if( basename($_SERVER["SCRIPT_NAME"]) !== 'login.php') {
        foreach( $_GET as $key => $value) {
            if( $key !== 'requser') {
                if( $url) $url .= "&";
                else $url = basename($_SERVER["SCRIPT_NAME"])."?";
                $url .= urlencode($key)."=".urlencode($value);
            }
        }
        if( !$url) $url = basename($_SERVER["SCRIPT_NAME"]);
    }
}

if(isset($_REQUEST['userid'])) {
    $err = '';

    while( isset( $_REQUEST["userid"])) {
        // Do some basic validation
        if( $userid === '') {
            break;
        }

        list($rc,$memberinfo) = ff_getmemberinfo( $userid);
        if( $rc == 2) {
            $err = "Incorrect username or password";
            break;
        } else if( $rc) {
            $err = "Internal system error: $rc $memberinfo";
            break;
        }

        list($rc,$err) = ff_checkpassword($memberinfo["encpwd"], $password);
        if( $rc == 5) {
            $err = "Incorrect username or password";
            break;
        } else if( $rc) {
            $err = "Internal system error: $rc $err";
            break;
        }

        include_once("loginlogic.php");
        list($rc,$err) = log_in( $memberinfo["username"], $sid, $remember);
        if( $rc) {
            print "Internal system error: $rc $err";
            exit;
        }

        if( $url === '') $url = "account.php";
        header( "Location: $GLOBALS[SITE_URL]$url");
        exit;
    }

    header( "Location: $GLOBALS[SITE_URL]login.php?url=".
        urlencode($url)."&u=".urlencode($userid).
        "&remember=".urlencode($remember)."&err=".urlencode($err));
    exit;
}

apply_template("Member Login",array(
    array("name"=>"Login", "href"=>"login.php"),
));

if( isset($msg) && !$err) $err = $msg;

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}
?>
<form class=loginform method="post" action="<?=$GLOBALS["SECURE_URL"]?>login.php" id=loginform>
<? if($url !== '') { ?>
<input type=hidden name=url value="<?=htmlentities($url)?>">
<? } ?>
<input type=hidden name=requser value="<?=htmlentities($requser)?>">
<center>
<table border=0 cellpadding=0 cellspacing=0>
<tr>
<th>Username</th>
<td colspan=2>
<? if( $requser) { ?>
    <input value="<?=htmlentities($requser)?>" disabled><input type=hidden name=userid value="<?=htmlentities($requser)?>">
<? } else { ?>
    <input name="userid" value="<?=htmlentities($userid)?>">
<? } ?>
</td></tr>
<tr>
<th>Password</th>
<td colspan=2>
<input type=password name="password" value="<?=htmlentities($password)?>">
</td></tr>
<tr><td></td>
<td style="height:2em;">
<a href="" class="normal-button" onClick="document.getElementById('loginform').submit();return false">Login</a>
</td>
<td><b>&nbsp;&nbsp;or&nbsp;&nbsp;<a href="signup.php<?=$url?"?url=".urlencode($url):""?>" class=signup>Sign Up for FOSS Factory</a></b></td></tr>
<tr><td colspan=3 align=center><br><a href="forgotpwd.php">forgot your password?</a></a></td></tr>
</table>
</center>
</form>
