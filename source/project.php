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
$id = scrub($_REQUEST["p"]);
$tab = scrub($_REQUEST["tab"]);

if( isset($_REQUEST["sponsor_amount"])) {
    if( !is_secure()) {
        header( "Location: $GLOBALS[SITE_URL]project.php?p=$id".
            "&tab=".urlencode($tab)."&sp_err=1");
        exit;
    }

    $amount = '';
    list($rc,$currencies) = ff_currencies();
    if( !$rc) {
        if( !isset($currencies[$_REQUEST["currency"]])) exit;

        $currency = $currencies[$_REQUEST["currency"]];

        $amount = round($_REQUEST["sponsor_amount"] *
            $currency["multiplier"]).$currency["code"];

        list($rc,$err) = ff_setsponsorship( $id, $username, $amount, true);
    }

    header( "Location: $GLOBALS[SITE_URL]project.php?p=$id".
        "&tab=".urlencode($tab)."&sp_err=$rc&amount=$amount");
    exit;
} else if( isset($_REQUEST["remove_amount"])) {
    $amount = '';
    list($rc,$currencies) = ff_currencies();
    if( !$rc) {
        if( !isset($currencies[$_REQUEST["currency"]])) exit;

        $currency = $currencies[$_REQUEST["currency"]];

        $amount = round($_REQUEST["remove_amount"] *
            $currency["multiplier"]).$currency["code"];

        list($rc,$err) = ff_setsponsorship( $id, $username, "-$amount", true);
    }

    header( "Location: project.php?p=$id".
        "&tab=".urlencode($tab)."&r_err=$rc&amount=$amount");
    exit;
} else if( $_POST['init_goal'] && $GLOBALS['username'] ) {
    // Assume English numbers: 1,000,000.01 OR 1 000 000.01 -> 1000000.01
    $amount = (float) str_replace( array( ' ', ',' ), '', $_POST['init_goal'] );
    $amount = (int)( $amount * 100 );
    list( $rc, $msg ) = ff_setfundinggoal( $GLOBALS['username'], $id, $amount.$GLOBALS['pref_currency'] );
    if( $rc == 0 )
        header( 'Location: project.php?p='.$id.'&tab='.urlencode( $tab ) );
}

$parent = scrub($_REQUEST["parent"]);
$post = scrub($_REQUEST["post"]);
if (!$tab) $tab= 'requirements';

// Get the project info
list($rc,$projinfo) = ff_getprojectinfo($id);
if( $rc == 2) {
    print "No such project: $id";
    softexit();
}

include_once("formattext.php");

$onload = "";
if( $post) {
    list($rc,$postinfo) = ff_getpostinfo($post);
    if( !$rc) {
        $ancestry = $postinfo['ancestors'];
        $ancestry[] = $post;
        $ancestry = implode("/",$ancestry);
        $onload = "fold3('reqmts$id','$ancestry')";
    }
}

apply_template($projinfo["name"],array(
    array("name"=>"Projects", "href"=>"browse.php"),
    array("name"=>$projinfo["name"], "href"=>"project.php?p=$id"),
    ),$onload);
