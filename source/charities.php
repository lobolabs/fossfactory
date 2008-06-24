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
<?php apply_template("Charities and Non-Profits",array(array("name"=>"Charities and Non-Profits", "href"=>"charities.php")),'',false,true); ?>
<h1>Charities and Non-Profits</h1>
<?
list($rc,$charities) = ff_getcharities();
if( $rc) {
    print "<div class=error>System error.  Please try again later.</div>\n";
    softexit();
}
?>
<p>
FOSS Factory collects money for the following organizations via our
<a href="overview.php#communitydeduction">community deduction</a> system.
Please use our <a href="feedback.php">feedback</a> page to recommend
other charitable groups.
</p>
<table border=0 cellpadding=10 cellspacing=0>
<tr><td valign=top width="50%">
<?
$halfway = intval((sizeof($charities)+1)/2);
$row = 0;
foreach( $charities as $charity) {
    if($row == $halfway) {
        print "</td><td valign=top width='50%'>\n";
    }
    $row++;
?>
<h2><?=htmlentities($charity["name"])?></h2>
<p>
<b>Funds collected:</b> <?=format_money($charity["total"])?>
</p>
<p>
<?=$charity["description"]?>
</p>
<p>
<a href="<?=htmlentities($charity["website"])?>"><?=htmlentities($charity["name"])?> website</a>
</p>
<?
    if( $row != sizeof($charities) && $row != $halfway) {
?>
<hr />
<?
    }
}
?>
</td></tr></table>
