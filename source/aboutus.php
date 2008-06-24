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
<?php
// A quick sanity check
if( !isset($GLOBALS["SITE_URL"])) {
    print "<b>You forgot to configure the autoprepend setting.</b>\n";
    exit;
}

apply_template("About Us",array(
   array("name"=>"About Us","href"=>""))
    ,$onload,false,true);
?>

<h1>About FOSS Factory</h1>

<p>
FOSS Factory's mission is to accelerate the advancement of free/open
source software by helping people collaborate on the design, funding, and
development of innovative software ideas.  All software solutions produced
using our system are released under free/open source licenses.  Our unique
model brings the best of innovators from both the entrepreneurial and
FOSS worlds together to solve real world problems using the mass resources
of the FOSS community.
</p>

<p>Learn more about our <a href="team.php">team</a></p>
