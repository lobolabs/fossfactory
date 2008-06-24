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
function show_dispute($id) { 
    list($rc,$projinfo) = ff_getprojectinfo($id);

    print "<div id='disputebody'>\n";
    print "<script>\n";
    print "function showdispute() { document.getElementById('disputebody').innerHTML='';";
    print "document.getElementById('disputebody').innerHTML='hello';";
    print "}\n";
    print "</script>";

    list($rc,$disputes) = ff_getprojectdisputes($id);

    print "<table border=0 cellpadding=0 cellspacing=0 width=100%>\n";
        print "<tr><td bgcolor=#e51f1f><b><font color='white'>&nbsp;subject</font></b></td>";
        print "<td align=left bgcolor=#e51f1f><b><font color='white'>status</font></td></tr>";
    foreach ($disputes as $dispute) {
    if ($dispute['status']=='plaintiff')
        $status = "awaiting response from ".$dispute['plaintiff'];
    elseif($dispute['status']=='defendant')
        $status = "awaiting response from ".$projinfo['lead'];
    else 
        $status =$dispute['status'];

        print "<tr>";
        print " <td><a href='dispute.php?id=".$dispute['disputeid']."'>".htmlentities($dispute['subject']?$dispute['subject']:'No Subject')."</a></td><td>".($status=='decided'?"<b>decided</b> ".date("M j, H:i T",$dispute["decided"]):$status)."</td>";
        print "</tr>";
    } 
    print"</table>";
    print "</div>";
}
?>
