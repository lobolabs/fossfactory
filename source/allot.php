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
include_once('formattext.php');
$parentid = $id;
// Get the project info
list($rc,$projinfo) = ff_getprojectinfo( $parentid);
if( $rc == 2) {
    print "No such project: $parentid";
    softexit();
}

//get subprojects of project 
list($rc,$subprojects) = ff_getsubprojects($parentid);
$totsubprojallot = 0;
foreach($subprojects as $subproject) $totsubprojallot +=$subproject['allotment']; 

function pri_cmp( $a, $b) {
    if( $GLOBALS["priorities"][$a["priority"]] <
        $GLOBALS["priorities"][$b["priority"]]) return 1;
    if( $GLOBALS["priorities"][$a["priority"]] >
        $GLOBALS["priorities"][$b["priority"]]) return -1;
    return 0;
}

function id_cmp( $a, $b) {
    if( intval(substr($a["id"],1)) < intval(substr($b["id"],1))) return -1;
    if( intval(substr($a["id"],1)) > intval(substr($b["id"],1))) return 1;
    return 0;
}

function allot_cmp( $a, $b) {
    if( $a["allotment"] < $b["allotment"]) return 1;
    if( $a["allotment"] > $b["allotment"]) return -1;
    return 0;
}

function lead_cmp( $a, $b) {
    if(strtolower($a["lead"]) < strtolower($b["lead"])) return -1;
    if(strtolower($a["lead"]) > strtolower($b["lead"])) return 1;
    return 0;
}

function bounty_cmp( $a, $b) {
    if(converted_value($a["bounty"])<converted_value($b["bounty"])) return 1;
    if(converted_value($a["bounty"])>converted_value($b["bounty"])) return -1;
    return 0;
}

function status_cmp( $a, $b) {
    if($a["status"]<$b["status"]) return -1;
    if($a["status"]>$b["status"]) return 1;
    return 0;
}

$columns = array(
    "id"=>"ID",
    "pri"=>"Priority",
    "status"=>"Status",
    "lead"=>"Project<br>Lead",
    "allot"=>"Allotment",
    "bounty"=>"Bounty",
);

