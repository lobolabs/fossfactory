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
$id = scrub($_REQUEST['id']);
if ($GLOBALS['username']=='') {
    print "sorry, must login first";
    softexit();
}

include_once('formattext.php');

// Get the project info
list($rc,$projinfo) = ff_getprojectinfo( $id);
if( $rc == 2) {
    print "No such project: $id";
    softexit();
}
$iserror = false;
$filenames='';
if(isset($_REQUEST['submit'])) {
    foreach ($_FILES["thefile"]["error"] as $key => $error) {
        if (is_uploaded_file($_FILES['thefile']['tmp_name'][$key]) && $_FILES['thefile']['size'][$key]!=0) {
            $files[] = array('pathname' => $_FILES['thefile']['tmp_name'][$key],
                       'filename' => $_FILES['thefile']['name'][$key],
                       'description' => $_REQUEST['description'][$key]
                       );
            $ferror[] = '';
        }
        elseif ($_FILES['thefile']['name'][$key] =='') {
            $ferror[] = '';
        }else{ 
            $iserror = true;
            $ferror[] = $_FILES['thefile']['error'][$key];
       }
    }
      
    if (!$iserror) {
        //we only process the submission if no errors at all were encountered
        list($rc,$subid) = ff_submitcode( $GLOBALS['username'],
            $files, $_REQUEST['comments'], $id);
        if(!$rc) {
            header("Location: subsuccess.php?id=$id");
            exit;
        }
        $msg = "There was a problem receiving your file: $rc $subid";
    } else {
        $msg = "There was a problem in submitting your file(s)";
    }
}

apply_template($projinfo["name"],array(
    array("name"=>"Projects", "href"=>"browse.php"),
    array("name"=>$projinfo["name"], "href"=>"project.php?p=$id"),
    array("name"=>"submit code","href"=>"submission.php?id=$id")
));

?>
<h1>Make a Submission</h1>
<? if( $msg) { ?>
<div class=error><?=htmlentities($msg)?></div>
<? } ?>
<script>
formats = new Array();
<? 
$i=0;
foreach ($ACCEPTABLE_FORMATS as $key => $format) {
    print "formats[$i] ='$key';\n";
    $formats[$i] = $key;
    $i++;
}
?>
function checkFileType() {
    correct = new Array();
    html='';
    n=0;
    theform = document.forms[0];
    for (i=0;i<theform.length;i++) {
        if (theform.elements[i].name=='thefile[]' && theform.elements[i].value!='') {  
            correct[i]=false;
            for (j=0;j<formats.length;j++) {
                if (theform.elements[i].value.substr(theform.elements[i].value.length-formats[j].length-1)=='.'+formats[j]) {
                    debug+=theform.elements[i].value.substr(theform.elements[i].value.length-formats[j].length-1)+"=="+'.'+formats[j]+"\n"; 
                    correct[i]= true;
                } 
            }
        }
    }

    for (i=0;i<theform.length;i++) {
        if (theform.elements[i].name=='thefile[]' && theform.elements[i].value!='') {  
            if (correct[i]==false) html+='error: '+theform.elements[i].value+'\nwrong file format';
        }
    }
    if (html!='') {
        alert(html);
        return false;
    } else {
        return true;
    }
}
</script>
<form action="submission.php" enctype="multipart/form-data" method="post" onSubmit='return checkFileType()'>
<input type=hidden name=id value=<?=$id?>>
<input type="hidden" name="MAX_FILE_SIZE" value="10000000000">
<p>
Use this form to submit your solution to project
<?=htmlentities($projinfo["name"])?>.  Do this only when you are sure
that you have completely satisfied all of the project requirements:
</p>
<? include_once("formattext.php"); ?>
<h2>Project Requirements:</h2>
<div class="spec">
<?=formatText($projinfo["reqmts"])?>
</div>

<h2>Submission File:</h2>
<div><input type="file" name="thefile[]" value="" size=40></div>
<h2>Comments:</h2>
<div><textarea name=comments rows=5 style="width:100%"><?=$_REQUEST['comments']?></textarea></div>
<br><br>

<div class=legal>
All submitted files must include proper copyright
notices and must be licensed under one or more of the free/open source
licenses listed in our <a href="terms.php">terms of use</a>.  <b>You may not
submit a copy or derivative work of any copyrighted work that was first 
published less than three days ago, unless you are the copyright owner of
that work, or unless you have special written permission from the copyright
owner.</b>  Submissions
that do not comply with these terms are not eligible for acceptance.
Submitting files here constitutes public distribution.  Do not submit
any file here that you do not have the legal right or permission to
distribute publicly.
<br><br>
<center>
<input id=iagree type=checkbox onClick="document.getElementById('submit').disabled=(this.checked?0:1)"> <label for=iagree>I understand and agree to these terms</label>
</center>
</div>


<p>
<input type='submit' id=submit name='submit' value='submit' disabled>
</p>

</form>
