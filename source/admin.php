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
if( $auth !== 'admin') {
    print "Not Authorized.";
    exit;
}

function age($time) {
    $now = time();
    $age = $now - $time;
    if( $time == 0 || $age < 0) return "";
    if( $age < 60) return "$age seconds";
    if( $age < 3600) return round($age/60)." minutes";
    return floor($age/3600)." hours, ".round(($age%3600)/60)." minutes";
}

apply_template("Administration",array(
    array("name"=>"Administration","href"=>"admin.php"),
));
?>
<h1>Administration</h1>
<ul>
<li><a href="updateemail.php">Corporate News Updates</a></li>
</ul>

<h1>Test Scripts</h1>
<ul>
<li><a href="test-payout.php">Submission and Payout</a></li>
</ul>

<hr>
<p>
<a href="withdrawals.php">Manage the Withdrawal Queue</a>
</p>
<p>
<a href="bookkeeping.php">Bookkeeping</a>
</p>
