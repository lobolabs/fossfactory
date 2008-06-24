/*
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
*/

/* To use this function, use HTML similar to the following:
 *   <img class=arrow id="foo-arrow" src="color-arrow.gif"
 *        onClick="folder('foo','color')">&nbsp;<a class=folder
 *        href="javascript:folder('foo','color')">Some Title</a>
 *   <div id="foo-div" class=folded>Here is some more information</div>
 */
function folder(id,color) {
    var color=(color==null)?"black-":color+"-";
    var d = document.getElementById(id+'-div');
    var a = document.getElementById(id+'-arrow');
    if( d.className == 'unfolded') {
        a.src = color+'arrow.gif';
        d.className = 'folded';
    } else {
        a.src = color+'arrow-down.gif';
        d.className = 'unfolded';
    }
}
