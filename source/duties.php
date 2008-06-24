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
$memberid = scrub($_REQUEST['u']);

header( "Content-type: text/xml");

echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";
echo "<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">\n";
echo "\t<channel>\n";
echo "\t\t<title>";
print xmlescape("[FF] $memberid's duties");
echo "</title>\n";
echo "\t\t<link>".xmlescape("$GLOBALS[SITE_URL]?requser=$memberid")."</link>\n";
echo "\t\t<description>";
print xmlescape("$memberid's FOSS Factory duties");
echo "</description>\n";
echo "\t\t<language>en-us</language>\n";

include_once("getduties.php");
include_once("formattext.php");

list($rc,$duties) = getduties( $memberid);
if( !$rc) {
    foreach($duties as $duty) {
        echo "\t\t<item>\n";
        echo "\t\t\t<title>".xmlescape($duty['subject'])."</title>\n";
        echo "\t\t\t<link>".xmlescape($duty["link"])."</link>\n";
        echo "\t\t\t<guid isPermaLink=\"false\">".
            xmlescape($duty["guid"])."</guid>\n";
        echo "\t\t\t<description>".xmlescape(formatText($duty["body"]))."</description>\n";
        echo "\t\t\t<pubDate>".gmdate('D, d M Y H:i:s T',$duty['time'])."</pubDate>\n";
        echo "\t\t</item>\n";
    }
}
echo "\t</channel>\n";
echo "</rss>\n";
?>
