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
$id = scrub($_REQUEST["id"]);

if ($GLOBALS['username']=='') {
    $msg = "To file a complaint, you need to be logged in.";
    include("login.php");
    softexit();
}

include_once('formattext.php');

if( isset( $_REQUEST["type"])) {//user submitted form
    if( $_REQUEST["type"] === 'badaccept')
        $object = scrub($_REQUEST['acceptedproject']);
    else if( $_REQUEST["type"] === 'badreject')
        $object = scrub($_REQUEST['rejectedproject']);
    else if( $_REQUEST["type"] === 'badchange') {
        $object = scrub($_REQUEST['reqmtschange']).":$_REQUEST[patch]";
    } else exit;

    //initiate new dispute
    list($rc,$disputeid) = ff_createdispute( $id, $GLOBALS['username'],
        $_REQUEST['type'], $object, $_REQUEST['body']);

    header("Location: dispute.php?id=$disputeid");
    exit;
}

// Get the project info
list($rc,$projinfo) = ff_getprojectinfo( $id);
if( $rc == 2) {
    print "No such project: $id";
    softexit();
}

apply_template("File a Complaint",array(
    array("name"=>"Projects", "href"=>"browse.php"),
    array("name"=>$projinfo["name"],
        "href"=>"project.php?p=$id"),
    array("name"=>"Post a Complaint",
        "href"=>"disputepost.php?id=$id"),
));

// Get a list of all submissions
list($rc,$submissions) = ff_getsubmissions( $id);
if($rc) {
    print "System error.  Please try again later.";
    exit;
}
list($rc,$reqmtshistory) = ff_getreqmtshistory( $id);
if($rc) {
    print "System error.  Please try again later.";
    exit;
}

?>
<h1>File a Complaint</h1>
<b>Project:</b> <a href="project.php?p=<?=$projinfo["id"]?>"><?=htmlentities($projinfo["name"])?></a></br>
<p>
Use this form to file a complaint regarding the management of the above
project.
</p>
<p>
When your complaint is filed, the project lead will be notified, and is
required to respond.  When he responds, you will most likely have to follow up,
so watch for system notifications.
</p>
<p>
Before filing your complaint, please familiarize yourself with the process
by reading the <a href="overview.php#disputes">Disputes</a> section of the
System Overview.
</p>

<form method="post">
<input type=hidden name=id value="<?=$id?>">
<input type=hidden name=patch id=patch value="">
<p>
What is the subject of your complaint?
</p>
<table border=0 cellpadding=2 cellspacing=0>
<tr>
<td valign=top width="0%">
<input type=radio name=type value=badreject id=badreject onclick="return selecttype(this.value)">
</td><td valign=top width="100%">
<label for=badreject><b>A submission was rejected even though it meets all of the project requirements:</b></label><br><br>
</td><td valign=top width="0%">
<select name=rejectedproject id=rejectedproject disabled>
<option value=0>* Choose One *</option>
<?
foreach( $submissions as $submission) {
    if( $submission["status"] !== 'reject' &&
        $submission["status"] !== 'prejudice') continue;
?>
<option value=<?=$submission["id"]?>><?=date("M j, Y g:ia",$submission["date"])?> by <?=htmlentities($submission["username"])?></option>
<?
}
?>
</select>
</td></tr>

<tr>
<td valign=top width="0%">
<input type=radio name=type value=badaccept id=badaccept onclick="return selecttype(this.value)">
</td><td valign=top width="100%">
<label for=badaccept><b>A submission was accepted that does *not* meet the project requirements:</b></label><br><br>
</td><td valign=top width="0%">
<select name=acceptedproject id=acceptedproject disabled>
<option value=0>* Choose One *</option>
<?
foreach( $submissions as $submission) {
    if( $submission["status"] !== 'accept') continue;
?>
<option value=<?=$submission["id"]?>><?=date("M j, Y g:ia",$submission["date"])?> by <?=htmlentities($submission["username"])?></option>
<?
}
?>
</select>
</td>

</tr><tr>

