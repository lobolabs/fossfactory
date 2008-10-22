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
function getduties( $username) {
    $username = scrub($username);

    list($rc,$duties) = ff_getduties( $username);
    if( $rc) return array($rc,$duties);

    $result = array();
    foreach($duties as $key => $duty) {
        list($rc,$projectinfo) = ff_getprojectinfo( $duty["projectid"]);
        if( $rc) return array($rc,$projectinfo);

        $deadline = $duty["deadline"];
        $tag = $deadline ? "newduty2" : "newduty";

        if( $duty["type"] == 'dispute-plaintiff') {
            list($rc,$disputeinfo) = ff_getdisputeinfo( $duty["id"]);
            if( $rc) return array($rc,$disputeinfo);

            $link = "dispute.php?id=$duty[id]&requser=$username";

            $macros = array(
                "subject" => $disputeinfo["subject"],
                "projectname" => $projectinfo["name"],
            );

            $textid = "plaintiff";
        } else if( $duty["type"] == 'dispute-defendant') {
            list($rc,$disputeinfo) = ff_getdisputeinfo( $duty["id"]);
            if( $rc) return array($rc,$disputeinfo);

            $link = "dispute.php?id=$duty[id]&requser=$username";

            $macros = array(
                "subject" => $disputeinfo["subject"],
                "username" => $disputeinfo["plaintiff"],
                "projectname" => $projectinfo["name"],
                "deadline" => date("D F j, H:i:s T",$deadline),
            );

            if( sizeof($disputeinfo["arguments"] == 1)) {
                $textid = "$tag-newdispute";
            } else {
                $textid = "$tag-dispute";
            }
        } else if( $duty["type"] == 'new-subproject') {
            list($rc,$pinfo) = ff_getprojectinfo( $duty["id"]);
            if( $rc) return array($rc,$pinfo);

            $link = projurl($duty["projectid"],
                "tab=subprojects&requser=$username");

            $macros = array(
                "projectname" => $pinfo["name"],
                "parentname" => $projectinfo["name"],
                "deadline" => date("D F j, H:i:s T",$deadline),
            );

            $textid = "$tag-newsubproject";
        } else if( $duty["type"] == 'code submission') {
            // Hide code submission duties on accepted projects
            if( $projectinfo["status"] == 'accept') continue;

            list($rc,$sinfo) = ff_getsubmissioninfo( $duty["id"]);
            if( $rc) return array($rc,$sinfo);

            $link = projurl($duty["projectid"],
                "tab=submissions&requser=$username#submission$duty[id]");

            $macros = array(
                "projectname" => $projectinfo["name"],
                "submitter" => $sinfo["username"],
                "deadline" => date("D F j, H:i:s T",$deadline),
            );

            $textid = "$tag-submission";
        } else if( $duty["type"] == 'change proposal') {
            list($rc,$postinfo) = ff_getpostinfo( $duty["id"]);
            if( $rc) return array($rc,$postinfo);

            $link = projurl($duty["projectid"],
                "requser=$username&post=$duty[id]");

            $macros = array(
                "projectname" => $projectinfo["name"],
                "submitter" => $postinfo["owner"],
                "deadline" => date("D F j, H:i:s T",$deadline),
            );

            $textid = "$tag-changeproposal";
        }

        list($rc,$subject)=ff_gettext("$textid-subject",$macros);
        if( $rc) return array($rc, $subject);

        list($rc,$body)=ff_gettext("$textid-body",$macros);
        if( $rc) return array($rc, $body);

        $duty["link"] = $link;
        $duty["subject"] = $subject;
        $duty["body"] = $body;
        $result[$key] = $duty;
    }

    return array(0,$result);
}
?>
