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
<?php
if( $username !== '') {
    header( "Location: account.php");
    exit;
}

$name = "$_REQUEST[name]";
$email = "$_REQUEST[email]";
$email2 = "$_REQUEST[email2]";
$uname = "$_REQUEST[uname]";
$pwd = "$_REQUEST[pwd]";
$pwd2 = "$_REQUEST[pwd2]";
$url = "$_REQUEST[url]";
$err = "$_REQUEST[err]";

if( isset( $_REQUEST["name"]) && !isset($_REQUEST["err"])) {
    while(1) {
        $err = '';

        // Do some basic validation
        if( $uname === '') {
            $err = "You forgot to enter a username.";
            break;
        }
        if( ereg("[^a-zA-Z0-9]",$uname)) {
            $err = "Usernames may only contain letters and numbers.";
            break;
        }
        if( strtolower($uname) === 'anonymous' ||
            strtolower($uname) === 'nobody' ||
            substr(strtolower($uname),0,7) === 'arbiter' ||
            strtolower($uname) === 'admin' ||
            strtolower($uname) === 'administrator' ||
            strtolower($uname) === 'root') {
            $err = "'$uname' is a reserved username.";
            break;
        }
        if( $pwd === '') {
            $err = "You forgot to enter a password.";
            break;
        }
        if( $pwd !== $pwd2) {
            $err = "The passwords that you entered don't match.";
            break;
        }
        if( $email === '') {
            $err = "You forgot to enter your email address.";
            break;
        }
        if( !ereg("^[-._a-zA-Z0-9]+@[-._a-zA-Z0-9]+$", $email)) {
            $err = "You entered an invalid email address.";
            break;
        }
        if( $email !== $email2) {
            $err = "The email addresses that you entered don't match.";
            break;
        }

        // Try to create the account
        list($rc,$err) = ff_createmember( $uname, $pwd, $name, $email);
        if( $rc) break;

        // Log the person in.
        include_once("loginlogic.php");
        log_in( $uname, $sid);

        if( $url !== '') {
            header( "Location: $GLOBALS[SITE_URL]$url");
            exit;
        }

        header( "Location: $GLOBALS[SITE_URL]account.php");
        exit;
    }

    header( "Location: $GLOBALS[SITE_URL]signup.php?".
        "name=".urlencode($name)."&email=".urlencode($email).
        "&email2=".urlencode($email2)."&uname=".urlencode($uname).
        "&url=".urlencode($url)."&err=".urlencode($err));
    exit;
}

apply_template("Member Sign Up",array(
    array("name"=>"Sign Up", "href"=>"signup.php"),
));

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}
?>
<form id=signup_form method="post" action="<?=$GLOBALS["SECURE_URL"]?>signup.php">
<? if( $url !== '') { ?>
<input type=hidden name=url value="<?=htmlentities($url)?>">
<? } ?>
<table border=0 cellspacing=0 cellpadding=4>
<tr class=oddrow>
<td align="right">Username:</td>
<td><input name="uname" value="<?=htmlentities($uname)?>" style="width:10em"> *</td>
<td rowspan=3 valign=top style="background-color:#ffffff;padding-top:0em"><div class=important><b>Important:</b> Choose your username carefully, as it will be how people know you on the site.  Usernames can only contain letters and numbers. (No spaces or other special characters.)</div></td>
</tr>
<tr class=evenrow>
<td align="right">Password:</td>
<td><input type=password name="pwd" value="<?=htmlentities($pwd)?>" style="width:10em"> *</td>
</tr>
<tr class=oddrow>
<td align="right">Re-type Password:</td>
<td><input type=password name="pwd2" value="<?=htmlentities($pwd2)?>" style="width:10em"> *</td>
</tr>
<tr class=evenrow>
<td align="right">Your Name:</td>
<td><input name="name" value="<?=htmlentities($name)?>" style="width:15em"></td>
</tr>
<tr class=oddrow>
<td align="right">Email:</td>
<td><input name="email" value="<?=htmlentities($email)?>" style="width:15em"> *</td>
<td rowspan=2 valign=top style="background-color:#ffffff;padding-top:0em"><div style="font-size:small;width:20em;margin-left:1em;border:thin solid black;padding:0.3em"><b>Tip:</b> If you have a PayPal account, we recommend using the same email address here as you use with PayPal.  This can make your life a little easier when you claim a bounty.</div></td>
</tr>
<tr class=evenrow>
<td align="right">Re-type Email:</td>
<td><input name="email2" value="<?=htmlentities($email2)?>" style="width:15em"> *</td>
</tr>
<tr class=oddrow>
<td>&nbsp;</td>
<td><input id=iagree type=checkbox> I have read and agree<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;to the <a href="terms.php">Terms of Use</a>.</td>
</tr>
<tr class=evenrow>
<td>&nbsp;</td>
<td><nobr><a href="" onClick="if(!document.getElementById('iagree').checked)alert('To sign up, you must agree to the terms of use.');else document.getElementById('signup_form').submit();return false" class="normal-button">Sign Up</a>
&nbsp;&nbsp;<a href="privacy.php" style="font-size:small">Privacy Policy</a></nobr></td>
</tr>
</table>
</form>
