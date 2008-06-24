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
<?php
if( $GLOBALS["auto_apply_footer"])
{
?>
</div> <!-- mainbody -->
</div> <!-- content-body -->

<div id=footer>
[&nbsp;&nbsp;<a href="./">Home</a>&nbsp;
| &nbsp;<a href="aboutus.php">About&nbsp;Us</a>&nbsp;
| &nbsp;<a href="team.php">The&nbsp;Team</a>&nbsp;
| &nbsp;<a href="advisors.php">advisors</a>&nbsp;
| &nbsp;<a href="contact.php">Contact&nbsp;Us</a>&nbsp;
| &nbsp;<a href="press.php">Press&nbsp;Releases</a>&nbsp;
| &nbsp;<a href="overview.php">Overview</a>&nbsp;
| &nbsp;<a href="faq.php">Faq</a>&nbsp;
| &nbsp;<a href="feedback.php">Feedback</a>&nbsp;
| &nbsp;<a href="terms.php">Terms&nbsp;Of&nbsp;Use</a>&nbsp;
| &nbsp;<a href="charities.php">charities</a>&nbsp;
| &nbsp;<a href="privacy.php">Privacy&nbsp;Policy</a>&nbsp;&nbsp;]</div>
<div id=copyright>
&copy; Copyright 2006-2008 FOSS Factory Inc.  All trademarks and copyrights on this page are owned by their respective owners.  Comments are owned by the individual poster.
<!--Creative Commons License-->All content owned by FOSS Factory is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc/2.5/ca/">Creative Commons Attribution-Noncommercial 2.5 Canada License</a>.<!--/Creative Commons License--><!-- <rdf:RDF xmlns="http://web.resource.org/cc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#">
    <Work rdf:about="">
        <license rdf:resource="http://creativecommons.org/licenses/by-nc/2.5/ca/" />
    <dc:type rdf:resource="http://purl.org/dc/dcmitype/InteractiveResource" />
    </Work>
    <License rdf:about="http://creativecommons.org/licenses/by-nc/2.5/ca/"><permits rdf:resource="http://web.resource.org/cc/Reproduction"/><permits rdf:resource="http://web.resource.org/cc/Distribution"/><requires rdf:resource="http://web.resource.org/cc/Notice"/><requires rdf:resource="http://web.resource.org/cc/Attribution"/><prohibits rdf:resource="http://web.resource.org/cc/CommercialUse"/><permits rdf:resource="http://web.resource.org/cc/DerivativeWorks"/></License></rdf:RDF> -->
</div>

<a href="get-source.php" style="float:right;font-size:small;color:#808080;margin-right:0.8em">FOSS Factory Source</a>
<? if( $auth == 'admin') { ?>
<a href="admin.php" style="float:right;font-size:small;color:#808080;margin-right:0.8em">admin</a>
<? } ?>
<? if( $auth == 'admin' || $auth == 'arbiter') { ?>
<a href="arbitration.php" style="float:right;font-size:small;color:#808080;margin-right:0.8em">disputes</a>
<? } ?>
</body>
</html>
<?php
}
?>
