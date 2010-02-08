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
function formatText( $text) {
    return "<tt>".linkify(str_replace("\n","<br>\n",
        ereg_replace("^ ","&nbsp;",
        str_replace("\n ","\n&nbsp;",
        preg_replace("/(  *) /e", "str_replace(' ','&nbsp;','\\1').' '",
        htmlentities($text))))))."</tt>";
}
?>
