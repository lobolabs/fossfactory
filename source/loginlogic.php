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
function log_in( $username, $sid='', $remember=0) {
    if( $sid) return ff_setsessioninfo( $sid, $username);

    list($rc,$sid,$secure_sid) = ff_createsession();
    if( $rc) return array($rc,$sid);

    $rc = ff_setsessioninfo( $sid, $username);
    if( $rc[0]) return $rc;

    setcookie( "ff_session", $sid,
        $remember ? time()+60*60*24*365 : 0, "/",
        "", FALSE, TRUE);
    setcookie( "ff_secure_session", $secure_sid,
        $remember ? time()+60*60*24*365 : 0, "/",
        "", TRUE, TRUE);

    return array(0,"Success");
}
?>
