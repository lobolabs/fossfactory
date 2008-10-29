/*
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
*/


function fold(topicid,postid) {
    return fold2(topicid,postid,postid);
}

function fold2(topicid,topid,postid) {
	if( unfolding == topid) return false;
	if( unfolding != '') { // if someone clicks on another arrow while an arrow is being unfolded
		// Cancel the current unfold operation
		document.getElementById('unfolder').innerHTML = '';
		document.getElementById('arrow'+unfolding).src = 'arrow-right.gif';
		document.getElementById('postbody'+unfolding).innerHTML = '';
		unfolding = '';
	}
	var img = document.getElementById('arrow'+topid);
	var body = document.getElementById('postbody'+topid);

	var imgname = ''+img.src;
	var sentinel = imgname.substr(imgname.length-5,1);
	if( sentinel == 't') { //ie if image source = arrow-right.gif
		// The message needs to be unfolded
		unfolding = topid; //indicates that message is currently being unfolded
		img.src = 'arrow-pending.gif';
		document.getElementById('unfolder').innerHTML =
			'<iframe src="forumframe.php?'+
            'topid='+topid+'&'+
			'postid='+postid+'&topicid='+topicid+
			'" style="width:1px;height:1px;display:none"></iframe>';
	} else {
		// The message needs to be folded
		img.src = 'arrow-right.gif';
		body.innerHTML = '';
	}
	return false;
}

// Example: fold3("3/7/8/12");
function fold3(topicid,ancestry) {
    var ancestors = ancestry.split('/');
    var postid = ancestors[ancestors.length-1];
    for( i in ancestors) {
        var img = document.getElementById('arrow'+ancestors[i]);
        var imgname = ''+img.src;
        var sentinel = imgname.substr(imgname.length-5,1);
        if( sentinel == 't') {
            return fold2(topicid,ancestors[i],postid);
        }

        // If the post was already unfolded, just scroll down to it.
        if( ancestors[i] == postid) {
            var o = document.getElementById('arrow'+postid);
            var y = 0;
            while( o.offsetParent) {
                y += o.offsetTop;
                o = o.offsetParent;
            }
            document.body.scrollTop = y;
        }
    }
    return false;
}

function clearpost(divid) {
    if (confirm('Are you sure you want to cancel your post?')==true) {
        document.getElementById(divid).innerHTML = '';
    }
    return false;
}

function attach_event(tmpid,filename) {
    if (tmpid.substr(0,6) != 'reqmts' ||
        document.getElementById(tmpid+'_newreq').disabled==1)
        document.getElementById(tmpid+'_body').value+=' '+filename+' ';
    else
        document.getElementById(tmpid+'_newreq').value+=' '+filename+' ';
}

function inlinepost(topicid,parent,divid,subject,username) {
    if( document.getElementById(divid).innerHTML) return;

    var tmpid = topicid+'_'+forum_uniq;
    forum_uniq ++;

    var html = '<form method="post" action="handlepost.php" id="'+
        tmpid+'_postcomment">';
    if( topicid.substr(0,5) == 'spect') {
        html+='<input type=hidden name="disputeid" value="'+
            topicid.substr(5)+'">';
    }
    html+='<input type=hidden name="topicid" value="'+topicid+'">';
    html+='<input type=hidden name="parent" value="'+parent+'">';
    html+='<table border=0 width="90%">';
    if( username != '') {
        html+='<tr><td align="right" width="0%">Username:</td><td width="100%">'+username+'</td></tr>';
        html+='<tr><td>&nbsp;</td><td><input type=checkbox name=anonymous value=1 id='+tmpid+'_anon> <label for='+tmpid+'_anon>Post Anonymously</label></td></tr>';
    } else {
        html+='<tr><td align="right" width="0%">Username:</td><td width="100%">Anonymous</td></tr>';
    }
    html+='<tr><td align="right" width="0%">Subject:</td>';
    html+='<td width="100%"><input name=subject value="" id="'+tmpid+'_subject" style="width:100%"></td></tr>';
    html+='<tr><td align="right" valign="top" width="0%">Comment:</td><td width="100%"><textarea rows=10 name=body id='+tmpid+'_body style="width:100%"></textarea></td></tr>';
    if( eval('typeof('+topicid+'_reqmts)') != 'undefined') {
        html+='<tr><td>&nbsp;</td>';
        html+='<td><input type=checkbox name=revision value=1 id='+tmpid+'_rev onChange="document.getElementById(\''+tmpid+'_newreq\').disabled=this.checked?0:1"> <label for='+tmpid+'_rev>Propose a Change</label></td></tr>';
        html+='<tr><td align="right" valign="top" width="0%">Changes:</td>';
        html+='<td width="100%">';
        html+='<input type=hidden name=reqversion value="'+eval(topicid+'_reqmts_seq')+'">';
        html+='<input type=hidden id='+tmpid+'_oldreq name=before value="">';
        html+='<textarea rows=10 id='+tmpid+'_newreq name=after style="width:100%" disabled></textarea></td></tr>';
    }
    html+='<tr><td colspan=2 width=100% align=right><div id='+tmpid+'_filelist></div></td></tr>';
    html+='<tr><td align="right" colspan=2 width="100%">';
    html+='<div style="position:relative">';
    html+='<table width=100% cellpadding=0 cellspacing=0>';
    html+='<tr>';
    html+='<td width="100%" align=right valign=top id='+tmpid+'_atchbtn></td>';
    html+='<td width="0%" valign=top><nobr>';
    html+='&nbsp;<a href="" onClick="return clearpost(\''+divid+'\')" class="normal-button">Cancel</a>';
    html+='&nbsp;<a href="" class="normal-button" onClick="document.getElementById(\''+tmpid+'_postcomment\').submit();return false">Submit Comment</a>';
    html+='</td></tr></table></div></td></tr>';
    if( username != '') {
        html+='<tr><td>&nbsp;</td>';
        html+='<td><input type=checkbox name=watchthread id='+tmpid+'_watchthread value=1 checked>';
        html+='&nbsp;<label for='+tmpid+'_watchthread>Notify me of replies to this thread.</label></td></tr>';
        // offerwatch is a global variable
        if( typeof(offerwatch) != 'undefined') {
            html+='<tr><td>&nbsp;</td>';
            html+='<td><input type=checkbox name=watchproject id='+tmpid+'_watchproject value=1 checked>';
            html+='&nbsp;<label for='+tmpid+'_watchproject>Watch this project.</label></td></tr>';
        }
    }
    html+='</table></form>';
    if( openpost == tmpid) {
        openpost = null;
    } else {
        document.getElementById(divid).innerHTML=html;
        var a = setup_attachments(tmpid,attach_event);
        document.getElementById(tmpid+'_filelist').appendChild(a[0]);
        document.getElementById(tmpid+'_atchbtn').appendChild(a[1]);
        document.getElementById(tmpid+'_subject').value=subject;
        openpost = tmpid;
    }
    if( eval('typeof('+topicid+'_reqmts)') != 'undefined') {
        var reqmts = eval(topicid+'_reqmts');
        document.getElementById(tmpid+'_oldreq').value = reqmts;
        document.getElementById(tmpid+'_newreq').value = reqmts;
    }
}

forum_uniq = 0;

// This keeps track of which post is currently being unfolded, if any.
unfolding = '';

openpost = null;

document.write('<div id=unfolder></div>');
