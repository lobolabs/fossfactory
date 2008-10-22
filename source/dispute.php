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
$id=scrub($_REQUEST['id']);

if( isset( $_REQUEST["orig"])) {
    include_once("diff.php");
    list($rc,$patch) = diffText($_REQUEST["reverted"], $_REQUEST["orig"]);
    if( !$rc) ff_resolvemergeconflict( $id, $GLOBALS['username'], $patch);
    header("Location: dispute.php?id=$id");
    exit;
}

if( isset( $_REQUEST["op"])) {
    if( $_REQUEST["op"] === 'reply') {
        ff_addargument($id,$GLOBALS['username'],$_REQUEST['body']); 
    } else if( $_REQUEST["op"] === 'cancel') {
        ff_canceldispute($id,$GLOBALS['username']);
    } else if( $_REQUEST["op"] === 'conclude') {
        ff_concludedispute($id,$GLOBALS['username']);
    }
    header("Location: dispute.php?id=$id");
    exit;
}

if( isset( $_REQUEST["decision"])) {
    list($rc,$err) = ff_applydisputedecision(
        $username, $id, $_REQUEST["decision"]);
    if( $rc) {
        print "Error: $rc $err";
        exit;
    }
    header("Location: dispute.php?id=$id");
    exit;
}

list($rc,$disputeinfo) = ff_getdisputeinfo($id);
if( $rc) {
    print "Error getting dispute info: $rc $disputeinfo";
    exit;
}

list($rc,$projectinfo) = ff_getprojectinfo($disputeinfo["projectid"]);
if( $rc) {
    print "Error getting project info: $rc $projectinfo";
    exit;
}

apply_template("Dispute",array(
    array("name"=>"Projects", "href"=>"browse.php"),
    array("name"=>$projectinfo["name"],
        "href"=>projurl($disputeinfo["projectid"],($parent?"#p$parent":""))),
    array("name"=>"Dispute",
        "href"=>"dispute.php?id=$id"),
));
?>
<table border=0 cellpadding=0 cellspacing=8>
    <tr><td valign=top width="0%"><b>Project:</b></td><td width="50%"><a href="<?=projurl($projectinfo["id"])?>"><?=htmlentities($projectinfo["name"])?></a></td>
    <td width="50%" rowspan=4 valign=top>
        <p class="help">
<? if( $GLOBALS["username"] === $disputeinfo["plaintiff"]) { ?>
        <b>Note:</b> This page tracks the complaint that you filed.  Every
        complaint evolves as a debate between the plaintiff and the project
        lead.  It normally terminates when one party or the other decides
        that there is nothing more to add.
<? } else if( $GLOBALS["username"] === $projectinfo["lead"]) { ?>
        <b>Note:</b> This page tracks a complaint that was filed against your
        project.  Every complaint evolves as a debate between the plaintiff
        and the project lead.  It normally terminates when one party or
        the other decides that there is nothing more to add.
<? } else { ?>
        <b>Please Note:</b> This page tracks a complaint that was filed
        on a project.  We invite you to participate by posting your
        comments and opinions using the forum below.  Arbiters will
        often use user comments as a resource to help with their decision.
<? } ?>
        </p>
    </td>
    </tr>
    <tr><td valign=top width="0%"><b>Plaintiff:</b></td><td width="50%"><a href="member.php?id=<?=urlencode($disputeinfo["plaintiff"])?>"><?=htmlentities($disputeinfo["plaintiff"])?></a></td></tr>
    <tr><td valign=top width="0%"><b>Filed:</b></td><td width="50%"><?=date("D, M j, Y H:i T", $disputeinfo["arguments"][0]["time"])?></td></tr>
    <tr><td valign=top width="0%"><b>Subject:</b></td><td width="50%"><?=htmlentities($disputeinfo["subject"]?"$disputeinfo[subject]:":'No subject')?>
<?
    if( $disputeinfo["type"]==='badaccept' ||
        $disputeinfo["type"]==='badreject') {
        list($rc,$submission) = ff_getsubmissioninfo(
            intval($disputeinfo["object"]));
        if(!$rc) {
?>
    <br><a href="<?=projurl($projectinfo["id"],"tab=submissions&s=$submission[submissionid]#tabs")?>"><?=date("M j, Y g:ia",$submission["date"])?> by <?=htmlentities($submission["username"])?></a>
<?
        }
    } else if( $disputeinfo["type"]==='badchange') {
        list($rc,$reqmtshistory) = ff_getreqmtshistory( $projectinfo["id"]);
        if(!$rc) {
            include_once("diff.php");
            foreach($reqmtshistory as $reqmtschange) {
                if( $reqmtschange["postid"] == intval($disputeinfo["object"])) {
                    $post=scrub(ereg_replace(":.$","",$disputeinfo["object"]));
                    $patch=ereg_replace("^[^:]*:","",$disputeinfo["object"]);
?>
    <br><a href="<?=projurl($projectinfo["id"],"post=$post")?>"><?=date("M j, Y g:ia",$reqmtschange["time"])?> - <?=htmlentities($reqmtschange["subject"])?></a>
    </td></tr>
    <tr><td valign=top width="0%"><b>Disputed Change:</b></td><td width="100%" colspan=2>
        <div style="border:1px solid #808080;padding:1em"><?=formatDiff($patch)?></div>
<?
                    break;
                }
            }
        }
    }
