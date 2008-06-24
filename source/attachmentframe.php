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
<html>
<head>
<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body style="margin:0em;background-color:#ffffff" class=attachmentframe>
<?
if(isset($_REQUEST['submitted'])) {
    if (is_uploaded_file($_FILES['attachment']['tmp_name']) && $_FILES['attachment']['size']!=0) {
        
        //make sure the temporary directory exists and has a recent timestamp
        @mkdir("$GLOBALS[DATADIR]/tempattachments");
        $destdir = "$GLOBALS[DATADIR]/tempattachments/$GLOBALS[sid]";
        if( is_file($destdir)) @unlink($destdir);// workaround for an old bug
        @mkdir($destdir);
        @touch($destdir);  // So we don't delete it too soon

        // Choose a tmp filename
        $tempnam = tempnam( $destdir, "attach");

        //keep track of the number of files attached
        $rc = @rename($_FILES['attachment']['tmp_name'],$tempnam);
        if( $rc === false) {
            print "<script>\n";
            print "alert('There was a problem processing the attachment.');";
            print "</script>\n";
        } else {
            //we add the attachment to a queue of attachments in forum.js
            print "<script>\n";
            print "parent.addattachment('".scrub($_REQUEST["uniq"]).
                "','".jsencode(basename($tempnam))."','".
                jsencode($_FILES['attachment']['name'])."',".
                intval($_FILES['attachment']['size']).");\n";
            print "</script>\n";
        }
    } else {
        print "<script>\n";
        print "alert('There was a problem processing the attachment.');";
        print "</script>\n";
    }
}
?>
<form method="post" enctype="multipart/form-data" style="margin:0em;padding:0em">
<input type=hidden name="MAX_FILE_SIZE" value ="10000000">
<input type=hidden name=uniq value="<?=scrub($_REQUEST["uniq"])?>">
<input type=hidden name=submitted value=true>
<div style="position:relative;font-size:24px;height:1em" align=right>
    <img id=attachbutton style="position:absolute;right:0;font-size:24px;height:1em;width:1em" src="paperclip.gif">
    <input id=browse type="file" name="attachment" size=1 style="filter:alpha(opacity:0);opacity:0;position:absolute;top:0;right:0;font-size:24px;height:1em;cursor:hand" onChange="this.style.visibility='hidden';document.forms[0].submit();return true" title="Add an Attachment">
    <img id=cover src="spacer.gif" style="position:absolute;right:1em;top:0;width:8em;height:1em">
</div>
</form>
</body>
</html>
