<? /*
Copyright 2008 John-Paul Gignac
Copyright 2008 FOSS Factory Inc.

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
$topicid = scrub($_REQUEST["topicid"]);
$parent = scrub($_REQUEST["parent"]);

 
//check for attachments
$tempdir = "$GLOBALS[DATADIR]/tempattachments/$sid";
$attachments = array();
foreach( $_REQUEST as $key => $filename) {
    if( !ereg("^attachment_filename_([a-zA-Z0-9]+)$", $key, $parts)) continue;
    $basename = $parts[1];
    $attachments[] = array(
        'filename' => $filename,
        'pathname' => "$tempdir/$basename",
        'description' => ''
    );
}

if (substr($topicid,0,6)=='reqmts') { 
    $id =substr($topicid,6);
    if( $username !== '' && $_REQUEST["watchproject"])
        al_createwatch("$id-news",$username);
    while( isset( $_REQUEST["subject"])) {
        $body = "$_REQUEST[body]";

        if( $_REQUEST["revision"]) {
            include_once("diff.php");
            list($rc,$diff) = diffText( $_REQUEST["before"],
                $_REQUEST["after"], intval($_REQUEST["reqversion"]));
            if( $rc) break;

            if( $diff !== '') {
                $body .= "\n/-/-/-/-/-begin-diff-/-/-/-/-/\n$diff";
            }
        }

        list($rc,$postid) = ff_createpost( "$topicid",
            "$_REQUEST[subject]", $body, $parent,
            $_REQUEST["anonymous"]?'':$username,'',$attachments,
            $_REQUEST["watchthread"]?1:0, projurl($id));
        header("Location: ".projurl($id,
            "post=$postid".($parent?"#p$parent":"")));
        exit;
    }
}
elseif(substr($topicid,0,5)=='spect') {
    $disputeid=scrub($_REQUEST['disputeid']);
    $id = substr($topicid,5);
    if( isset( $_REQUEST["subject"])) {
        list($rc,$postid) = ff_createpost( "$topicid",
            "$_REQUEST[subject]", "$_REQUEST[body]", $parent,
            $_REQUEST["anonymous"]?'':$username,'',$attachments,
            $_REQUEST["watchthread"]?1:0, "dispute.php?id=$disputeid");
        header("Location: dispute.php?id=$disputeid&post=$postid".
            ($parent?"#p$parent":""));
        exit;
    }
} 
elseif(substr($topicid,0,4)=='proj') {
    $id = substr($topicid,4);
    if( $username !== '' && $_REQUEST["watchproject"])
        al_createwatch('$id-news',$username);
    if( isset( $_REQUEST["subject"])) {
        list($rc,$postid) = ff_createpost( "$topicid",
            "$_REQUEST[subject]", "$_REQUEST[body]", $parent,
            $_REQUEST["anonymous"]?'':$username,'',$attachments,
            $_REQUEST["watchthread"]?1:0, projurl($id));
        header("Location: ".projurl($id,
            "post=$postid".($parent?"#p$parent":"")));
        exit;
    }
}
elseif(substr($topicid,0,8)=='feedback') {
    if( isset( $_REQUEST["subject"])) {
        $tab = substr($topicid,8);
        list($rc,$postid) = ff_createpost( "$topicid",
            "$_REQUEST[subject]", "$_REQUEST[body]", $parent,
            $_REQUEST["anonymous"]?'':$username,'',$attachments,
            $_REQUEST["watchthread"]?1:0,
            "feedback.php".($tab?"?tab=$tab":""));
        header("Location: feedback.php?post=$postid".
            ($tab?"&tab=$tab":"").($parent?"#p$parent":""));
        exit;
    }
}
elseif(substr($topicid,0,4)=='subm') {
    $id = substr($topicid,4);
    if( isset( $_REQUEST["subject"])) {
        list($rc,$postid) = ff_createpost( "$topicid",
            "$_REQUEST[subject]", "$_REQUEST[body]", $parent,
            $_REQUEST["anonymous"]?'':$username,'',$attachments,
            $_REQUEST["watchthread"]?1:0, projurl($id,"tab=submissions"));
        header("Location: ".projurl($id,
            "tab=submissions&post=$postid".($parent?"#p$parent":"")));
        exit;
    }
}
?>
