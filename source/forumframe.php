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
<body>
<script>
<?php
include_once("forum.php");
$postid = scrub($_REQUEST["postid"]);
$topid = scrub($_REQUEST["topid"]);
$topicid=scrub($_REQUEST['topicid']);
if( !$topid) $topid = $postid;
ob_start();
list($rc,$top) = ff_getpostinfo( $topid);

list($rc,$post) = ff_getpostinfo( $postid);
$ancestors = $post["ancestors"];
$ancestors[] = $postid;

// Make sure we have all of the children of each post in the ancestry.
// Also, populate $openids with the list of ancestors to be opened.
$openids = array();
$foundtop = 0;
$curpost = false;
foreach( $ancestors as $ancestor) {
    if( $ancestor == $topid) $foundtop = 1;
    if( !$foundtop) continue;

    if($curpost === false) $curpost =& $top;
    else $curpost =& $curpost["children"][$ancestor];

    list($rc,$curpost["children"]) = ff_getposts( false, $curpost["id"], 1);

    $openids[$ancestor] = 1;
}

show_body($topicid, $top,  $openids);
$html = ob_get_contents();
ob_end_clean();
?>
parent.document.getElementById('postbody<?=$topid?>').innerHTML='<?=jsencode($html)?>';
parent.document.getElementById('arrow<?=$topid?>').src='arrow-down.gif';
parent.unfolding = '';
o = parent.document.getElementById('arrow<?=$postid?>');
var y=0;
while (o.offsetParent)  {
    y+=o.offsetTop;
    o=o.offsetParent;
}
parent.document.body.scrollTop = y;
</script>
</body>
</html>