?>
</td></tr>
</table>
<? if( $disputeinfo["status"] === 'conflict' &&
        $disputeinfo["plaintiff"] === $GLOBALS["username"]) { ?>
<form method=get>
<div style="border:2px solid #808000;padding:0.7em">
Due to other recent requirements changes, the system can't remove the change
automatically.  Please remove it manually.
<nobr><b>Make no other changes, or the dispute will be thrown out!</b></nobr>
<br><br>
<input type=hidden name=id value="<?=$id?>">
<input type=hidden name=orig id=orig value="<?=htmlentities($projectinfo["reqmts"])?>">
<textarea rows=10 name=reverted id=reverted style="width:100%"><?=htmlentities($projectinfo["reqmts"])?></textarea>
<div align=right>
<input type=submit value="Clear Changes" onclick="if(confirm('Are you sure you want to clear all your changes?'))document.getElementById('reverted').value=document.getElementById('orig').value;return false">
<input type=submit value="Submit" onclick="return confirm('Are you sure?')">
</div>
</div>
</form>
<? } ?>
<br>
<table cellpadding=5 cellspacing=0 width=100% class=dispute>
<tr><th>Time</th><th>User</th><th>Role</th><th>Comments</th></tr>
<?
include_once("formattext.php");

$i = -1;
foreach( $disputeinfo["arguments"] as $arg) {
    $i++;
?>
    <tr valign='top' class='<?=$i&1?'evenrow':'oddrow'?>'>
        <td><?=date("Y-m-d H:i\\&\\n\\b\\s\\p\\;T", $arg["time"])?></td>
        <td><a href="member.php?id=<?=urlencode($arg["username"])?>"><?=htmlentities($arg["username"])?></a></td>
<? if (($i&1)==0) { ?>
        <td><i>plaintiff</i></td>
<? } else { ?>
        <td><i>project<br>lead</i></td>
<? } ?>
        </td>
        <td width="100%">
            <?=$arg['body']?formatText($arg['body']):"&nbsp;"?>
        </td>
    </tr>
<?
}

if( $disputeinfo["status"] === 'defendant')
    $whoseturn = $projectinfo['lead'];
else if( $disputeinfo["status"] === 'plaintiff')
    $whoseturn = $disputeinfo['plaintiff'];
else
    $whoseturn = "nobody"; // A reserved username

