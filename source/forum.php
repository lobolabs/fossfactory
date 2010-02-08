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
function show_forum( $topicid,$subject, $open=1, $openids=false)
{
    static $setup = 0;
    if( !$setup) {
        print "<script src='attachments.js'></script>\n";
        print "<script src='forum.js'></script>\n";
        $setup = 1;
    }

    list($rc,$posts) = ff_getposts( $topicid, false, 2);
    if( $rc) {
        print "Forum Error: $posts";
        return;
    }

    if( substr($topicid,0,6) == 'reqmts') {
        list($rc,$proj) = ff_getprojectinfo( substr($topicid,6));
        if( $rc) {
            print "Forum Error: $rc $proj";
            return;
        }
        print "<script>\n";
        print "${topicid}_reqmts_seq = $proj[reqmts_seq];\n";
        print "${topicid}_reqmts = '".jsencode($proj["reqmts"])."';\n";
        print "</script>\n";
    }

    //'post a comment' link  must disappear when the post form is displayed
    print "<div id=${topicid}_commentdiv>\n";
    button("Post a Comment", "javascript:inlinepost('$topicid','','${topicid}_fieldcomment','','$GLOBALS[username]')");
    if( sizeof($posts) == 0) {
        print "<p>There are currently no comments in this forum.</p>\n";
        print "</div>\n";
        print "<div id=${topicid}_fieldcomment></div>\n";
        return;
    }
    print "</div>\n";

    print "<div id=${topicid}_fieldcomment></div>\n";

    show_thread( $topicid,$posts,  $open, $openids);
}

function show_thread($topicid, $posts, $open=0, $openids=false) {
    foreach( $posts as $post) {
        show_post($topicid, $post,  $open, $openids);
    }
}

function show_post($topicid, $post,  $open, $openids=false) {
    if( $openids[$post["id"]]) $open = 1;
?>
<div class="postheader">
<img id=arrow<?=$post["id"]?> src="arrow-<?=$open?'down':'right'?>.gif" width=20 height=20 style="width:1.3em;height:1.3em" onClick="fold('<?=$topicid?>',<?=$post["id"]?>)">
<? if( $post["status"]) { ?>
<a href="./" onClick="return fold('<?=$topicid?>',<?=$post["id"]?>)" class="<?=$post["status"]?>">[<?=strtoupper($post["status"])?>]</a>
<? } ?>
<a href="./" onClick="return fold('<?=$topicid?>',<?=$post["id"]?>)" name=p<?=$post["id"]?>><?=trim($post["subject"])?htmlentities($post["subject"]):"No subject"?></a> <i>by <?
if( $post["owner"]) {
    print "<a href=\"member.php?id=$post[owner]\">$post[owner]</a>";
} else {
    $name = $post["ownername"];
    if( $name==='') $name = "Anonymous";
    print htmlentities($name);
    if( $name!=='Anonymous') print " (unregistered)";
}
?></i>
<nobr>on <b><?=date("D, M j, Y @ H:i T",$post["time"])?></b></nobr>
<?if($post["descendants"]) print "($post[descendants] repl".($post["descendants"]==1?"y":"ies").")";?>
</div>
<div id=postbody<?=$post["id"]?> class="postbody"><?if($open)show_body($topicid,$post,  $openids)?></div>
<?
}

function show_body($topicid, $post,  $openids=false) {
    $body = $post["body"];
    $diff = '';

	
    if(ereg("^(.*)\n/-/-/-/-/-begin-diff-/-/-/-/-/\n(.*)$", $body, $args)) {
        $body = $args[1];
        $diff = $args[2];
    }

    $body = linkify(str_replace("\n","<br />\n",trim(htmlentities($body))));

    //check if post has attachments
    if (sizeof($post['attachments'])> 0) {
        list($rc,$body) = ff_attachtobody($post['id'],$body);
	}
	print $body;

    if( $diff) {
        include_once("diff.php");
        print "<hr>\n";
        print formatDiff( $diff);
    }

	if (sizeof($post['attachments'])>0) {
    	print "<br>\n";
		print "<b>attachments:</b><br>\n";
        list($rc,$err) = ff_listattachments($post['id']);
	}
		
    $subject = $post["subject"];
    $subject = ereg_replace("^[rR][eE]:? *","",$subject);
    $subject = "Re: $subject";
                
	print "<br>\n";

    print "<div class=postfooter>";
    print "[ <a href=\"javascript:inlinepost('$topicid','$post[id]','${topicid}_field$post[id]','".htmlentities(jsencode("Re: ".ereg_replace("^[rR][eE]:? *","",$post["subject"])))."','$GLOBALS[username]')\">Reply to This</a> ]";

    // If the post status is pending and the lead is viewing,
    // offer the Accept / Reject options.
    if ( $post['status'] =='pending') {
        $projectid = substr($post['topicid'],6);
        list($rc,$projinfo) = ff_getprojectinfo($projectid);
        if( $GLOBALS['username']==$projinfo['lead'] && $GLOBALS['username']!=="") {
            // If a requirements change dispute is deliberating then
            // the lead can't accept this proposal.
            list($rc,$disputes) = ff_getprojectdisputes($projectid);
            if( $rc) $disputes = array();
            $canaccept = 1;
            foreach( $disputes as $dispute) {
                if( $dispute["type"]=='badchange' &&
                    $dispute["status"]=='deliberating') {
                    $canaccept = 0;
                    break;
                }
            }

            print "[ <a href=\"handlechange.php?project=$projectid&accept=1&post=$post[id]\"".($canaccept?"":" onClick=\"alert('You can\\'t accept requirements changes while a\\nchange dispute is in deliberation.  Please try again later.');return false\"")."> Accept </a> ]";
            print "[ <a href=\"handlechange.php?project=$projectid&accept=0&post=$post[id]\"> Reject </a> ]";
        }
    }
    print "</div>";

    print "<div id=${topicid}_field$post[id]></div>"; 
    print "<div id=${topicid}_attachment$post[id] align='right'></div>";

    if( sizeof($post["children"])) {
        print "<div class=postindent>";
        show_thread($topicid, $post["children"],  0, $openids);
        print "</div>\n";
    }
}
?>
