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

function account_show_abstracts(c) {
    var o=c;
    //var check=document.getElementById('check');
    //var check2=document.getElementById('check2');

    //selected is the tab that is currently selected
    //var selected=tab_selected();
    var selected = document.getElementById('my_projects');
    //if the "show abstracts" checkbox is checked
    if (c.checked==true) {
        selected.className="open shown";
    }
    else {
        selected.className="open";
    }
}
function assign_dialogue() {
    d = document.getElementById('assign_dialogue');
    if (d.className=="pop-up hidden") 
        d.className="pop-up shown";
    else
        d.className="pop-up hidden";
}
function submit_form() {
    d=document.getElementById('assign_dialogue');
    d.submit();
    d.className="pop-up hidden";
}

