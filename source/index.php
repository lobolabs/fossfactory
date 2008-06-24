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

apply_template('Welcome to FOSS Factory','','setup()',array(
    "header-style","intro-style","featured-projects","footer-style"));

?>
    <script>
     function login() {
        o=document.getElementById('login_form');
        s=document.getElementById('search_form');
        if(o.className=="closed") {
        //if sign up is not appearing, make it appear and hid search bar
            o.className="open";
            s.className="closed";
        }        
        else {
            o.className="closed";
            s.className="open";
        }
        return false;
     }
        function open_close(obj) {
            if( obj.className != 'open') obj.className = 'open';
            else obj.className = 'closed';
        }

        function select(clicked) {
            // Deselect all of the siblings
            var o = clicked;
            while( o.previousSibling) o = o.previousSibling;
            while( o) {
                if( o.tagName == clicked.tagName) o.className = '';
                o = o.nextSibling;
            }

            clicked.className="selected";
            return false;
        }

        function better() {
            document.getElementById('howitworks').className = 'closed';
            var benefits = document.getElementById('why_better');
            open_close(benefits);
            if( benefits.className == 'open') {
                curLowest = get_lastchild(benefits);
            } else {
                curLowest = benefits;
            }
            return false;
        }
        
        //check if the last node is an element node
        //ie same as n.lastchild
        function get_lastchild(n) {
            if(n.lastChild!=null) {
                var x=n.lastChild;
                while (x.nodeType!=1)  {
                 x=x.previousSibling;
                }
                return x;
            } else {
                return n;
            }
        }
        //if a tab is selected, then curLowest is last <li> element (ie the result"
        //if a tab is unselected, then curLowest = whybetter
        function details(curTab) {
            document.getElementById('why_better').className = 'closed';
            open_close( curTab.parentNode); 
            if( curTab.parentNode.className=='open') {
                curLowest = get_lastchild(get_lastchild(curTab));
                if( !curLowest)
                    curLowest = document.getElementById('why_better');
            } else {
                curLowest = document.getElementById('why_better');
            }
            return false;
        }

        function getYPos( o, whence) {
            var y = 0;
            while( o != null && o != whence) {
                y += o.offsetTop;
                o = o.offsetParent;
            }
            return y;
        }

        function select_tab(clicked) {
            select(clicked);
            select(document.getElementById(clicked.id+'_details'));
            return false;
        }

        function setup() {
            curLowest = document.getElementById('why_better');
            curHeight = 0;
        }
    </script>
<a href="http://sourceforge.net/awards/cca/?project_name=FOSS%20Factory&project_url=http%3A//www.fossfactory.org/" style="position:absolute;right:1em;width:120px;height:240px"><img src="http://sourceforge.net/awards/cca/badge_img.php?project_name=FOSS%20Factory&project_url=http%3A//www.fossfactory.org/&style=3" border="0" /></a>
<div id="maincontent" style="min-height:240px">
<!-- beginning of body-->
<div id="sidebar">
<div id="introduction">
    <h1>Introduction</h1>
    <p>FOSS Factory is the only website where the community collaborates
    on every aspect of free/open source software production, including
    design, funding and development.  Our mission is to help accelerate the
    advancement of free/open source software.</p>
</div>

    <a href="browse.php" id="browse">Browse Projects</a>

<div id="why_better" class="closed">
    <a href="" onclick="return better()">Why is FOSS Factory better?</a>
    <ul>
        <li onClick="return select(this)" class="selected">Collaboration
            <div>People collaborate in designing, sponsoring, and developing projects.  That means more money and more development effort, producing better software in less time!</div>
        </li>
        <li onClick="return select(this)">Community Control
            <div>Every aspect of the project lifecycle, including funding,
            management, development and final payment is fully in the hands
            of the community.  At present, FOSS Factory staff is sometimes
            called on to serve as dispute arbiters.  However, we are
            working to add a jury system which will fill this last gap,
            placing arbitration under community control.</div>
        </li>
        <li onClick="return select(this)">Recurring Sponsorships
            <div>Sponsors are urged to set up automatic monthly
            contributions to their favourite projects.  The promise of
            a stable funding stream, however large or small, can be a
            driving force that keeps projects moving.</div>
        </li>
        <li onClick="return select(this)">Guaranteed Results
            <div>Sponsorship funds are only paid out as requirements are met, and sponsors can retract their remaining funds at any time.  Sponsorship retraction does not hurt developers because they are paid in small increments for their work.  If the funds go away, the developers can choose to follow.</div>
        </li>
        <li onClick="return select(this)">A model that succeeds where others have failed
            <div>Most FOSS funding approaches break the open spirit of FOSS development by either locking parties into contracts or demotivating collaboration.  FOSS Factory's unique structure integrates competition with collaboration, motivating developers while maintaining everybody's freedom to walk away.</div>
        </li>
    </ul>
