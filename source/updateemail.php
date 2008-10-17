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
if( $auth !== 'admin') {
    print "Not Authorized.";
    exit;
}

list($rc,$memberinfo) = ff_getmemberinfo( $username);
if( $rc) {
    print "$rc $memberinfo";
    exit;
}

if( isset( $_REQUEST["s"])) {
    $subject = $_REQUEST["s"];
    $body = str_replace("\r","",$_REQUEST["b"]);
} else {
    $subject = "FOSS Factory Update";
    $body = "%MEMBERNAME%,\n\n\n\n\n\n\n\n\n".
        "Sincerely,\n\n$memberinfo[name]\n<****your title****>\n".
        "FOSS Factory Inc.\n\n----\n".
        "You can unsubscribe from future FOSS Factory updates by ".
        "changing your email preferences at ".
        "<http://www.fossfactory.org/account.php?tab=prefs>.";
}

$msg = "";

if( isset( $_REQUEST["test"])) {
    list($rc,$err) = al_sendnewsupdate( $username, $subject, $body, true);
    if( $rc) $msg = "Error sending test email: $rc $err";
    else $msg = "Sent test email";
} else if( isset( $_REQUEST["send"])) {
    list($rc,$err) = al_sendnewsupdate( $username, $subject, $body, false);
    if( $rc == 8) {
        $msg = "Some emails were not sent.  These weren't:<br>".
            explode("<br>",$err);
    } else if( $rc) $msg = "Error sending real emails: $rc $err";
}

list($rc,$count) = al_countwatches("news");

apply_template("Corporate News Updates",array(
    array("name"=>"Corporate News Updates","href"=>"updateemail.php"),
));
?>
<h1>Corporate News Updates</h1>

<?
if( $msg) {
    print "<div class=error>$msg</div>";
}
?>

<p>
Use this form to send update emails to all members who are subscribed
to the updates mailing list.  You can use the following macros in the
body:
</p>
<ul>
<li>%MEMBERNAME% - The recipient's full name</li>
</ul>

<form method="post">
<p>
Subject: <input name="s" value="<?=htmlentities($subject)?>">
</p>
<p>
Message:<br>
<textarea name="b" cols=80 rows=20><?=htmlentities($body)?></textarea>
</p>

<p>
<input type=submit name=test value="Send test to <?=htmlentities($memberinfo["email"])?>">
<input type=submit name=send id=send value="Send to all <?=$count?> recipients"<?=isset($_REQUEST["test"])?"":" disabled"?>>
</p>

</form>
