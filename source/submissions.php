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
$id = scrub($id);

$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

list($rc,$submissions) = ff_getsubmissions( $id);

// Remove from the list any projects that have been rejected with prejudice
$s = array();
foreach( $submissions as $key => $submission) {
    if( $submission["status"] === 'prejudice' &&
        intval($_REQUEST["s"]) != intval($submission["id"])) continue;
    $s[$key] = $submission;
}
$submissions = $s;

if( $rc || sizeof($submissions) == 0) {
?>
<br>
<b>No submissions have been made for this project.</b>
<br>
<br>
<?button("Make a Submission","submission.php?id=$id")?>
<?
} else {
    // Get the current project requirements
    list($rc,$projectinfo) = ff_getprojectinfo( $id);
    if( $rc) {
        print "Error: $rc $projectinfo";
        softexit();
    }
?>
<script src="folder.js"></script>
<table border=0 cellspacing=0 class=submissions>
<? 
$foundcurrent = 0;
$rownum = 0;
foreach ($submissions as $key => $submission) {
    $rownum++;
    $current = 0;

    if( !$foundcurrent && (
        $submission['status']=='accept' ||
        $submission['status']=='complete' ||
        $submission['status']=='pending')) {
        $foundcurrent = 1;
        $current = 1;
    }

    if( isset($_REQUEST["s"]))
        $unfolded = ($submission["id"] == intval($_REQUEST["s"]));
    else $unfolded = $current;
?>
<tr<?=($rownum&1)?" class=oddrow":""?>><td width="0%" valign="top"><img class=arrow id="sbm<?=$submission['id']?>-arrow" src="<?=$unfolded?"arrow-down.gif":"arrow-right.gif"?>" onClick="folder('sbm<?=$submission['id']?>')"></td><td width="100%" style="padding-right:1em"><a name=subm<?=$submission['id']?>></a><a class=folder href="#" onClick="folder('sbm<?=$submission['id']?>');return false"><span class=postdate>&nbsp;<?=htmlentities(date("M j, Y g:ia",$submission['date']))?></span> <i>Submitted by <?=htmlentities($submission['username'])?></i></a> <? 
            if ($submission['status']=='complete') {
                print "<span class=accepted>[COMPLETE]</span>";
            } elseif($submission['status']=='accept') {
                print "<span class=accepted>[ACCEPTED]</span>";
            } elseif($submission['status']=='reject') {
                print "<span class=rejected>[REJECTED]</span>";
            } elseif($submission['status']=='pending') {
                print "<span class=pending>[PENDING]</span>";
            } elseif($submission['status']=='prejudice') {
	    	print "<span class=rejected>[REJECTED WITH PREJUDICE]</span>";
	    }

?>
<div id="sbm<?=$submission['id']?>-div" class=<?=$unfolded?"unfolded":"folded"?>>
<? if( $submission["reqmts"] !== $projectinfo["reqmts"]) { ?>
<div class=importantnote><b>Note:</b> The project requirements have been changed since this submission was made.  This submission does <b>not</b> have to meet the new requirements.  Below is a copy of the requirements at the time that this submission was made.</div>
<?
$body = formatText($submission["reqmts"]);
if( $projectinfo["numattachments"] > 0)
    list($rc,$body) = ff_attachtoproject($projectinfo['id'],$body);
?>
<div class=spec><?=$body?></div>
<br>
<? } ?>
<b>Submission notes:</b><br>
<?=formatText($submission['comments'])?>
<table cellpadding=0 cellspacing=0>
<? foreach($submission['files'] as $filekey => $file) { ?>
        <tr>
            <td><?=htmlentities($file['filename'])?></td>
            <td align=right>&nbsp;&nbsp;&nbsp;(<?=htmlentities($file['filesize'])?> bytes)</td>
            <td>&nbsp;&nbsp;&nbsp;<nobr><a href="displaysubmission.php/<?=$submission['id']?>/<?=($filekey+1)?>/<?=urlencode($file['filename'])?>" style="font-size:small">[download]</a>&nbsp;<?
                if (ereg ("\\.(pdf|txt|png|jpeg|jpg|html|htm|patch)$",strtolower(htmlentities($file['filename'])),$regs)) {
                ?>
                <a href="viewsubmission.php/<?=$submission['id']?>/<?=($filekey+1)?>/<?=urlencode($file['filename'])?>" style="font-size:small;margin-right:2em">[view]</a>
             <?   
             }
             ?></nobr></td>
        </tr>    
    <? } ?>
</table>
<? if( file_exists("/home/git/s$submission[id].git")) { ?>
<p>
<b>Git Download:</b><br>
<tt>git clone git@<?=htmlentities($hostname)?>:s<?=$submission['id']?></tt>
</p>
<? } ?>
<br>
<?
    if( $submission["status"] === "reject") {
?>
<b>Reason for rejection</b>:
<blockquote>
<?=htmlentities($submission["rejectreason"]?$submission["rejectreason"]:"No reason provided")?></blockquote>
<?
        if( $projinfo["status"] !== "complete" && !$foundcurrent &&
            $GLOBALS['username'] !== "" &&
            $GLOBALS['username'] === $projinfo['lead']) {
?>
<p style="font-size:small">
As project lead, you may still accept this submission if you realize that it
meets the project requirements after all.
</p>
<form method="post" action="handlesubmission.php">
<input type=hidden name=id value="<?=$id?>">
<input type=hidden name=submissionid value="<?=$submission["id"]?>">
<input type=hidden name=accept value=true>
<input type=submit value="Accept" onClick="return confirm('Are you sure you want to accept this submission?')">
</form>
<?
        }
    } else if( $current && $submission["status"] !== 'complete' &&
        $GLOBALS['username'] !== "" &&
        $GLOBALS['username'] === $projinfo['lead']) {
        // This submission is awaiting the project lead's decision.
        // Determine the deadline for the decision to be made.
?>
<p style="font-size:small">
<? if( $submission["status"] === 'pending') { ?>
As project lead, you must decide whether this submission meets the project
requirements.
<? } else { ?>
This submission has been accepted, and the payment will occur
<b><?=date("D, M j, Y @ H:i T",$projinfo["payout_time"])?></b>.  As project
lead, you may still reject the submission before the payment occurs.  This is
useful if you realize that the submission does not satisfy the project
requirements after all.
<? } ?>
</p>
<form method="post" action="handlesubmission.php">
<input type=hidden name=id value="<?=$id?>">
<input type=hidden name=submissionid value="<?=$submission["id"]?>">

<table border=0>
<? if( $submission["status"] === 'pending') { ?>
<tr>
<td valign=top><input type=radio id=accept name=accept value=true onclick="document.getElementById('apply').disabled=0;document.getElementById('rejectreason').disabled=1;return true"></td>
<td><label for=accept><b>Accept</b> - The submission meets all project requirements.</label></td>
</tr>
<? } ?>
<tr>
<td valign=top><input type=radio id=reject name=accept value=false onclick="document.getElementById('apply').disabled=0;document.getElementById('rejectreason').disabled=0;return true"></td>
<td><label for=reject><b>Reject</b> - The submission fails to meet one or more project requirements.</label><br>
<i style="font-size:small">Please describe how the code fails to meet the requirements:</i>
<textarea id=rejectreason name=rejectreason style='width:100%;height:5em' disabled></textarea>
</td>
</tr><tr>
<td valign=top><input type=radio id=prejudice name=accept value=prejudice onclick="document.getElementById('apply').disabled=0;document.getElementById('rejectreason').disabled=1;return true"></td>
<td><label for=prejudice><b>Reject with prejudice</b> - The submission is
clearly not a serious attempt to meet the project requirements.
<i>Use this option only for extremely deficient submissions (Eg, prank or
accidental submissions, or if there is a serious misunderstanding of project
requirements).</i></label>
</td>
</tr><tr>
<td>&nbsp;</td>
<td><script>
function confirmapply() {
    if(document.getElementById('reject').checked &&
        /^[ \t]*$/.test(document.getElementById('rejectreason').value)) {
        alert('You must provide a reason for rejecting the submission.');
        return false;
    }
    if(document.getElementById('reject').checked) {
        return confirm('Are you sure you want to reject this submission?');
<? if( $submission["status"] === 'pending') { ?>
    } else if(document.getElementById('accept').checked) {
        return confirm('Are you sure you want to accept this submission?');
<? } ?>
    }
    return confirm('Are you sure you want to reject this '+
        'submission with prejudice?');
}
</script><input type=submit value="Apply" id=apply disabled onclick="return confirmapply()"></td>
</tr></table>
</form>
<?
    }
?>
</div>
</td></tr>
<? } ?>
</table>

<? show_forum("subm${id}",$projinfo['name']); ?>
<?
}
?>