?>
<div class="relatedprojects">
<div class="sidenoteheader"><nobr>Related Projects</nobr></div>
<iframe class="relatedprojects" src="tree.php?p=<?=$id?>"></iframe>
<? if ($projinfo['status']!='complete') { ?>
    <ul id="options">
        <li class="first-child"><a href="newsubproject.php?p=<?=$id?>">Create a Subproject</a></li>
        <li><a href="disputepost.php?id=<?=$id?>">File a Complaint</a></li>
        <li class="bug"><a href="newbug.php?p=<?=$id?>">Report a Bug</a></li>
        <li class="last-child"><a href="submission.php?id=<?=$id?>">Make a Submission</a></li>
    </ul>
<? } ?>
</div>
<div class="projectinfo">
<? if( $_REQUEST["err"] === 'created') { ?>
<div class="results">
Thank you for creating a new FOSS Factory project.  Your payment of
<?=format_money("$_REQUEST[amt]")?> has been received.  A receipt
has been emailed to you.  You may log into your account at
<a href="http://www.paypal.com/">www.paypal.com</a> to view details of
the transaction.
</div>
<? } else if( intval($_REQUEST["err"]) == 1) { ?>
<div class="error">Your attempt to become the project
lead has failed.</div>
<? } ?>
<? if( $_REQUEST["sp_err"] === '0') { ?>
<div class="results">Your sponsorship of
<?=format_money($_REQUEST["amount"])?> has been added to this project.</div>
<? } else if( $_REQUEST["sp_err"] === '9') { ?>
<div class="error">The sponsorship was cancelled due to insufficient funds
in your reserve.</div>
<? } else if( $_REQUEST["sp_err"]) { ?>
<div class="error">Due to a system error, the sponsorship operation failed.
The money is still in your reserve.  Please try again later.</div>
<? } else if( $_REQUEST["r_err"] === '0') { ?>
<div class="error"><?=format_money($_REQUEST["amount"])?> has been removed
from this project's bounty and placed in your
<a href="account.php?tab=reserve">reserve</a>.</div>
<? } else if( $_REQUEST["r_err"] === '8') { ?>
<div class="error">The amount you requested couldn't be removed due to a hold on project funds.  This is because of one or more recent code submissions either on this project or on a subproject.</div>
<? } else if( $_REQUEST["r_err"] === '9') { ?>
<div class="error">The amount you requested couldn't be removed because it is more than than your current sponsorship of this project.</div>
<? } else if( $_REQUEST["r_err"]) { ?>
<div class="error">The removal couldn't be performed due to a system error.  Please try again.</div>
<? } else if( $_REQUEST["pp_err"] === '1') { ?>
<div class="results">Your transfer of <?=format_money("$_REQUEST[gross]$_REQUEST[currency]")?> was received from PayPal, but it could not be transferred into this project.  The money has been placed in your <a href="account.php?tab=reserve">reserve</a>.  <? if( ereg("[1-9]",$_REQUEST["fee"])) { ?>A PayPal transaction fee of <?=format_money("$_REQUEST[fee]$_REQUEST[currency]")?> was deducted.  <? } ?>A receipt has been sent to you by email.  You may log into your account at <a href="http://www.paypal.com/">www.paypal.com</a> to view details of this transaction.</div>
<? } else if( isset($_REQUEST["pp_err"])) { ?>
<div class="results">Thank you for your sponsorship of <?=format_money("$_REQUEST[gross]$_REQUEST[currency]")?>.  <? if( ereg("[1-9]",$_REQUEST["fee"])) { ?>A PayPal transaction fee of <?=format_money("$_REQUEST[fee]$_REQUEST[currency]")?> was deducted.  <? } ?>A receipt has been sent to you by email.  You may log into your account at <a href="http://www.paypal.com/">www.paypal.com</a> to view details of this transaction.</div>
<? } ?>

<span class=project-title><?=htmlentities($projinfo["name"])?></span>

<div id=projectlead><h1>Project Lead: </h1>
<? if( $projinfo["lead"]) { ?>
<a href="member.php?id=<?=urlencode($projinfo["lead"])?>"><?=htmlentities($projinfo["lead"])?></a>
<?
    list($rc,$hiscredits) = ff_numcredits( $id, $projinfo["lead"]);
    if( $rc == 0) print "<em>($hiscredits credits)</em>";
    if( $projinfo["lead"] === $username) {
?><br><a href="resign.php?id=<?=$id?>" onClick="return confirm('Are you sure you want to resign?')">(resign)</a><?
    }
} else {
    $hiscredits = -1;
?>

<b>None</b>
<?
}
?>
<p>
<?
if( $username !== '') {
    list($rc,$memberinfo) = ff_getmemberinfo( $username);
    if( $rc) exit;
    list($rc,$donations) = ff_memberdonations( $username, $id);
    if( $rc) exit;
}
if( $username && $username !== $projinfo["lead"]) {
    if( $donations[$id]["credits"] &&
        $donations[$id]["assignee"] !== $username) {
?>You have assigned <?=$donations[$id]["credits"]?> credits to <a href="member.php?id=<?=urlencode($donations[$id]["assignee"])?>"><?=htmlentities($donations[$id]["assignee"])?></a>.<?
    } else {
        list($rc,$mycredits) = ff_numcredits( $id, $username);
        if( $rc == 0) {
?>You have <a href="javascript:folder('sponsor','orange')"><?=$mycredits?></a> credits. <?
            if( $mycredits > $hiscredits) {
?>
<a href="supplant.php?id=<?=$id?>" onClick="return confirm('Are you sure you want to become the\nnew project lead for this project?')">(Become Project Lead)</a>
<?
            }
        }
    }
} else print "&nbsp;";
?></p></div>

<div id=bounty><h1>Bounty: </h1><?=convert_money($projinfo["bounty"])?><a href="javascript:folder('sponsor','orange')">(Sponsor Project)</a></div>

