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
<?php apply_template("System Overview",array(array("name"=>"System Overview", "href"=>"overview.php"))); ?>
<?
$n = 1;
function n() {
    global $n;
    print "$n. ";
    $n++;
}

list($rc,$currencies) = ff_currencies();
$currency = $currencies[$GLOBALS["pref_currency"]];

?>
<h1>System Overview</h1>

<p>
FOSS Factory depends on a complex system of checks and balances.
We recommend reading over this document carefully, as it describes some
subtleties that can help you get the most out of the system.
</p>
<p>
If you're just looking for a high-level introduction, check out our opinion piece: <a href="why.php">Why we need FOSS Factory</a>.
</p>

<h1>Contents</h1>

<ol class="table-of-contents">
<li><a href="#projects">Projects</a></li>
<li><a href="#projectlead">The Project Lead</a></li>
<ul>
<li><a href="#whoisprojectlead">Who is the project lead?</a></li>
</ul>
<li><a href="#funding">Sponsorships</a></li>
<ul>
<li><a href="#projectcredits">Project credits</a></li>
<li><a href="#featuredprojects">Featured projects</a></li>
</ul>
<li><a href="#subprojects">Subprojects</a></li>
<ul>
<li><a href="#subprojectstrategy">Subproject strategy</a></li>
</ul>
<li><a href="#projectcompletion">Project Completion</a></li>
<ul>
<li><a href="#cooloffperiod">The cool-off period</a></li>
<li><a href="#holds">Holds on funds</a></li>
<li><a href="#submissionrejection">Submission rejection</a></li>
<li><a href="#communitydeduction">The community deduction</a></li>
<li><a href="#fee">FOSS Factory's fee</a></li>
</ul>
<li><a href="#disputes">Disputes</a></li>
</ol>

<a name="projects"></a>
<h1><?n()?>Projects</h1>
<p>
Each project consists of a name and a set of requirements.
Project requirements should be complete, but brief.  They should focus
on functional needs, as opposed to design points.  Wherever possible,
design issues should be left in the hands of the community.
On the other hand, it can be a good idea to outline quality
requirements like performance and resource utilization.
We anticipate that the community will develop standard wording for
abstract requirements such as usability and code maintainability.
</p>

<p>
In the interest of fairness to developers, project requirements should
be fairly static.  However, there will always be a need for changes
or clarifications.  Any member can post a requirement change proposal at any
time.  It is up to the project lead to accept or reject the proposal.
</p>

<p>
The project lead must ensure that requirement changes are reasonable
and justified.  If a <a href="#disputes">dispute</a> arises over a
requirement change, the benefit of the doubt will normally lie with
the plaintiff.
</p>

<a name="projectlead"></a>
<h1><?n()?>The Project Lead</h1>
<p>
Each project has a project lead &ndash; that is, the user
responsible for keeping the project moving forward.
The project lead's duties include:
</p>
<ul>
<li>To accept or reject requirement change proposals.</li>
<li>To allot a portion of the project funds to newly created subprojects.</li>
<li>To review submissions and decide whether they satisfy all project requirements.</li>
<li>To respond to complaints that are filed.</li>
</ul>

<a name="whoisprojectlead"></a>
<h2>Who is the project lead?</h2>
<p>
By design, the position of project lead can be transient.  The intent
is to ensure that there is always somebody able to fulfill the duties.
Nevertheless, a good project lead should have no problem retaining the
position.  The project creator is normally the initial project lead.
</p>

<p>
Whenever the position is vacant, any site member can become the project lead.
At any other time, the current project lead can be supplanted by any member
holding a larger number of <a href="#projectcredits">project credits</a>.
</p>

<p>
Immediately upon missing any duty deadline, the project lead is automatically
removed from the position, leaving it open for anyone.
This is not intended as punishment, but rather as a way to keep the
project moving.  In most cases, the original lead can reclaim the position
when he/she returns.
</p>

<a name="funding"></a>
<h1><?n()?>Sponsorships</h1>
<p>
Any site member can sponsor projects.  Subject to certain restrictions (see
<a href="#holds">Holds on funds</a>), you can distribute and redistribute
funds freely among any projects in the system.
</p>

<h2>Your Reserve</h2>
<p>
The <a href="account.php?tab=reserve#tabs">reserve</a> is like your personal bank account on FOSS Factory.  You can
transfer money into or out of it using PayPal, and you can draw from it
to sponsor projects or retract sponsorship funds back into it.
If you earn a bounty by submitting a project solution, the payment will
be placed into your reserve.
</p>

<div class=sidenote1>
<b>Tip:</b> A monthly sponsorship is an admirable contribution to the
community, and an important way to maximize the value of your time
and money.
</div>

<a name="monthly-sponsorships"></a>
<h2>Monthly Sponsorships</h2>
<p>
A monthly sponsor is a member who sponsors projects via a pre-authorized
monthly plan.  Your monthly sponsorship settings can be managed in the
<a href="account.php?tab=subscription#tabs">Monthly Sponsorship</a> section of
your <a href="account.php">My Factory</a> page.  You can choose how much of
your monthly deposit goes into each of your favourite projects.  You can also
choose an amount to contribute to a randomly selected featured project.
</p>

