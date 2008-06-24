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
if( $username === '') {
    $msg = "To create a new project, you need to be logged in.";
    include("login.php");
    softexit();
}

$draftid = scrub($_REQUEST["draft"]);
if( isset( $_REQUEST["name"])) {
    $name = "$_REQUEST[name]";
    $reqmts = "$_REQUEST[reqmts]";
    // Assume English numbers: 1,000,000.01 OR 1 000 000.01 -> 1000000.01
    $fundgoal = (float) str_replace( array( ' ', ',' ), '', $_REQUEST['fundgoal'] );
} else if( $draftid) {
    list($rc,$drafts) = ff_getprojectdrafts( $username);
    if( $rc) exit;
    $name = $drafts[$draftid]["name"];
    $reqmts = $drafts[$draftid]["reqmts"];
    $fundgoal = (float) $drafts[$draftid]["funding_goal"] / 100.0;
}
$err = intval($_REQUEST["err"]);

list($rc,$memberinfo) = ff_getmemberinfo($username);
if( $rc) exit;

list($rc,$currencies) = ff_currencies();
$currency = $currencies[$GLOBALS["pref_currency"]];

if( !$err && isset($_REQUEST["name"])) {
    $name = trim($name);
    $reqmts = trim($reqmts);
    $fundgoal2 = (int)( $fundgoal * 100 );

    if( $_REQUEST["newproject_action"]==='savedraft') {
        list($rc,$err) = ff_saveprojectdraft( $username,
            $name, $fundgoal2, $reqmts, $draftid==='' ? false : $draftid);
        if( $rc) {
            header( "Location: newproject.php?draft=$draftid&name=".
                urlencode($name)."&reqmts=".urlencode($reqmts)."&err=1");
            exit;
        }
        $draftid = $err;

        header( "Location: account.php?tab=drafts");
        exit;
    }

    list($rc,$id) = ff_createproject($username, $name, $reqmts, '', false, empty ($draftid) ? false : $draftid);
    if( !$rc) {
        ff_setfundinggoal( $username, $id, $fundgoal2.$GLOBALS['pref_currency'] );
        header( "Location: project.php?p=$id");
        exit;
    }

    header( "Location: newproject.php?draft=$draftid&name=".
        urlencode($name)."&reqmts=".urlencode($reqmts).
        "&fundgoal=".urlencode($fundgoal)."&err=1");
    exit;
}

apply_template("New Project", array(
    array("name"=>"New Project", "href"=>"newproject.php")
));

if( $err) {
    print "<div class=error>".htmlentities($err)."</div>\n";
}

?>
<h1>Create a New Project</h1>
<script>
function newProjectSubmitCheck() {
    f = document.form;
    if (!f.elements['name'].value.match(/[^ \n\r\t]/) ||
        !f.elements['reqmts'].value.match(/[^ \n\r\t]/)) {
        alert('Please fill out all the fields before submitting.');
        return false;
    }

    return confirm('Are you sure you want to create the new project?  Please read everything over carefully before submitting.');
}
function newProjectContinueCheck() {
    f = document.form;
    if (!f.elements['name'].value.match(/[^ \n\r\t]/) ||
        !f.elements['reqmts'].value.match(/[^ \n\r\t]/)) {
        alert('Please fill out all the fields before continuing.');
        return false;
    }
    return true;
}
function newProjectSaveDraftCheck() {
    f = document.form;
    if (!f.elements['name'].value.match(/[^ \n\r\t]/)) {
        alert('Please provide a project name.');
        return false;
    }
    return true;
}
</script>
<form method="post" name='form' id='newproject_form' style="clear:left">
<input type=hidden name=newproject_action id=newproject_action value="">
<input type=hidden name=draft value="<?=$draftid?>">
<table border=0 cellpadding=0 cellspacing=0 width="100%"><tr>
<td valign="top" colspan="2" width="100%">
<p>
By entering your project idea here, you are authorizing the FOSS Factory
community to start working on the project.  Anybody will be able to
contribute sponsorship funds to your project.  Development will be done in
the open, and the end result will be free/open source software.
</p>
</td><td valign="top" rowspan="3" width="0%">
<div class=important>
<b>Important:</b> Make sure to specify all of your practical requirements,
but at the same time, keep it brief.  The basic idea is to give the
developers as much creative latitude as possible, while still ensuring
that the final product will meet your needs.  The best requirements documents
are only a few paragraphs long.
</div>
</td></tr><tr><td valign="bottom" width="50%">
<h2>Project Name</h2>
<input name=name value="<?=htmlentities($name)?>" style="width:22em">
</td><td valign="bottom" width="50%">
<h2>Initial Project Funding Goal</h2>
<?php echo $GLOBALS['pref_currency']; ?> <input name="fundgoal" value="<?=htmlentities(number_format($fundgoal, 2))?>" size="7" />
</td></tr><tr><td valign="bottom" colspan="2" width="100%">
<br>
<h2>Project Requirements</h2>
<div style="font-size:small">
(Note: Your first paragraph will also be used as the project abstract.  Use blank lines to separate paragraphs.)
</div>
</td>
</tr><tr>
<td valign="top" colspan="2" width="100%">
<textarea name=reqmts style="width:100%" rows=16><?=htmlentities($reqmts)?></textarea>
</td><td valign=center width="0%">
<div class=help>
<b>Can the requirements be changed later?</b>
Once the project is created, anyone can propose requirements changes.  The project lead will be responsible for accepting or rejecting change proposals.
</div><br><br><br>
</td>
</tr><tr><td width="0%" colspan="2" align="right">
<br>
<a href="" onClick="document.getElementById('newproject_action').value='savedraft';if(newProjectSaveDraftCheck())document.getElementById('newproject_form').submit();return false" class=normal-button>Save Draft</a>
<a href="" onClick="document.getElementById('newproject_action').value='create';if(newProjectSubmitCheck())document.getElementById('newproject_form').submit();return false" class=normal-button>Create Project</a>
</td><td>&nbsp;</td>
</tr></table>
</form>