<?php if( !empty( $projinfo['funding_goal'] ) ) { ?>
<div id="thermometer">
    <div>
        <h1>Goal funding:</h1>
        <a href="setvote.php?type=funding&amp;vote=more&amp;id=<?php echo $id.'&amp;tab='.urlencode( $_REQUEST['tab'] ); ?>" title="Vote for higher goal funding">More</a>
        <a href="setvote.php?type=funding&amp;vote=less&amp;id=<?php echo $id.'&amp;tab='.urlencode( $_REQUEST['tab'] ); ?>" title="Vote for lower goal funding">Less</a>
    </div>
    <div><img src="thermo.php?p=<?php echo ( converted_value( $projinfo['bounty'] ) / converted_value( $projinfo['funding_goal'] ) ).'&amp;t='.converted_value($projinfo['funding_goal']).$GLOBALS['pref_currency']; ?>" /></div>
</div>
<?php } else if( $projinfo['creator'] == $GLOBALS['username'] ) { ?>
<div id="thermometer">
    <div>
        <h1>Goal funding:</h1>
        Initial amount
    </div>
    <div>
        <form method="post">
            <?php echo $GLOBALS['pref_currency']; ?>
            <input type="text" name="init_goal" size="7" />
            <input type="submit" value="Set" />
        </form>
    </div>
</div>
<?php } ?>

<span class=abstract-content>
<div style="float:right">
<script type="text/javascript">
digg_url = reddit_url = 'http://www.fossfactory.org/project.php?p=<?=$id?>';
</script>
<script src="http://digg.com/tools/diggthis.js" type="text/javascript"></script>
<script type="text/javascript" src="http://reddit.com/button.js?t=2"></script>
</div>
<?=formatText(ereg_replace("\n.*","",$projinfo["reqmts"]))?>
</span>
<div id="actions"><?
if( $username) {
    list($rc,$watches) = al_getwatches( $username, "$id-news");
    if( !$rc) {
?><a title='<?=sizeof($watches)?"Click to stop watching this project.":"Click to start watching this project."?>' class="first-child" href="watchproject.php?id=<?=$id?>&tab=<?=urlencode($_REQUEST["tab"])?><?=sizeof($watches)?"&stop=1":""?>"><?=sizeof($watches)?"You are watching this project.":"watch"?></a><?
    }
    
    $voted_for_project = false;
    if( $username )
        list( ,$voted_for_project ) = ff_hasvoted( $username, $id );
?><a title='<?=$voted_for_project?"Click to retract your vote.":"Click to vote for this project."?>' href="setvote.php?type=project&id=<?=$id?>&tab=<?=urlencode($_REQUEST["tab"])?><?=($voted_for_project)?"&stop=1":""?>"><?=$voted_for_project?"You have voted for this project.":"vote"?></a><?
}
?></div>

<?

if (strtolower($projinfo['status'])!='pending') {
?><div id=status class=<?=$projinfo['status']?>><h1>Status: </h1><?
    list($rc,$relcodeinfo) = ff_getrelcodeinfo($id,$projinfo['status']);
    if( !$rc) {
?><em><a href='project.php?p=<?=$id?>&tab=submissions#submission<?=$relcodeinfo["submissionid"]?>'><?
    }

    if( $projinfo['status'] == 'submitted') {
        print "[PENDING CODE EVALUATION]";
    } else if( $projinfo['status'] == 'accept') {
        print "[CODE ACCEPTED, COOL OFF PERIOD]";
    } else if( $projinfo['status'] == 'complete') {
        print "[COMPLETE]";
    }

    if( !$rc) {
?></a></em><?
    }

?><p><?

    if( $projinfo['status'] == 'submitted') {
?><?=htmlentities($relcodeinfo["username"])?>'s submission is under review by the project lead.<?
        if( $relcodeinfo['numothersubmissions'] == 1) {
?>There is one other submission in the queue.<?
        } else if( $relcodeinfo["numothersubmissions"] > 1) {
?>There are <?=$relcodeinfo["numothersubmissions"]?> other submissions in the queue.<?
        }
    } else if( $projinfo['status'] == 'accept') {
?>Payment will occur on <?=date("D, M j, Y @ H:i T",$projinfo['payout_time'])?>.
Now is your last chance to evaluate the code and raise any questions or concerns.<?
    } else if( $projinfo['status'] == 'complete') {
        list($rc,$successfulsubmitter) = ff_getsuccessfulsubmitter($id);
?>The final payment was made to <?=htmlentities($successfulsubmitter['username'])?> on <?=date('D, M j, Y',$successfulsubmitter['payout_time'])?>.  This project is complete; no new sponsorships or submissions will be accepted.  You can continue to use the forums for discussion purposes.<?
    }

?></p></div><?
}
?>
<? if( $projinfo["status"] !== 'complete') { ?>
<script src="folder.js"></script>
<img class=arrow id="sponsor-arrow" src="orange-arrow.gif" onClick="folder('sponsor','orange')">&nbsp;<a class="folder clean" id="sponsor-project" href="javascript:folder('sponsor','orange')">Sponsor This Project</a>
<div id="sponsor-div" class=folded>
<?     if( $username !== '') { ?>
<?         include("sponsor.php"); ?>
<?     } else { ?>
    <p>
    You currently need to <a href="login.php?url=<?=urlencode("project.php?p=$id")?>">log in</a> to
    sponsor a project.  We apologize for the inconvenience.
    </p>
<?     } ?>
</div>
<? } ?>
<? if( $username !== '' && ereg("[1-9]",$donations[$id]["amount"])) { ?>
<img class=arrow id="retract-arrow" src="orange-arrow.gif" onClick="folder('retract','orange')">&nbsp;<a class="folder clean" id="retract-project" href="javascript:folder('retract','orange')">Retract or Reduce Your Sponsorship</a>
<div id="retract-div" class=folded>
<?     include("retract.php"); ?>
</div>
<? } ?>
</div>
<a name="tabs"></a>
<?
include_once("tabs.php");

