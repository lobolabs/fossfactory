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
$name = "$_REQUEST[name]";
$reqmts = "$_REQUEST[reqmts]";
// Assume English numbers: 1,000,000.01 OR 1 000 000.01 -> 1000000.01
$fundgoal = str_replace( array( ' ', ',' ), '', $_REQUEST['fundgoal'] );

if( !$p) exit;

list($rc,$parent) = ff_getprojectinfo($p);
if( $rc) {
    print "System error: $rc $parent";
    softexit();
}

$islead = ($parent["lead"] !== '' && $parent["lead"] === $GLOBALS["username"]);

if( trim($name) && trim($reqmts)) {
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

    $lead = $_REQUEST["makemelead"] ? $username :
        ($parent['lead'] ? $parent['lead'] : '');

    list($rc,$id) = ff_createproject($username, $name, $reqmts, $p,
        $attachments, false, 'subproject', $lead,
        isset($_REQUEST["allotment"]) ?
            round(floatval($_REQUEST["allotment"]) * 10) : false);
    if( !$rc) {
        ff_setfundinggoal( $username, $id, (int)( $fundgoal * 100 ).$GLOBALS['pref_currency'] );
        header( "Location: ".projurl($id));
        exit;
    }
    $err = $id;
}

apply_template("New Project", array(
    array("name"=>"New Project", "href"=>"newsubproject.php?p=$p")
));

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}

?>
<h1>Create a New Subproject</h1>
<? if( $username == '') { ?>
<p class="note">
You are not currently logged in!  If you want to be
the initial lead for this project, then you should
<a href="login.php?url=<?=urlencode("newsubproject.php?p=$p")?>">log
in</a> before creating it.
</p>
<? } ?>
<script>
function checkAllot() {
    f = document.form;
    if (!f.elements['name'].value.match(/[^ \n\r\t]/) ||
        !f.elements['reqmts'].value.match(/[^ \n\r\t]/)) {
        alert('Please fill out all the fields before submitting');
        return false;
    }

    return confirm('Are you sure you want to create the new project?  Please read everything over carefully before submitting.');
}
</script>
<form method="post" name='form' style="clear:left" onSubmit="return checkAllot()">
<input type=hidden id=stopspam name=stopspam value="no">
<script>document.getElementById('stopspam').value='yes';</script>
<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr>
<td valign="top" colspan="<?=$islead?3:2?>" width="100%">
<p>
The goal of every subproject is to tackle one portion of its parent
project, and to further specify and/or clarify the requirements of
that portion.  Subprojects are FOSS Factory's mechanism for enabling
collaborative design and development.
</p>

<p>
When a subproject is created, the parent project's project lead may
grant it a portion of the parent project's funding.  The decision will
be made based on the subproject's quality, clarity, scope and relevance.
</p>

<p>
<a href="overview.php#subprojects">Learn more about subprojects.</a>
</p>

<b>Parent Project:</b> <a href="<?=projurl($parent["id"])?>"><?=htmlentities($parent["name"])?></a><br><br>
<input type=hidden name=p value="<?=$p?>">
</td><td valign="top" rowspan="3" width="0%">
<div class=important>
<b>Tip:</b> When creating low-level subprojects, consider providing detailed
interface descriptions.  (Eg. function prototypes, class interfaces,
command-line specs, etc.)  This will ensure code-compatibility with the rest
of the system.
</div>
</td></tr><tr><td valign="bottom" width="<?=$islead?33:50?>%">
<h2>Subproject Name</h2>
<input name=name value="<?=htmlentities($name)?>" style="width:22em">
</td><td valign="bottom" width="<?=$islead?33:50?>%">
<h2>Initial Subproject Funding Goal</h2>
<?php echo $GLOBALS['pref_currency']; ?> <input name="fundgoal" value="<?=htmlentities(number_format ($fundgoal, 2))?>" size="7" />
</td>
<? if( $islead) { ?>
<td valign="bottom" width="34%">
<h2>Bounty Allotment</h2>
<input name="allotment" value="0" size=5>&nbsp;% of <?=convert_money($parent["bounty"])?>
</td>
<? } ?>
</tr><tr><td valign="bottom" colspan="<?=$islead?3:2?>" width="100%">
<br>
<h2>Subproject Requirements</h2>
<div style="font-size:small">
(Note: Your first paragraph will also be used as the subproject abstract.
Use blank lines to separate paragraphs.)
</div>
</td>
</tr><tr>
<td valign="top" colspan="<?=$islead?3:2?>" width="100%">
<textarea name=reqmts style="width:100%" rows=16><?=htmlentities($reqmts)?></textarea>
<input name=makemelead id=makemelead value=1 type=checkbox checked> <label for=makemelead>Make me the project lead for this subproject</label>
</td><td valign=center width="0%">
<div class=help>
<b>Can the requirements be changed later?</b>
Once the project is created, anyone can propose requirements changes.  The project lead will be responsible for accepting or rejecting change proposals.
</div><br><br><br>
</td>
</tr><tr><td width="0%" colspan="<?=$islead?3:2?>" align=right>
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