<td valign=top width="0%" rowspan=2>
<input type=radio name=type value=badchange id=badchange onclick="selecttype(this.value)">
</td><td valign=top width="100%">
<label for=badchange><b>The project requirements were changed in a bad way:</b></label>
</td><td valign=top width="0%">
<select name=reqmtschange id=reqmtschange disabled onChange="setreqmtschange(this.value);return true">
<option value=0>* Choose One *</option>
<?
for( $i=sizeof($reqmtshistory)-1; $i >= 0; $i--) {
    $changeevent = $reqmtshistory[$i];
    if( $changeevent["action"] !== 'accept') continue;
?>
<option value=<?=$changeevent["postid"]?>><?=date("M j, Y g:ia",$changeevent["time"])?> - <?=htmlentities($changeevent["subject"])?></option>
<?
}
?>
</select>
</td>
</tr><tr>
<td colspan=2 width="100%">
<div id=patchdiv style="display:none">
<div id=patchinfo style="display:none">
<div id=formattedpatch style="margin:1em;padding:1em;border:1px solid #808080"></div>
</div>
</div>
</td>

</tr></table>

<p>
Please clearly explain, in detail, the reasoning behind your complaint.  This
may be your only opportunity to comment before the issue is sent for
arbitration.
</p>

<textarea rows=10 name=body id=body style="width:100%"></textarea>

<script>
patches = [];
rawpatches = [];
<?
include_once("diff.php");
foreach( $reqmtshistory as $changeevent) {
    if( $changeevent["action"] !== 'accept') continue;
    print "patches['".$changeevent["postid"]."']='".
        jsencode(formatDiff($changeevent["patch"]))."';\n";
    print "rawpatches['".$changeevent["postid"]."']='".
        jsencode($changeevent["patch"])."';\n";
}
?>

function selecttype(v) {
    document.getElementById('rejectedproject').disabled=1;
    document.getElementById('acceptedproject').disabled=1;
    document.getElementById('reqmtschange').disabled=1;
    if( v == 'badreject') {
        document.getElementById('rejectedproject').disabled=0;
    } else if( v == 'badaccept') {
        document.getElementById('acceptedproject').disabled=0;
    } else if( v == 'badchange') {
        document.getElementById('reqmtschange').disabled=0;
    }
    if( v == 'badchange') {
        document.getElementById('patchdiv').style.display='block';
    } else {
        document.getElementById('patchdiv').style.display='none';
    }
    return true;
}

prevreqs = '0';
function setreqmtschange(postid) {
    prevreqs = postid;
    if( postid=='0') {
        document.getElementById('patchinfo').style.display = "none";
        return true;
    }
    document.getElementById('formattedpatch').innerHTML = patches[postid];
    document.getElementById('patchinfo').style.display = "block";
    return true;
}

function checkform() {
    if( !document.getElementById('badreject').checked &&
        !document.getElementById('badaccept').checked &&
        !document.getElementById('badchange').checked) {
        alert('You must choose a subject for your complaint.');
        return false;
    }

    if( document.getElementById('badreject').checked &&
        document.getElementById('rejectedproject').value == 0) {
        alert('Please indicate which project was wrongly rejected.');
        return false;
    }

    if( document.getElementById('badaccept').checked &&
        document.getElementById('acceptedproject').value == 0) {
        alert('Please indicate which project was wrongly accepted.');
        return false;
    }

    if( document.getElementById('badchange').checked &&
        document.getElementById('reqmtschange').value == 0) {
        alert('Please indicate which requirements change you are opposing.');
        return false;
    }

    if( /^[ \t\r\n]*$/.test(document.getElementById('body').value)) {
        alert('You forgot to provide a reason for your complaint.');
        return false;
    }

    document.getElementById('patch').value =
        rawpatches[document.getElementById('reqmtschange').value];

    return confirm('Are you sure you are ready to submit this complaint?');
}
</script>

<p>
<input type=submit value="Submit Complaint" onclick="return checkform()">
</p>
</form>
<script>
document.getElementById('badreject').checked = 0;
document.getElementById('badaccept').checked = 0;
document.getElementById('badchange').checked = 0;
document.getElementById('reqmtschange').value = 0;
</script>
