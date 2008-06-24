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
<script>
function treefold(id) {
    var a = document.getElementById('bc'+id);
    var i = document.getElementById('bw'+id);
    if( a.style.display == 'block') {
        a.style.display = 'none';
        i.src = 'plus.gif';
    } else {
        a.style.display = 'block';
        i.src = 'minus.gif';
    }
}
function treesel(ancestry) {
    // Set a cookie with the entire ancestry
    return true;
}
</script>
</head>
<body style="background-color:#ebebee;font-size:80%;margin-left:4px">
<?
function showtree( $graph, $path='', $ancestry='') {
    $i = 0;
    foreach( $graph as $id => $node) {
        $i++;
        $anc = "$ancestry/$id";
        $open = (substr($path,0,strlen($anc)) == $anc)?1:0;
        print "<div class=".($i<sizeof($graph)?"mid":"last")."><nobr>";
        if( sizeof($node["children"])) {
            print "<img id=bw$id src='".($open?"minus.gif":"plus.gif").
                "' width=18 height=9 onclick=\"treefold('$id')\">";
        } else {
            print "<img src='nochildren.gif' width=18 height=9>";
        }
        $cur = (substr($path,-strlen($id)-1) === "/$id");
        if( $cur) print "<b";
        else print "<a href='project.php?p=$id' target='_parent' onclick=\"return treesel('$anc')\"";
        if( $node["status"] != 'pending') print " class=bg$node[status]";
        print ">";
        print htmlentities($node["name"]);
        if( $cur) print "</b>";
        else print "</a>";
        print "</nobr></div>\n";
        if( sizeof($node["children"])) {
            print "<div id=bc$node[id] class=".($i<sizeof($graph)?"next":"nonext")." style='display:".($open?'block':'none')."'>\n";
            showtree( $node["children"], $path, $anc);
            print "</div>\n";
        }
    }
}

$p = scrub($_REQUEST["p"]);
list($rc,$path) = ff_getnominalprojectpath( $p);
if( $rc) $root = $p;
else $root = ereg_replace("^/([^/]*).*$","\\1",$path);
list($rc,$graph) = ff_getprojectgraph( $root);
showtree( $graph, $path);
?>
</body>
</html>
