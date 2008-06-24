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

apply_template("Advisors",array(
   array("name"=>"Advisors","href"=>""))
    ,$onload,false,true);
?>
<style>
h1,h2 { clear: both; }
</style>
<div id="advisors">
<h3>Advisors</h3>
<h2 style="clear:none">Larry Smith<br>
Adjunct Associate Professor, University of Waterloo</h2>
<img src="images/larry.jpg">

<p>
Larry Smith is an Adjunct Associate Professor of Economics at the University of Waterloo.  He is recipient of the University of Waterloo's Distinguished Teacher Award.</p>
<p>Larry is also president of Essential Economics Corporation, an economic consulting practice that serves a wide range of public and private clients. The firm specializes in the economics of innovation, education and development.</p>
<p>He is the author of Beyond the Internet: How Expert Systems Will Truly Transform Business.</p>
<p>Larry advises UW students who start their own ventures and he has now taught more than ten percent of the university's alumni. In the winter 2007 term he assigned his 25,000th grade, representing almost 18,000 individuals.</p>


<h2>Adib Saikali<br>
Founder & President, Programming Mastery Inc.</h2>
<img src="images/adib.png"><p>
Adib Saikali is the founder of Programming Mastery Inc. a software
development consulting and training company. Since founding Programming
Mastery Adib has trained &amp; mentored over 500 developers at organizations
throughout Canada, USA and Europe. Adib has contributed his work as a
technical author to many publications such as Oracle Magazine, and has also
spoken at several industry conferences including JavaOne. His vast technical
knowledge and project experience combined with his ability to identify,
research and educate others allow him to create effective training and
mentoring solutions to propel his clients forward.  Adib holds a Bachelor of
Mathematics from University of Waterloo.</p>
<h2>Stephen Van Egmond<br>
Tiny Planet's owner and principal</h2>
<img src="images/svan2.png">

<p>
Stephen van Egmond, Tiny Planet's owner and principal, is one of
Toronto's foremost entrepreneurial web developers, with a range of
technical experience on projects involving project management
discipline and complex systems architecture. He led the redevelopment
of FTD.com into a stable, efficient service that serves millions of
customers hourly and accepts over 5 orders per second. He started
Tiny Planet Consulting in 2001 to bring effective, usable
collaborative technologies within the reach of small organizations.
His main interests are the subtle forces that foster online
communities, the nuances of simple designs, and the obscure details
that bring a project to completion within budget and without hassle.</p>


<h2>Bill Snow, BMath<br>
Consultant, Tiny Planet Consulting</h2>
<img src="images/bill2.png">

<p>
Bill has consulted on or participated in dozens of Internet projects.
A background in programming and human-computer interaction help to
inform his focussed and practical approach to design.  He has spent
his consulting career delivering value to businesses by solving
problems with Internet tools.  Prior to launching his consulting
career in 2003, Bill served as VP with the group of companies
responsible for the Galaxiworld Internet Casino.

His wife and three beautiful young children occupy all of Bill's time
outside the consulting practice.  They enjoy bike trailers, books,
and breakfasts together.</p>


<h2>Fred McLain<br>
Systems Analyst/Senior Computing Technologist</h2>
<img src="images/fred-mclain.jpg">

<p>
Fred McLain has been actively developing software for over 25 years and
founded several successful software development companies.  Notorious
for exposing security issues with existing technologies, Fred is now
working as a research consultant for Boeing Phantom Works, Mathematics
and Computer Technologies, where he is helping to produce secure software
and data distribution to the Boeing 787.
</p>
<p>
Fred was the founder and CEO of Apropose Inc., co-founded Internet
America and worked as Senior VP of
Unison Group.  He has worked as a senior engineer for IBM and US West
(now Verizon).  Fred has also worked in a lead development role and
as advisor to several open source projects, including the Open Voting
Consortium and the National Election Data Archive.
</p>
</div>
