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
<?php apply_template("FAQ",array(array("name"=>"FAQ", "href"=>"faq.php")),'',false,true); ?>
<?
$n = 1;
function n() {
    global $n;
    print "$n. ";
    $n++;
}
?>
<h1>Frequently Asked Questions</h1>
<p>
If you are new to FOSS Factory, please read the <a href="overview.php">System Overview</a>.<br>
For a high-level introduction, see <a href="why.php">Why we need FOSS Factory</a>.
</p>
<h2><?n()?>If the project is fulfilled, who can download the solution?</h2>
<p>
All final solutions on FOSS Factory are free/open source software (FOSS),
and freely downloadable by anyone.
</p>
<h2><?n()?>Why should I sponsor projects?</h2>
<p>
There are many good reasons to sponsor projects.  Here are a few:
</p>
<ol>
<li>Free software development is a noble cause, worthy of your support.</li>
<li>Due to developer competition, higher bounties encourage your favourite projects to be completed more quickly.</li>
<li>Dividing your funds across subprojects gives you a degree of control over project direction.</li>
<li>Every dollar you contribute buys you a vote for which projects should be featured on the home page.</li>
<li>If you don't like the way a project is being run, you can use your
project credits to supplant the project lead!</li>
<li>Just for signing up as a monthly sponsor, your project credits and votes are automatically doubled.</li>
</ol>
<a name="fee"></a>
<h2><?n()?>How does FOSS Factory make money?</h2>
<p>
FOSS Factory deducts a <?=conf("commission")?>% transaction fee whenever
a bounty is paid.  In the future, we may also put advertisements on
the site.
</p>
<a name="paypalfees"></a>
<h2><?n()?>What's up with the PayPal fees?</h2>
<p>
When transferring money into or out of our system, FOSS Factory must charge
any PayPal fees to our users since the money is not part of our revenue.
We do not add any fee of our own.  The charge we apply is exactly what PayPal
charges us.
</p>
<p>
PayPal's fees are difficult to summarize since they can vary based
on the payment type, the payer or payee's account types, their volume
or transaction history, and the currency of the payment.  In general,
we have found the rates to be quite reasonable.
</p>
<p>
PayPal's official pricing can be found <a href="https://www.paypal.com/us/cgi-bin/webscr?cmd=_display-fees-outside">on their website</a>.
The website <a href="http://ppcalc.com/">ppcalc.com</a> provides a
calculator that may help you estimate the fees that may apply to your
transaction.
</p>
<h2><?n()?>What is the role of the project lead?</h2>
<ol>
<li>To passively direct the project by dividing up the bounty among
subprojects.</li>
<li>To maintain the official project requirements by accepting or
rejecting change proposals.</li>
<li>To decide when the project has been completed, and trigger
the payout.</li>
</ol>
<p>
Note that every action of the project lead is subject to scrutiny
and possibly arbitration.
</p>
<h2><?n()?>Why is there a cool-off period before bounties are paid out?</h2>
<p>
There are two important reasons.  First, it gives participants time to assess
the situation and to decide whether they think the payout is fair.
During this period, participants can file complaints if necessary.
</p>
<p>
The second reason is that it provides a little bit more time for users to
assess the stability of the code.  If any major bugs are reported,
the project lead can halt the pay-out.
</p>
<h2><?n()?>If a solution is submitted, then a better one is submitted
a few minutes later, shouldn't the second submission get the bounty?</h2>
<p>
That wouldn't be fair to the first submitter.  The decision of which
code is "better" can be extremely subjective; such a decision in the
hands of the project lead would leave far too much room for abuse.
</p>
<p>
To avoid shoddy code, it's important to outline quality standards in
the project requirements.  That way, low-quality submissions will fail
to satisfy the requirements, and can be rejected on that basis.
</p>

<h2><?n()?>I'm not happy with the project lead.  What are my options?</h2>
<p>
You have several options.
</p>
<ol>
<li>You can file a complaint.  The decision of the arbiters is
final.  This will only do you good if you have a genuine case.</li>
<li>If you (or you and your friends collectively) have more project
credits than the project lead, then you may supplant the project
lead.</li>
<li>You can fork the project.  Note that the forked project will start
off with no bounty.  But if you have enough support, you might convince
some of the sponsors to move their bounty over to the forked project.</li>
</ol>
<a name="deadlines"></a>
<h2><?n()?>Isn't it overkill to remove the project lead after a
single missed deadline?</h2>
<p>
Removal of the project lead is intended as a way of keeping the
project moving, not as a way of punishing the lead.  It means that if the
lead becomes busy with other things in life, somebody else can quickly
step in and fill the role.  In many cases, the original lead can reclaim
the position later.
</p>
