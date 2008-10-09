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
$p = scrub( $_REQUEST["p"]);
$reqmts = "$_REQUEST[reqmts]";
$priority = scrub( $_REQUEST["priority"]);
$allotment = floatval($_REQUEST["allotment"]);

if( !$p) exit;

if( trim($reqmts)) {
    if( $_REQUEST["stopspam"] !== 'yes') exit;
    $tempdir = "$GLOBALS[DATADIR]/tempattachments/$sid";
    $attachments = array();
    foreach( $_REQUEST as $key => $filename) {
        if( !ereg("^attachment_filename_([a-zA-Z0-9]+)$", $key, $parts))
            continue;
        $basename = $parts[1];
        $attachments[] = array(
            'filename' => $filename,
            'pathname' => "$tempdir/$basename",
            'description' => ''
        );
    }

    $reqmts = trim($reqmts);

    list($rc,$id) = ff_createproject( $username,
        '', $reqmts, $p, $attachments, false, $priority, '',
        isset($_REQUEST["allotment"]) ? round($allotment * 10) : false);
    if( !$rc) {
        if( $username !== '') al_createwatch( "$id-news", $username);

        header( "Location: project.php?p=$p&tab=subprojects");
        exit;
    }
    $err = $id;
}

apply_template("Report a Bug", array(
    array("name"=>"Report a Bug", "href"=>"newbug.php?p=$p")
));

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}

list($rc,$parent) = ff_getprojectinfo($p);
if( $rc) {
    print "System error: $rc $parent";
    softexit();
}
?>
<h1>Report a Bug</h1>
<script>
function checkAllot() {
    f = document.form;
    if (!f.elements['reqmts'].value.match(/[^ \n\r\t]/)) {
        alert('Please provide a description of the bug');
        return false;
    }

    return confirm('Really submit this bug report?  Please read everything over carefully before submitting.');
}
</script>
<form method="post" name='form' style="clear:left" onSubmit="return checkAllot()">
<input type=hidden id=stopspam name=stopspam value="no">
<script>document.getElementById('stopspam').value='yes';</script>
<input type=hidden name=p value="<?=$p?>">
<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr>
<td valign="top" colspan="2" width="100%">

<b>Project:</b> <a href="project.php?p=<?=$parent["id"]?>&tab=subprojects"><?=htmlentities($parent["name"])?></a><br><br>
</td>
</tr><tr><td valign="bottom" width="50%">
<h2>Bug Priority</h2>
<select name=priority>
<? foreach( array_keys($GLOBALS["priorities"]) as $priority) {
    if( $priority === 'subproject') continue;
?>
<option value="<?=htmlentities($priority)?>"<?=$priority==='low'?" selected":""?>><?=htmlentities($priority)?></option>
<? } ?>
</select>
</td><td valign="bottom" width="50%">
<? if( $parent['lead'] === $GLOBALS["username"]) { ?>
<h2>Bounty Allotment</h2>
<input name="allotment" value="0" size=5>&nbsp;% of <?=convert_money($parent["bounty"])?>
<? } ?>
</td></tr><tr><td valign="bottom" colspan="2" width="100%">
<br>
<h2>Bug Description</h2>
<div style="font-size:small">
(Note: Your first paragraph will also be used as the bug summary.
Use blank lines to separate paragraphs.)
</div>
</td>
</tr><tr>
<td valign="top" colspan="2" width="100%">
<textarea name=reqmts style="width:100%" rows=8><?=htmlentities($reqmts)?></textarea>
</td>
</tr><tr><td width="0%" colspan="2" align=right>
<div id=filelist style="margin-bottom:0.3em"></div>
<table width="100%" cellpadding=0 cellspacing=0><tr>
<td width="100%" align=right valign=top id="atchbtn"></td>
<td width="0%" valign=top><nobr>
&nbsp;<input type=submit value="Create Subproject">
</nobr></td></tr></table>
</td><td>&nbsp;</td>
</tr></table>
<script src='attachments.js'></script>
<script>
var a = setup_attachments('proj');
document.getElementById('filelist').appendChild(a[0]);
document.getElementById('atchbtn').appendChild(a[1]);
</script>
</form>