if( sizeof($subprojects)) {
    // Sort the subprojects by the appropriate column
    $sort_key = $_REQUEST["sort"];
    if(!isset($columns[$sort_key])) $sort_key = 'pri';
    uasort( $subprojects, $sort_key."_cmp");

?>
<script>
function update_unallotedPercentage() { 
    i=1;
    html='';
    totalpercentage=0;
<? foreach($subprojects as $subproject) {?>


    if (!(document.form.elements['sub<?=$subproject['id']?>'].value<0 || isNaN(document.form.elements['sub<?=$subproject['id']?>'].value)))  {
        totalpercentage+=parseFloat(document.form.elements['sub<?=$subproject['id']?>'].value);
    }
<? } ?>
    totalpercentage = totalpercentage*10;
    totalpercentageObj = document.getElementById('totalpercentage');

    if (totalpercentage>1000) { 
        totalpercentageObj.style.color='#ff0000';
        totalpercentageObj.style.fontWeight='bold';         
        totalpercentageObj.style.marginBottom='1em';
        //we use this method b/c of 'floor' usage in 'money' function
        totalpercentageObj.innerHTML='-'+((totalpercentage-1000)/10)+'%';
        return true;
    } else {
        totalpercentageObj.style.color='';

        totalpercentageObj.style.color='';
        totalpercentageObj.style.fontWeight='';
        totalpercentageObj.style.marginBottom='';

        totalpercentageObj.innerHTML=((1000-totalpercentage)/10)+'%';
        return true;
    }
}

function checkAllot() {
    var total = 0;
    i=0; 
<? foreach( $subprojects as $id => $subproject) { ?>
        if(document.form.elements['sub<?=$subproject['id']?>'].value<0) {
            alert("Cannot have negative percentages");
            return false;
        } else if(isNaN(document.form.elements['sub<?=$subproject['id']?>'].value)) {
            alert("Please enter percentages in integers only");
            return false;
        }

        total = total + 10*document.form.elements['sub<?=$subproject['id']?>'].value;
        i++;
 <? } ?> 
    if (total<=1000) { 
        return true;
    } else {
        alert('Please enter a total allotment percentage at most 100%');
        return false;
    }
}
</script>
<form action="allotpost.php" name='form' method='post'>
<table width=100% border=1 cellpadding=5 cellspacing=0>
<tr bgcolor="#e0e0e0">
<?
foreach( $columns as $key => $title) {
    print "<th>";
    if( $sort_key !== $key) {
        print "<a href='".
            projurl($parentid,"tab=subprojects".
                ($key !== 'pri' ? "&sort=$key" : ""));
        print "'>$title";
        print "</a>";
    } else print $title;
    print "</th>";
}
?>
<th>Summary</th>
</tr>
<? foreach($subprojects as $subproject) { 
    $totalallotment+=$subproject['allotment']/10;
?>
    <tr<?=$subproject['status']==='complete'?" bgcolor='#c0c0c0'":""?>>
        <td align=right valign=top><?=$subproject['status']==='complete'?"<del style='color:#000'>":''?><a href="<?=projurl($subproject["id"])?>"><?=$subproject['id']?></a><?=$subproject['status']=='complete'?'</del>':''?></td>
        <td valign=top><? if ($GLOBALS['username'] !== '' && $GLOBALS['username']==$projinfo['lead']) { ?>
                <select name="pri<?=$subproject['id']?>">
                <?
                    foreach(array_keys($GLOBALS["priorities"]) as $priority) {
                        print "<option value=\"".htmlentities($priority).
                            "\"";
                        if( $priority === $subproject['priority'])
                            print " selected";
                        print ">".htmlentities($priority)."</option>\n";
                    }
                ?>
                </select>
            <? } else { ?>
                <?=$subproject['priority']?>
            <? } ?>
        </td>
        <td valign=top><?=$subproject['status']?></td>
        <td valign=top><? if($subproject["lead"] !== '') { ?><a href="member.php?id=<?=urlencode($subproject['lead'])?>"><?=htmlentities($subproject['lead'])?></a><? } else { ?>(none)<? } ?></td>
            <? if ($GLOBALS['username'] !== '' && $GLOBALS['username']==$projinfo['lead']) { ?>
                <td align=right valign=top<?=$subproject['allotted']?"":" class='allotted'"?>>
                <input type='textfield' name='sub<?=$subproject['id']?>' size='4' onChange="return update_unallotedPercentage()" maxLength='4' value='<?=($subproject['allotment'])/10?>'> %
                </td>
            <? } else { ?>
                <td align=right valign=top>
                <?=$subproject['allotment']/10?> %
                </td>
            <? } ?>
        <td align=right valign=top><?=htmlentities(convert_money($subproject['bounty']))?></td>
        <td valign=top><?=ereg("^Bug [a-z0-9]*$",$subproject["name"])?formatText(ereg_replace("\n.*","",$subproject['reqmts'])):htmlentities($subproject["name"])?></td>
    </tr>
<?  
}
?>
</tr></table>
<? if ($GLOBALS['username'] !== '' && $GLOBALS['username']==$projinfo['lead']) { ?> 
<input type='hidden' name='id' value=<?=$parentid?>>
<input type='hidden' name='tab' value='subprojects'>
<p align='right'><input type='submit' name='submit' value='Update' onClick='return checkAllot()'></p>
<? } ?>
<hr height=1></hr>
<p align=right><b>Unalloted Percentage:</b><span id='totalpercentage'><?=(100-$totalallotment)?>%</span></p>
</form>
<p>
<a href="newbug.php?p=<?=$parentid?>">Report a bug</a>
</p>
<?
} else {
?>
<br>
<b>This project has no bugs/subprojects.</b>
<br>
<br>
<a href="newbug.php?p=<?=$parentid?>">Report a bug</a><br><br>
<a href="newsubproject.php?p=<?=$parentid?>">Create a subproject</a><br><br>
<?
}
?>
