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
<?php apply_template("Why we need FOSS Factory",array(array("name"=>"Why we need FOSS Factory", "href"=>"why.php")),'',false,true); ?>
<h1>Why we need FOSS Factory</h1>

<p>
The free software movement doesn't need a bounty model.  On the contrary,
the movement has proven to be an incredibly powerful force, turning
whole segments of the software industry on their heads, forcing countless
major corporations to rethink their business models.
</p>

<div class=sidenote1>
With FOSS Factory, a project can get its start from just an itch and a
preliminary plan.
</div>

<p>
Even so, only a tiny fraction of its potential is being realized.
Most large FOSS projects begin as a single programmer scratching his
own personal itch.  In reality, that programmer needs much more than just
an itch.
He also needs a plan, the skill in the relevant domain, good charisma
and leadership abilities, and the drive and dedication to see the
project through.  Plus he needs to have the time to commit.  It's a pretty
rare mix in any single individual.
</p>

<p>
With FOSS Factory, a project can get its start from just an itch and a
preliminary plan.  As long as you're not the only person with that
particular need, your project has a good chance of success.
</p>


<h2>Background</h2>

<p>
The concept of free software bounties is not new.  Two notable attempts,
SourceXchange and Cosource.com, both launched in 1999.  At the time,
it generated some excitement.  In May of that year, Doc Searls of the
<a href="http://www.linuxjournal.com/">Linux Journal</a>
quoted Eric S. Raymond:
"I love it. One company is just a bunch of crazy guys. But
<a href="http://www.searls.com/linuxforsuits/industry.html">two companies
is an industry</a>."
</p>

<p>
A doomed industry, apparently.  Both companies were defunct by 2001.
</p>

<p>
Today, there are a few interesting FOSS bounty sites around.  Two of the best
known are Gnome's
<a href="http://www.gnome.org/bounties/">Desktop Integration Bounty Hunt</a>
and the bounty system integrated into
<a href="https://launchpad.net/bounties/">Launchpad</a>.  Neither of these
sites is intended to be general-purpose, each catering to its own
domain.  Nat Friedman, maintainer of the Gnome bounties, specifically laments
this point in the page introduction.  He writes, "Some day I'd like there
to be a
<a href="http://nat.org/2005/january/#bountysystem">general bounty system</a>
that anyone can use.", linking to an entry in his blog where he outlines
the idea.  The link is worth reading.
</p>

<p>
Both the Gnome and Launchpad sites' strength lies in their
simplicity.  Both seem to perform their intended functions well.
But we clearly could use something more.
</p>

<p>
A much more interesting bounty site is
<a href="http://www.pubsoft.org/">The Public Software Fund, Inc.</a>
This site provides all the basic functionality for members
to post projects, contribute funding and claim bounties.  The posted bounties
on their site are impressive, to say the least, with many of them in the
range of tens of thousands of US dollars.  Unfortunately, the site comes
across more as a proof-of-concept demo than as a production system -- the
interface is tedious and uninformative, and it produces absolutely no sense
of community.  More importantly, it's sure to suffer from serious project
management problems as will be explained in the next section.  In fact,
there is evidence to that effect.  For example, the
<a href="https://www.pubsoft.org/pubsoft.py/project?proj=CurrencyWatermarks">CurrencyWatermarks</a> bounty has $20,000 of funding, and a $20,000 bid.
The site gives no indication of the project status, but the
<a href="http://www.links.org/watermarks/">proposal</a> estimates a
completion date of November 2004.  It's impossible to tell whether the
work was ever done, or whether the bounty was ever paid.
</p>

<h2>Why do FOSS bounty sites fail?</h2>

<p>
There are several major hurdles in designing a general-purpose FOSS bounty
site.
</p>

<h3>1. The thought of sharing the bounty is a disincentive to
collaboration.</h3>
<p>
In June of 1999, Bernie Thompson of Cosource.com
<a href="http://web.archive.org/web/19991109234353/http://www.cosource.com/gray_response.shtml">wrote</a>:
<blockquote>
"This was one of the first issues we wanted to tackle with
Cosource.com. How can we make a bounty system work, yet not have
developers motivated to keep secrets from each other as they all race
to win the bounty?"
</blockquote>
Their "solution" was a bidding process, granting an exclusive on
the bounty.  This approach avoids the incentive for secrecy, but it
completely fails to encourage collaboration.  At the same time, it
creates even bigger problems. (see below)
</p>

</p>
It's interesting to note that for very small tasks, this issue disappears
because there's no reason to split up the work.  In fact, the Gnome site's
success is owed largely to the fact that its tasks are all very small
and well-defined &ndash; perfect for a one-man team.
</p>

<p>
FOSS Factory exploits this principle by tying bounty subdivision to
the collaborative design process.  Here's how it works.  Any community
member can help break down a large project into smaller components,
offering an initial design for each component.  Forums are provided for
discussing and proposing design revisions.  The most promising components
are assigned a portion of the bounty.
</p>

<p>
This approach creates a disconnect between the choice of whether to
collaborate and the choice of whether to share the bounty.  The result is an
environment where collaboration is rewarded with early partial payment,
and failure to collaborate carries a risk of getting left behind.
</p>


