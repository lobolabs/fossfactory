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

function select(clicked) {
    //Deselect all of the siblings
    var o = clicked;
    var x = document.getElementById(clicked.id+"_container");
    var y = x;
    while ( o.previousSibling) o = o.previousSibling;
    while ( o) {
        if (o.tagName == clicked.tagName) o.className = '';
        o = o.nextSibling;
    }

    while ( y.previousSibling) y = y.previousSibling;
    while ( y) {
        if (y.tagName == x.tagName) y.className = 'closed';
        y = y.nextSibling;
    }

    clicked.className="selected";
    x.className="open";
    show_abstracts(document.getElementById('check'));
    return false;
}
function tab_selected() {
    var x = document.getElementById("projects_container");
    while (x) {
        if (x.className.match("open")=="open") {
            return x;
        } else {
            x=x.nextSibling;
        }
    }
}
function select_tab(clicked) {
    select(clicked);
    return false;
}
//we're looking for the details div
function get_lastchild(n,tag_name) {
    var x=n.lastChild;
    while (x.tagName!=tag_name) {
        x=x.previousSibling;
    }
    return x;
}
//this is to fetch the "abstract" div
function get_prev_sibling( n,tag_name) {
    n = n.previousSibling.previousSibling;
    while( n.tagName !=tag_name) {
        n = n.previousSibling;
    }
    return n;
}
function details(curRow) {
    //get the li element of the row
    var pnode = curRow.parentNode.parentNode;

    //if if the details class is not open
    if( get_lastchild(pnode,"DIV").className.match("open") != 'open') { 
        //set the details class to open
        get_lastchild(pnode,"DIV").className = 'details open';
        //set the li class to open
        pnode.className = "open";
        //set the abstract class to shown 
        get_prev_sibling(get_lastchild(pnode),"DIV").className="abstract shown";
    }
    else {
        get_lastchild(pnode,"DIV").className = 'details closed';
        pnode.className = "closed";
        get_prev_sibling(get_lastchild(pnode),"DIV").className="abstract hidden";
    }
    return false;
 }
function show_abstracts(c) {
    var o=c;
    var check=document.getElementById('check');
    var check2=document.getElementById('check2');
    
    //selected is the tab that is currently selected
    var selected=tab_selected();
    //if the "show abstracts" checkbox is checked
    if (c.checked==true) {
        selected.className="open shown";
    }
    else {
        selected.className="open";
    }
        check.checked=c.checked;
        check2.checked=c.checked;
}
function checkAll(field) {
    for (i=0;i<field.length;i++) 
        field[i].checked=true;
}
function clearAll(field) {
    for (i=0;i<field.length;i++)
        field[i].checked=false;
}
    
