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
function tab_header( $tabs, $url, $tab='', $default='') {
    $sep = strpos($url,'?')?'&':'?';

    print "<ul class=tabs>\n";
    $i = -1;
    foreach( $tabs as $key => $name) {
        $i++;
        print "<li class=".($tab==$key?'curtab':'othertab').">";
        print "<a href=\"".htmlentities("$url".
            ($key==$default?'':"${sep}tab=".urlencode($key)))."#tabs\">".
            str_replace(" ","&nbsp;",htmlentities($name))."</a></li>";
    }
    print "</ul><div class=tabbox>\n";
    print "<div class=tabtop>\n";
    print "<div class=tabbottom>\n";
    print "<div class=tableft>\n";
    print "<div class=tabright>\n";
    print "<div class=tabtl>\n";
    print "<div class=tabtr>\n";
    print "<div class=tabbl>\n";
    print "<div class=tabbr>\n";
    print "<div class=tabbody>\n";
}

function tab_footer() {
    print "<div style='clear:both;height:1px;width:0px'></div>";
    print "</div></div></div></div></div></div></div></div></div></div>\n";
}
?>
