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
$tab = scrub($_REQUEST["tab"]);
if( !$tab) $tab = 'general';

apply_template("Feedback",array(
    array("name"=>"Feedback","href"=>"feedback.php")));
?>
<h1>FOSS Factory Feedback</h1>
<p>
Please use these forums to discuss anything about the site.
You can also <a href="contact.php">contact us</a> by email or snail mail.
</p>
<?
include_once("tabs.php");

$tabs = array(
    "general" => "General",
    "bugs" => "Bugs",
    "features" => "Feature Requests",
);

tab_header( $tabs, "feedback.php", $tab, "general");

include_once("forum.php");

$forum = "feedback";
if( $tab != "general") $forum .= "$tab";

show_forum($forum,$tabs[$tab]);

tab_footer();
?>
