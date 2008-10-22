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
$project = scrub( $_REQUEST["project"]);
$post = scrub( $_REQUEST["post"]);
$accept = intval( $_REQUEST["accept"]);

function error($rc,$err) {
    header( "Location: ".
        projurl($GLOBALS["project"],"err=".urlencode("$rc $err")));
    exit;
}

// Get the post info
list($rc,$postinfo) = ff_getpostinfo( $post);
if( $rc) error($rc,$postinfo);

$subject = $postinfo["subject"];

// Get the project info
list($rc,$projinfo) = ff_getprojectinfo( $project);
if( $rc) error($rc,$projinfo);

if( $accept) {
    // Get the old requirements
    $before = $projinfo["reqmts"];
    $oldseq = $projinfo["reqmts_seq"];

    // Get the diff
    $body = $postinfo["body"];
    if( !ereg( "\n/-/-/-/-/-begin-diff-/-/-/-/-/\n(.*)$", $body, $args))
        return array(4,"The given post doesn't contain a diff: $post");
    $diff = $args[1];

    // Apply the diff
    include_once("diff.php");
    list($rc,$after) = patchText( $before, $diff);
    if( $rc) error($rc,$after);

	//we make the file names linkable
    if (sizeof($attachments)> 0) {
        list($rc,$after) = ff_attachtobody($project,$after);
    }

    list($rc,$err) = ff_setprojectreqmts( $username,
        $project, $oldseq, $after, $subject, $post,$postinfo['attachments']);
    if( $rc) error($rc,$err);
} else {
    list($rc,$err) = ff_rejectreqmtschange(
        $username, $project, $subject, $post);
    if( $rc) error($rc,$err);
}

header( "Location: ".projurl($GLOBALS["project"]));
?>
