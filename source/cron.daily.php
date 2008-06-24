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
// This *must* be done *before* tallying the votes, since
// the featured projects are sorted using the current exchange rates.
$rc = ff_updateexchangerates();
print "Updating exchange rates: $rc[0]: $rc[1]<br>\n";

$rc = ff_tallyvotes();
print "Tallying Featured Project Votes: $rc[0]: $rc[1]<br>\n";
?>