<p>Special benefits are reserved for monthly sponsors:</p>

<ul>
<li>Your <a href="#featuredprojects">featured projects</a> voting power
is doubled.</li>
<li>Your number of <a href="#projectcredits">project credits</a> is doubled
for all of your projects.  This doubles your capacity for supplanting the
project lead should the need arise.</li>
<li>Normally, when a developer receives payment for a solution, a portion of
the payment (currently <?=conf('communitydeduction')?>%) is deducted.
We call this the <a href="#communitydeduction">community deduction</a>.
For developers who are also monthly sponsors, this deduction is waived.</li>
</ul>

<p>
If you plan to participate as a developer, we highly recommend that
you become a monthly sponsor to avoid the community deduction.
</p>

<a name="refunds"></a>
<h2>Refunds</h2>
<p>
Project sponsorships can be withdrawn at any time to the
member's reserve, except for amounts held due to pending submissions.
</p>

<a name="projectcredits"></a>
<h2>Project credits</h2>
<p>
Every dollar you contribute to a given project gives you one project credit.
(This number is doubled for monthly sponsors)  Anyone with
more credits than the current project lead may take over the position
at any time.
</p>

<p>
You may also assign your project credits to any other member.
That is, you can use your credits as votes to help elect a member
of your choice.  This opens up the possibility that a group of
disgruntled users may overthrow the project lead by electing a
replacement.
</p>

<a name="featuredprojects"></a>
<h2>Featured projects</h2>
<p>
Every dollar you contribute to any project also buys you a vote toward the
list of featured projects.  (Your number of votes is doubled if you are
a monthly sponsor.)  Featured projects are displayed prominently throughout
the site.  Furthermore, most of the funds collected from the
<a href="#communitydeduction">community deduction</a> are distributed
among the featured projects.  Having your favourite project in the
featured list will help give it the attention it needs to quickly move
forward.
</p>

<div class=sidenote2>
<b>Remember:</b> Trying to finish a large project by yourself runs the risk
of somebody else submitting first.  Instead, break it down by creating
subprojects, and turn it into a community effort.
</div>

<a name="subprojects"></a>
<h1><?n()?>Subprojects</h1>

<p>
Subprojects provide a way of breaking down large projects into smaller
pieces.  Each subproject has its own requirements, and its own project
lead.  Subprojects may be broken further down into smaller subprojects.
The idea is that large projects may be recursively partitioned into
manageable tasks on the order of a single day of work.  The resulting
project hierarchy would naturally serve as a rough design document.
</p>

<p>
Anybody can create a new subproject at any time.  Each new subproject
can be thought of as a proposal for a particular breakdown approach.
The project lead for the parent project must decide what portion of the
parent project's funds to allot to it.
This decision should be based on the merit of the breakdown approach, and
on an estimate of the workload.
Subject to certain restrictions (see <a href="#holds">Holds on funds</a>),
project leads may rebalance subproject allotment percentages at any time.
</p>

<p>
The deliverable for a subproject is usually a cleanly separable component
such as a function, class, module, library, or even a completely separate
executable program.  The subproject requirements should outline the
functional requirements, plus the complete programming interface.
For example, if the deliverable is a command-line executable then the
subproject requirements should fully define the command-line syntax.
</p>

<a name="subprojectstrategy"></a>
<h2>Subproject strategy</h2>

<p>
If you are thinking of working on a project which would take you more than
about one day of work, consider creating a subproject for the first component
that you'd like to work on.  Don't bother making a complete breakdown
design.  You can leave creation of the other subprojects to other developers.
Creating only subprojects that you intend to work on helps to signal to other
developers that somebody is probably working on it, so they're more likely
to leave the task for you rather than compete for the same funds.
</p>

<p>
Avoid creating redundant subprojects.  If you believe that you know a better
approach for a subproject, then start by proposing a requirements change
on that subproject.
If your change proposal gets rejected and you still feel that
your approach is significantly better, then it's appropriate to create a
competing subproject.  The top-level project lead will decide whether
to allocate funding to your new approach.  It is at his/her discretion whether
to remove funding from the first approach, or to support both equally.
It is often valuable to support two competing approaches for a single
component.
</p>

<a name="projectcompletion"></a>
<h1><?n()?>Project Completion</h1>

<p>
To claim the project funds, a developer must be the first person to provide a
complete solution to the project as a single submission.  The submission
must satisfy all of the project requirements at the time of submission.
</p>

<p>
A solution can be posted either for the complete top-level project, or
for any individual subproject.
</p>

<p>
The effective funding on a subproject is the sum of any funds contributed
directly to that subproject, plus its share of its parent project's
funds.  When a subproject is solved, the solver is paid the full direct
funding on that subproject, plus the portion of the parent project's funds
that were allocated to that subproject.  For the purpose of
project sponsorship and credit tracking, the reduction in parent project
funds is attributed proportionally to all sponsors.
</p>

<p>
At first it might seem that this race-to-the-finish approach violates
the collaborative spirit of the free software community.  However,
as long as a project or subproject is large enough or lucrative enough
to be attractive to multiple developers, most developers are motivated
to minimize their risk by further subdividing the project.  The result
is better project definition, and more opportunity for collaboration.
</p>

