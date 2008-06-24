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
$memberid = scrub($_REQUEST['memberid']);
$projectid = scrub($_REQUEST['p']);

if( !$memberid && !$projectid) exit;

if( $projectid) {
    list($rc,$projectinfo) = ff_getprojectinfo( $projectid);
    if( $rc) exit;
}

if( $memberid) $url = "$GLOBALS[SITE_URL]";
else $url = "$GLOBALS[SITE_URL]project.php?p=$projectid";

header( "Content-type: text/xml");

echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";
echo "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
echo "\t<channel>\n";
echo "\t\t<title>";
if( $projectid) {
    print xmlescape("[FF] $projectinfo[name]");
} else {
    print xmlescape("[FF] $memberid's news");
}
echo "</title>\n";
echo "\t\t<link>".xmlescape($url)."</link>\n";
echo "\t\t<description>";
if( $projectid) {
    print xmlescape("Recent events affecting FOSS Factory project '$projectinfo[name]'");
} else {
    print xmlescape("$memberid's FOSS Factory news");
}
echo "</description>\n";
echo "\t\t<language>en-us</language>\n";
if( $memberid) {
    //get the watches pertaining to this member
    list($rc,$watches) = al_getwatches($memberid);
    if( $rc) exit;
} else {
    // Just watch one event
    $watches = array(array("eventid"=>"$projectid-news"));
}
foreach ($watches as $watch) { 
    //get the recent events watched by this member
    list($rc,$recentevents) = al_getrecentevents("watch:$watch[eventid]");
    foreach($recentevents as $recentevent) {
        echo "\t\t<item>\n";
        echo "\t\t\t<title>".xmlescape($recentevent['subject'])."</title>\n";
        echo "\t\t\t<link>".xmlescape($GLOBALS["SITE_URL"].$recentevent['url']."&memberid=$memberid")."</link>\n";
        echo "\t\t\t<description>".xmlescape($recentevent["body"])."</description>\n";
        echo "\t\t\t<pubDate>".gmdate('D, d M Y H:i:s T',$recentevent['time'])."</pubDate>\n";
        echo "\t\t</item>\n";
    }
}
echo "\t</channel>\n";
echo "</rss>\n";
?>

    