<h3>2. Exclusive project assignment, though seemingly necessary, is a
project management nightmare.</h3>
<p>
Fairness seems to dictate that if a person or team commits to a project,
then that project should be exclusively theirs at least for a period of time.  
</p>

<p>
Exclusivity is the direct opposite of openness, and it incites
a hornet's nest of project management problems.  As often as
not, programmers get bored or distracted.  We all have a tendency to
overestimate our own abilities, and to underestimate project complexity.
Projects are often overdesigned, leading to an endless series of
sidetracks, or underdesigned, leading to an endless series of rewrites.
For these, and many more reasons, exclusively-assigned projects are
frequently stalled or abandoned, leaving the project no further ahead
after weeks, months or years of delay.
</p>

<div class=sidenote2>
In the absence of exclusivity, developers retain the freedom to work on their
own schedule, and on their own terms.
</div>

<p>
FOSS Factory never grants exclusivity.  Instead, it provides facilities to
make project contention avoidable, and trusts its users to cooperate.
In the absence of exclusivity, developers retain the freedom to work on their
own schedule, and on their own terms.  It eliminates the complexity of
a bidding process, and it reduces all of the project management
issues down to a single decision: Does the submission meet the spec?
</p>

<h3>3. Because the final product will be freely available, it's especially
hard to motivate sponsors.</h3>

<p>
Separating people from their money is always the hardest part of any
venture.  Past successes in the FOSS bounty arena have focussed on corporate
sponsors, and therefore involved a lot of leg work on the part of the
organizers.  Notably, the Gnome site benefits from one incredibly
generous sponsor, namely Novell.
</p>

<p>
In all probability, corporate sponsors will play a dominant role in FOSS
Factory's funding.  However, its success will ultimately be measured by
its self-sufficiency.  The hope is that the community will become adequately
attractive to sponsors so as to no longer need manual sales strategies.
To this end, FOSS Factory incorporates several motivational tactics to
encourage both single and recurring sponsorships.  For example:
</p>
<ul>
<li>Sponsors are granted exceptionally broad flexibility in handling their
funds.</li>
<li>Project credits (derived from sponsorships) may be used to vote for
featured projects, or to help elect a favourite project lead.</li>
<li>Monthly sponsorships are rewarded by increasing
the value of project credits.</li>
</ul>
<p>
Time will tell whether the existing incentives will accomplish the
site's long-term funding goals.  In the meantime, the success of other
sites proves that traditional techniques will suffice.
</p>

<h3>4. The latitude that developers need for inspiration conflicts with
the control that sponsors want in return for their funding.</h3>

<p>
This issue is familiar to anyone who has been involved in any
software consulting project.  Unless the project is unambiguously specified
down to every minute detail (a nearly impossible task), there will
always be disagreements.  In the worst case, it can destroy the project,
resulting in refusal to pay and/or refusal to deliver.
</p>

<div class=sidenote3>
The freedom to participate in any way, at any time, without any formal
commitment is a cornerstone of the free software movement, and is pivotal
in FOSS Factory's design.
</div>

<p>
In an <a href="http://sohodojo.com/techsig/sxc24-postmortem.html">in-depth
post-mortem</a> for SourceXchange Project #24, Jim Salmons of <a
href="http://sohodojo.com/">Sohodojo</a> acutely portrayed the problem.
</p>

<p>
Again, this problem is not unique to FOSS bounty sites.  In fact, it
seems conspicuously absent in the vast majority of unfunded free software
projects.
</p>

<p>
FOSS Factory's solution rests on four pillars:
</p>
<ol>
<li>Encourage brief project proposals with wide creative leeway, but specific goals.</li>
<li>Involve the entire community in the design process.</li>
<li>Limit the project lead's authority.</li>
<li>Preserve all parties' freedom to walk away.</li>
</ol>
<p>
The fourth pillar depends on an interesting irony.  When parties are
not forced to cooperate, they feel more free to cooperate.  The freedom
to participate in any way, at any time, without any formal commitment
is a cornerstone of the free software movement, and is pivotal
in FOSS Factory's design.
</p>

<h2>Completeness and Usability</h2>

<p>
Despite all the benefits mentioned above, if FOSS Factory's website
were too hard to use, nobody would use it.  Fortunately, its usability
and level of completeness truly set it apart from other current bounty
sites.
</p>

<p>
FOSS Factory was designed to accommodate a large,
vibrant community working together toward many shared goals.  The site
includes facilities for bounty management, collaborative
project design, general discussion, jury-based dispute resolution,
selection of featured projects, project alerts and announcements,
and much more.  Every aspect of project workflow, including funding
and bounty payouts is fully controlled by the community.  The shallow
navigation model ensures that the site doesn't get in your way, but
rather puts the tools you need at your fingertips.
</p>

<h2>Conclusion</h2>

<p>
FOSS Factory is exactly what is needed to take free software development
to the next level.  It's been a long time in coming, but we
finally have a general bounty system that can fund large FOSS projects.
The applications are endless.  Let's put it to use.
</p>

<hr>
<i>Last Modified: <?=date("F j, Y",filemtime($_SERVER["SCRIPT_FILENAME"]))?></i>