<a name="cooloffperiod"></a>
<h2>The cool-off period</h2>

<p>
Whenever a submission is approved by the project lead, there is 
a time delay before the payout actually occurs.  The delay may be
anywhere from 24 hours to two weeks, depending on the popularity of the
project and the size of the payout.  The purpose of the delay is to give
community members a chance to assess whether the project lead has made
a mistake, or even acted in bad faith.  During this interval, members
can file a complaint if they believe there is a problem.
</p>

<a name="holds"></a>
<h2>Holds on funds</h2>

<p>
Normally sponsors can redistribute their sponsorship funds freely. However,
from the moment a submission is made on a project, all funds on that
project are locked in place at least until a decision is made.
This ensures that sponsors can't use their funds as a carrot, only to
withdraw it the moment a submission arrives.  The hold remains in place
either until the submission is rejected with prejudice, or until the
submission is rejected without prejudice and the refiling grace period
expires.  If the submission is accepted, then the funds are paid out,
so they are obviously no longer withdrawable.
</p>

<p>
While a hold is in place sponsors can still increase their funding if
they so choose (though they are warned about the hold).  Pre-authorized
sponsorships will proceed without warning.
</p>

<a name="submissionrejection"></a>
<h2>Submission rejection</h2>

<p>
There are two different modes of submission rejection &ndash;
rejection with prejudice, and rejection without prejudice.
</p>

<p>
If a submission is rejected <i>with prejudice</i>, it means that, in
the judgment of the project lead, it is not even close to being a valid
solution.  There are many possible reasons for this.  For example:
</p>
<ul>
<li>Confusion or misunderstanding on the part of the submitter</li>
<li>Obvious copyright infringement, or other legal problems</li>
<li>Deliberate abuse, or gaming of the system</li>
</ul>
<p>
In any case, the submission is completely removed,
and the hold on project funds is immediately lifted.  A notification is
sent to the submitter to explain why the submission was rejected.
</p>

<p>
If a submission is rejected <i>without prejudice</i>, it means that it
seemed to be an honest solution attempt, but it was found to be lacking
in minor ways.  In this case, the hold on project funds remains for a
period of time in order to give the submitter a chance to fix the problems
and resubmit.  The submission will remain listed in the submissions tab for
as long as the hold exists.  Other contestants may still submit their
solutions during the hold period; as always, the first correct submission
will earn the payment.
</p>

<a name="communitydeduction"></a>
<h2>The community deduction</h2>

<p>
Normally, whenever a project is successfully completed,
<?=conf('communitydeduction')?>% is deducted
from the payout.  By default, the deduction is distributed among the
top ten featured projects, weighted according to the number of votes
for each project.  Alternately, the claimant can choose to 
direct the deduction to one of several
<a href="charities.php">charitable organizations</a>.
</p>

<p>
The community deduction is waived whenever the payment is claimed by a
monthly sponsor.  That is, if you are both a developer and a monthly sponsor,
then whenever you complete a project you are entitled to 100% of the funds.
</p>

<p>
The purpose of the community deduction is to encourage members to become
monthly sponsors, in order to help sustain the community.
</p>

<a name="fee"></a>
<h2>FOSS Factory's fee</h2>

<p>
FOSS Factory deducts a <?=conf("commission")?>% transaction fee from
all bounty payments.  This fee is separate from the community deduction.
</p>

<a name="disputes"></a>
<h1><?n()?>Disputes</h1>

<p>
Any registered member can file a complaint regarding the management
of a project.  When a complaint is filed, the project lead is notified,
and must respond to the complaint.  If the project lead responds by posting a
counter-argument, then the plaintiff is notified and must follow up.
The dispute can continue back and forth between the plaintiff and
the project lead until one of the following occurs:
</p>
<ol>
<li>The plaintiff opts to cancel the dispute; or</li>
<li>On their turn, one or the other party chooses to finalize the discussion.
In so doing, they forfeit their final opportunity to comment, thus granting
their opponent the last word.</li>
</ol>

<p>
The entire dispute takes place on a public page, linked to from the
project page, with a public discussion forum underneath.
</p>

<p>
The final decision is normally made by a jury.  The jury is auto-selected
as a group of active site members who have shown no interest in the project
in question, or in any of its related projects.  Jury members remain
anonymous.  In the event that a jury
can't be selected, the decision is made by an arbiter (a FOSS Factory staff
member).  In either case, the decision is final and binding.
</p>

<p>
Prior to making their decision, jury members (or the arbiter) have the
opportunity to direct specific questions to either party.  The parties
are responsible for answering the questions.
</p>

<p>
Throughout the process, the project lead is given deadlines for each of
his/her responsibilities.  The plaintiff, on the other hand, is not given
any deadlines.  The presumption is that it's the plaintiff who is seeking
justice, so any delay on his/her part only harms him/herself.
</p>

<p>
<i>Note: The automatic jury selection system is not yet operational.
Currently, all finalized disputes are decided by an arbiter.</i>
</p>