</div>
</div>
<div id="howitworks" class="closed">
    <h1>How it works</h1>
    <ul id="intro_tabs" class="closed">
        <li id="innovators" onclick="return select_tab(this)" class="selected"><a href="innovators">Innovators</a></li>
        <li id="designers" onclick="return select_tab(this)"><a href="designers">Designers</a></li>
        <li id="sponsors" onclick="return select_tab(this)"><a href="sponsors">Sponsors</a></li>
        <li id="developers" onclick="return select_tab(this)"><a href="developers">Developers</a></li>
        <li id="everybody" onclick="return select_tab(this)"><a href="everybody">Everybody</a></li>
    </ul>

    <div id="innovators_details" class="selected">
        <p>Post your software idea as a project now, sponsor it, and get access to a community that helps you design, fund and develop it.
        <a href="" onClick="return details(this.parentNode.parentNode)">See details</a></p>
        <ul>
            <li>
                <h2>Post your software idea as a project</h2>
                <img src="images/posted-note.png">
                Your project will be viewed by the community, who can assist in design, funding and implementation.
                <ul>
                    <li><a href="newproject.php">post project</a></li>
                    <li><a href="overview.php#projects">learn more</a></li>
                </ul>
            </li>
            <li>
                <h2>Sponsor your project</h2>
                <img src="images/bountyBIG.png">
                Attach an optional sponsorship fee to your project: Anyone who completes your project will get paid the amount.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#funding">learn more</a></li>
                </ul>
            </li>
            <li>
                <h2>Others will assist in your project's design</h2>
                <img src="images/3-guys.png">
                There are many people out there who can help in designing your project.  The community can work to develop the design without sacrificing your core requirements.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#subprojectstrategy">learn more</a></li>
                </ul>
            </li>
            <li>
                <h2>... in funding</h2>
                <img src="images/bounty.png">
                Many people would like to see your idea developed and would be happy to help sponsor your project.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#funding">learn more</a></li>
                </ul>
            </li>
            <li>
                <h2>... and in development</h2>
                <img src="images/coding.png">
                Innovative ideas attract innovative developers.  The system allows developers to collaborate in coding your project.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>
            <li class="last-child" >
                <h3>The result</h3>
                Your idea gets developed into better quality software, in a shorter time, with less cost.  Best of all, it's free for everyone to use.
            </li>
        </ul>
    </div>

    <div id="designers_details">
        <p>Browse, share and contribute to the projects that are most relevant to you.
        <a href="" onClick="return details(this.parentNode.parentNode)">See details</a></p>

        <ul>
            <li>
                <h2>Browse for fascinating software projects</h2>
                <img src="images/browseBIG.png">
                Find the project that interests you the most.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>

            <li>
                <h2>Suggest adding, modifying or deleting project features</h2>
                <img src="images/changesBIG.png">
                Your suggested changes will be viewed by the community, who will build on your suggestions.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>

            <li>
                <h2>The project lead's role</h2>
                <img src="images/approved_suggestions.png">
                If the project lead likes your suggestions, he will modify the project requirements and merge your suggestions into it.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#projectlead">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>You can also suggest breaking down projects</h2>
                <img src="images/subprojects.png">
                By breaking down projects you help to refine the requirements, and to parcel out the work.  This decreases developers' opportunity cost for each portion, and helps them to get paid for tangible results.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#subprojects">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>Approved subproject suggestions get more money</h2>
                <img src="images/percentages.png">
                The project lead will assign a percentage of the sponsorship to your suggested subprojects.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#projectlead">learn more</a></li>
                </ul>
            </li>

            <li class="last-child">
                <h3>The result</h3>
                Your design contribution will be crucial to the production process, and will set the stage for development.
            </li>
        </ul>
    </div>

    <div id="sponsors_details">
        <p>Look for a project that you would like to see completed soon and sponsor it to support its completion.  Every penny counts!
        <a href="" onClick="return details(this.parentNode.parentNode)">See details</a></p>

        <ul>
            <li>
                <h2>Browse for projects that you would like to see completed</h2>
                <img src="images/browseBIG.png">
                There are great projects out there awaiting sponsorship funds to begin development.  All of your sponsorship funds will go to your selected projects.  FOSS Factory's business model does not infringe on the funding of projects.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>

            <li>
                <h2>Add funds to your reserve</h2>
                <img src="images/deposit_money.png">
                Every member has a reserve, which is your personal bank account at FOSS Factory.  You can add funds to your reserve using Paypal and withdraw funds at any time.  Effective use of your reserve can help you avoid PayPal fees.
                <ul>
                    <li><a href="account.php?tab=reserve#tabs">add funds</a></li>
                </ul>
            </li>

            <li>
                <h2>Add sponsorship funds</h2>
                <img src="images/handshake.png">
                You may initiate sponsorships, or add funds to already existing pools created by other sponsors.  You can transfer your funds between different projects any time.
                <ul>
                    <li><a href="account.php?tab=reserve#tabs">add funds</a></li>
                    <li><a href="overview.php#funding">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>Money transfers</h2>
                <img src="images/money-transfer.png">
                You only pay for projects that have been developed according to specifications.  Once a submission is approved by the project lead, the funds wil be transferred to the successful candidate's reserve.
                <ul>
                    <li><a href="overview.php#projectcompletion">learn more</a></li>
                </ul>
            </li>

            <li  class="last-child">
                <h3>The result</h3>
                Your sponsorship contribution will be the backbone of the production process and will facilitate momentum within the project.
            </li>
        </ul>
    </div>

    <div id="developers_details">
        <p>The only FOSS funding model that respects and preserves the open,
        collaborative spirit of the FOSS community.
        <a href="" onClick="return details(this.parentNode.parentNode)">See details</a></p>

        <ul>
            <li>
                <h2>Find a project that you like and start developing</h2>
                <img src="images/browseBIG.png">
                You don't have to submit a bid, you don't have to sign any contract and you can quit anytime you want.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>

            <li>
                <h2>There is plenty of space for you to be creative</h2>
                <img src="images/lightbulb.png">
                Most FOSS Factory projects have specific goals, but broad creative leeway for design and implementation.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#projects">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>You can develop the whole project alone, or work with others</h2>
                <img src="images/collaberative_code.png">
                You could aim to collect the entire bounty yourself if you wish.  However, working with others will minimize your risk and provide the benefit of early partial payment.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#subprojectstrategy">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>Complete your code and submit</h2>
                <img src="images/submit.png">
                The way you earn money is by submitting work that satisfies the project's requirements.  The faster you submit, the more likely it is you will get paid.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#projectcompletion">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>Submission approvals</h2>
                <img src="images/submit-approved.png">
                The project lead will evaluate whether your submission meets the requirements.  The built-in dispute resolution system will protect you against unfair project leads.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php#disputes">learn more</a></li>
                </ul>
            </li>

            <li class="last-child">
                <h3>The result</h3>
                Your development contribution will earn you money and will help produce free/open source software for everyone's benefit.
            </li>
        </ul>
    </div>

    <div id="everybody_details">
        <p>Take on any role you like, or <i>every</i> role, if you prefer.  The final product is always free/open source software, so everybody wins.
        <a href="" onClick="return details(this.parentNode.parentNode)">See details</a></p>

        <ul>
            <li>
                <h2>Participate in any way you want</h2>
                <img src="images/everybody3.png">
                You can be a sponsor, a designer, a developer, a project lead, or any mix of them.  Roles are only defined by what you do, not who you are.
                <ul>
                    <li><a href="newproject.php">post project</a></li>
                    <li><a href="overview.php">learn more</a></li>
                </ul>
            </li>

            <li>
                <h2>Take advantage!</h2>
                <img src="images/give-take.png">
                Every program produced here is yours to use, study, adapt,
                improve, and share as you please.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                </ul>
            </li>

            <li>
                <h2>Your innovation sandbox</h2>
                <img src="images/stars.png">
                Use this site as a place to throw all your crazy ideas that
                you don't have time to work on yourself.  If others like one
                of your ideas, it may become reality before you know it.
                <ul>
                    <li><a href="browse.php">browse projects</a></li>
                    <li><a href="overview.php">learn more</a></li>
                </ul>
            </li>

            <li class="last-child">
                <h3>The result</h3>
                This is <i>your</i> site to help you create the software you need, want or dream of.
            </li>
        </ul>
    </div>
</div>
    </div>

<?
list($rc,$features) = ff_getfeaturedprojects();
if( !$rc && sizeof($features) > 0) {
?>
<div style="clear:both">&nbsp;</div>
<div id="featured_projects">
    <h1>Featured Projects <a href="browse.php">(Browse All)</a></h1>
    <ul>
<?
    $count = 0;
    foreach( $features as $feature) {
?>
        <li>
            <a href="project.php?p=<?=$feature["id"]?>">
            <h2><?=htmlentities($feature["name"])?></h2>
            <p><?=htmlentities($feature["abstract"])?></p>
            <h3>Bounty: <em><?=convert_money($feature["bounty"])?></em></h3>
            </a>
        </li>
<?
        $count++;
        if( $count == 4) break;
    }
?>
     </ul>
</div>
<? } ?>