$tabs = array(
    "requirements" => "Requirements",
    "subprojects" => "Bugs/Subprojects",
    "sponsors" => "Sponsors",
    "news" => "Activity",
    "submissions" => "Submissions",
    "start" => "Get Started",
    );
list($rc,$disputes) = ff_getprojectdisputes($id);
if (!$rc && sizeof($disputes)) $tabs["disputes"] = "Disputes";

tab_header( $tabs, "project.php?p=$id", $tab, "requirements");

include_once("forum.php");
if( $tab =='requirements') {
    print '<div style="font-size:small;color:#9c9d9d;text-align:center;padding-bottom:0.2em;font-style:italic">';
    print "Note: You can propose changes ".
        "using the forum below.</div>\n";
    print '<div class="spec">';
    //check if post has attachments
    if ($projinfo['numattachments']> 0) 
        list($rc,$body) = ff_attachtoproject($projinfo['id'],formatText($projinfo["reqmts"]));
    else
        $body = formatText($projinfo["reqmts"]);    
    print $body;
    print '</div>';

    if ($projinfo['numattachments']>0) {
        print "<br>\n";
        print "<b>attachments:</b><br>\n";
        list($rc,$err) = ff_listprojectattachments($projinfo['id']);
    }

    //display the list of history changes
    list($rc,$history) = ff_getreqmtshistory($id);
    if( !$rc && sizeof($history) > 0) {
        print "<br><b>Change History:</b><br>\n";
        print "<ul>\n";
        $historysize = sizeof($history);
        for ($i=$historysize-1;$i>=0; $i--) {
            list($rc,$historyarray) = ff_getpostinfo($history[$i]['postid']);
            $historyarray['ancestors'][] = $history[$i]['postid'];
            $ancestry = implode("/",$historyarray['ancestors']);
            if (!$history[$i]['subject']) $history[$i]['subject']='No subject';
            print "<li>";
            print "<span class=postdate>";
            print date("Y-m-d H:i T", $history[$i]["time"]);
            print "</span> ";
            if( $history[$i]["action"] == 'accept') {
                print "<span class=accepted>[ACCEPTED]</span> ";
            } else if( $history[$i]["action"] == 'reject') {
                print "<span class=rejected>[REJECTED]</span> ";
            }
            print "<a href='project.php?p=$id&post=".$history[$i]['postid'].
                "' onClick='return fold3(\"reqmts$id\",\"".$ancestry."\")'>";
            print htmlentities($history[$i]['subject'])."</a>";
            print "</li>\n";
        } 
        print "</ul>\n";
    }

    show_forum("reqmts$id",$projinfo['name'], 0);
} else if ($tab =='disputes') {
    include_once("disputelist.php");
    show_dispute($id);
} else if ($tab=='subprojects') {
    include_once('allot.php');
} else if ($tab=='sponsors') {
	include_once('sponsors.php');
} else if ($tab=='submissions') {
	include_once('submissions.php');
} else if ($tab=='start') {
	include_once('getstarted.php');
} else if( $tab=='news') {
    print '<div style="float: right;"><a href="rss.php?src=projectevents&p='.$id.'" title="Project news feed"><img src="images/rss.png" style="border: 0;" alt="RSS" /></a></div>';
    list($rc,$events) = al_getrecentevents( "watch:$id-news");
    if( !$rc && sizeof($events) > 0) {
        print "<b>Recent Activity:</b><br><nobr>\n";
        foreach( $events as $event) {
            print date("Y-m-d H:i:s T",$event["time"]);
            print "&nbsp;&nbsp;<a href=\"".htmlentities($event["url"])."\">";
            print htmlentities($event["subject"]);
            print "</a><br>\n";
        }
        print "</nobr><br>";
    } else {
        print "<br><b>There has been no recent activity on this project.</b>";
        print "<br><br>";
    }
}

tab_footer();
?>
