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
if( $auth !== 'admin' && $auth !== 'arbiter') {
    print "Not Authorized.";
    exit;
}

if( $_REQUEST["accept"]) {
    $dispute = scrub($_REQUEST["id"]);
    list($rc,$err) = ff_assigndispute( $dispute, $username);
    if( $rc) {
        print "Error: $rc $err";
        exit;
    }
    header( "Location: arbitration.php");
    exit;
}

if( $_REQUEST["relinquish"]) {
    $dispute = scrub($_REQUEST["id"]);
    list($rc,$err) = ff_unassigndispute( $dispute);
    if( $rc) {
        print "Error: $rc $err";
        exit;
    }
    header( "Location: arbitration.php");
    exit;
}

apply_template("Arbitration",array(
    array("name"=>"Arbitration","href"=>"arbitration.php"),
));

list($rc,$disputes) = ff_getactivedisputes();
if( $rc) {
    print "Error: $rc $disputes";
    softexit();
}

if( sizeof($disputes) == 0) {
    print "There are no active disputes.";
    softexit();
}

?>
<h1>Active Disputes</h1>

<table border=1 cellspacing=0 cellpadding=3><tr>
<th>Assigned To</th>
<th>Project</th>
<th>Lead</th>
<th>Plaintiff</th>
<th>Subject</th>
<th>Dispute Began</th>
<th>Debate Ended</th>
</tr>

<?
foreach( $disputes as $dispute) {
    // Get some info about the project
    list($rc,$projectinfo) = ff_getprojectinfo( $dispute["projectid"]);
    if( $rc) {
        print "Problem fetching project info: $rc ".
            htmlentities($projectinfo)."\n";
        softexit();
    }

    $assignee = "";
    if( substr($dispute["assignedto"],0,8) === 'arbiter:') {
        $assignee = substr($dispute["assignedto"],8);
    }
?>
<tr>
<? if( $assignee === "") { ?>
<td>unassigned <nobr><a href="arbitration.php?id=<?=$dispute["disputeid"]?>&accept=1">(assign to me)</a></nobr></td>
<? } else if( $assignee === $username) { ?>
<td><?=$assignee?> <nobr><a href="arbitration.php?id=<?=$dispute["disputeid"]?>&relinquish=1">(relinquish)</a></nobr></td>
<? } else { ?>
<td><a href="member.php?id=<?=$assignee?>"><?=$assignee?></a></td>
<? } ?>
<td><a href="project.php?p=<?=$projectinfo["id"]?>"><?=$projectinfo["id"]?></a></td>
<td><a href="member.php?id=<?=$projectinfo["lead"]?>"><?=$projectinfo["lead"]?></a></td>
<td><a href="member.php?id=<?=$dispute["plaintiff"]?>"><?=$dispute["plaintiff"]?></a></td>
<td><a href="dispute.php?id=<?=$dispute["disputeid"]?>"><?=htmlentities($dispute["subject"])?></a></td>
<td><?=date("M j @ H:i",$dispute["created"])?></td>
<td><?=date("M j @ H:i",$dispute["concluded"])?></td>
</tr>
<?
}
?>
</table>