?>
<? if( $whoseturn !== $GLOBALS["username"]) { ?>
<tr class='<?=sizeof($disputeinfo["arguments"])&1?'evenrow':'oddrow'?>'>
<? if ($disputeinfo['status']=='cancelled') { ?>
<td colspan='4'>
<i>This dispute has been <b>cancelled</b>.</i>
<? } else if($disputeinfo['status']=='deliberating') { ?>
<td colspan='4'>
<i>This debate has been concluded.  Waiting for the arbiter's decision...</i>
<? } else if($disputeinfo['status']=='plaintiff') { ?>
<td colspan='4'>
<i>Waiting for the plaintiff to respond...</i>
<? } else if($disputeinfo['status']=='defendant') { ?>
<td colspan='4'>
<i>Waiting for the project lead to respond...</i>
<? } else if($disputeinfo['status']=='waiting') { ?>
<td colspan='4'>
<i>This debate has been concluded, but has not yet been sent for arbitration.  One or more <a href="<?=projurl($disputeinfo["projectid"],"tab=disputes#tabs")?>">other disputes</a> must be decided before this one can proceed.</i>
<? } else if($disputeinfo['status']=='conflict') { ?>
<td colspan='4'>
<i>Waiting for the plaintiff to resolve a merge conflict...</i>
<? } else { ?>
<td><?=date("Y-m-d H:i\\&\\n\\b\\s\\p\\;T", $disputeinfo["decided"])?></td>
<td colspan='3'>
The dispute was decided in favour of the <?=$disputeinfo["decision"]==='defendant'?"project lead":"plaintiff"?>.
<? } ?>
</td><tr>
<? } ?>
</table><br>
<? if( $whoseturn === $GLOBALS["username"]) { ?> 
<script>
function updateFormState() {
    document.getElementById('disputetext').disabled =
        document.getElementById('opreply').checked ? 0 : 1;
    document.getElementById('submitform').disabled =
        (document.getElementById('opreply').checked ||
<? if( $disputeinfo['status'] === 'plaintiff') { ?>
        document.getElementById('opcancel').checked ||
<? } ?>
        document.getElementById('opconclude').checked) ? 0 : 1;
}
function checkForm() {
    if( document.getElementById('opreply').checked) {
        if( !document.getElementById('disputetext').
            value.match( /[^ \n\r\t]/)) {
            alert('You forgot to enter your comments.');
            return false;
        }
        return true;
    } else if( document.getElementById('opconclude').checked) {
        return confirm('Are you sure you want to conclude the dispute?');
<? if( $disputeinfo['status'] === 'plaintiff') { ?>
    } else if( document.getElementById('opcancel').checked) {
        return confirm('Are you sure you want to cancel your complaint?');
<? } ?>
    }
    alert('Please choose an action.');
    return false;
}
</script>
<form onSubmit='return checkForm()'>
<div style="border:2px solid #808000;padding:0.7em">
<b>Please choose one of the following actions:</b>
<table border=0 cellpadding=10 cellspacing=0 width="100%">
<tr>
<td width="50%" valign=top>
<p>
<input id=opconclude type=radio name=op value="conclude" onChange='updateFormState()'>&nbsp;<label for="opconclude" style="font-weight:bold;color:#117aad">Conclude the Dispute</label>
<div style="font-size:small;font-style:italic;margin-top:-0.5em">
Use this option if you have no more comments to add.  The dispute will be
forwarded as-is to an arbiter.
</div>
</p>
<? if( $disputeinfo['status'] === 'plaintiff') { ?>
<p>
<input id=opcancel type=radio name=op value="cancel" onChange='updateFormState()'>&nbsp;<label for="opcancel" style="font-weight:bold;color:#117aad">Cancel the Complaint</label>
<div style="font-size:small;font-style:italic;margin-top:-0.5em">
Use this option if you feel that the issue has been resolved, or if you
wish to withdraw your complaint.  The dispute will still be available for
viewing, but it will be marked as cancelled.
</div>
</p>
<? } ?>
</td>
<td width="50%" valign=top>
<p>
<input id=opreply type=radio name=op value="reply" onChange='updateFormState()'>&nbsp;<label for="opreply" style="font-weight:bold;color:#117aad">Add Comments</label>
<div style="font-size:small;font-style:italic;margin-top:-0.5em;margin-bottom:0.5em">
Make sure to express your position clearly.   This may be your last
opportunity to comment before the issue is sent for arbitration.
</div>
</p>
<textarea id=disputetext rows=7 name=body style="width:100%" disabled></textarea>
</td>
</tr>
</table>
<center><input id=submitform type=submit value="Submit" disabled></center>
<input type=hidden name='id' value='<?=$id?>'>
</div>
</form>
<? } else if( $disputeinfo["status"] === 'deliberating' &&
        $disputeinfo["assignedto"] === "arbiter:$username") { ?>
<form method=get>
<div style="border:2px solid #808000;padding:0.7em">
<b>Please decide the outcome of this dispute:</b>
<table border=0 cellpadding=10 cellspacing=0 width="100%">
<tr>
<td width="50%" valign=top>
<? if( $disputeinfo["type"] == 'badchange') { ?>
<input id=defendant type=radio name=decision value=defendant>&nbsp;<label for="defendant">Allow the requirements change</label>
<p class=help>
If the requirements change is an important clarification that <b>does
not change what is required of submissions in any controversial way</b>,
then it should be allowed.
</p>
<? } else if( $disputeinfo["type"] == 'badaccept') { ?>
<input id=defendant type=radio name=decision value=defendant>&nbsp;<label for="defendant">Allow the submission to be accepted</label>
<p class=help>
If the submission fully met all project requirements at the time it
was submitted, then the acceptance should be allowed to proceed.
</p>
<? } else if( $disputeinfo["type"] == 'badreject') { ?>
<input id=defendant type=radio name=decision value=defendant>&nbsp;<label for="defendant">Allow the submission to be rejected</label>
<p class=help>
If the submission failed to meet any requirement as specified at the time it
was submitted, then it should stay rejected.
</p>
<? } ?>
</td>
<td width="50%" valign=top>
<? if( $disputeinfo["type"] == 'badchange') { ?>
<input id=plaintiff type=radio name=decision value=cancelchange>&nbsp;<label for="plaintiff">Cancel the requirements change</label>
<p class=help>
If the change makes any controversial, substantial change to what
is required of submissions, then it should be cancelled.  Whether
requirements are added or removed, in either case such changes should
be rejected if they are in any way controversial.
</p>
<? } else if( $disputeinfo["type"] == 'badaccept') { ?>
<input id=plaintiff type=radio name=decision value=reject>&nbsp;<label for="plaintiff">Reject the submission</label>
<p class=help>
If the submission failed to meet any requirement as specified at the time it
was submitted, then it should be rejected.
</p>
<? } else if( $disputeinfo["type"] == 'badreject') { ?>
<input id=plaintiff type=radio name=decision value=accept>&nbsp;<label for="plaintiff">Accept the submission</label>
<p class=help>
If the submission fully met all project requirements at the time it
was submitted, then it should be accepted.
</p>
<? } ?>
</td>
</tr>
</table>
<input type=submit value="Submit Decision" onclick="return confirm('Are you sure you want to submit your decision?')">
<input type=hidden name='id' value='<?=$id?>'>
</div>
</form>
<? } ?>
<? 
include_once("forum.php");
show_forum("spect$id","");?>
