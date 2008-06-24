/*
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
*/

attachments = new Array();
attachment_callbacks = new Array();

function setup_attachments(uniq,callback) {
    attachments[uniq] = new Array();
    attachment_callbacks[uniq] = callback;

    var filelist = document.createElement('div');
    filelist.id = uniq+'_filelist';

    var button = document.createElement('iframe');
    button.style.border = '0px';
    button.style.width = '9em';
    button.style.fontSize = '24px';
    button.style.height = '1em';
    button.style.overflow = 'hidden';
    button.style.marginTop = '0.3em';
    button.scrolling = 'no';
    button.src = 'attachmentframe.php?uniq='+uniq;
    button.frameBorder = 0;

    return [filelist,button];
}

function refresh_attachments(uniq) {
    var h = '';
    if( attachments[uniq].length) {
        h += '<b>Attachments:</b>\n'+
            '<table class="attachments" cellspacing=0>\n';
        for( i in attachments[uniq]) {
            var a = attachments[uniq][i];

            var filesize = a['filesize'];
            if( filesize < 1024) {
                filesize = filesize + ' bytes';
            } else if( filesize < 1024*1024) {
                filesize = (Math.round(filesize/102.4)/10) + ' kbytes';
            } else {
                filesize = (Math.round(filesize/102.4/1024)/10) + ' Mbytes';
            }

            if( i & 1) var row = 'evenrow';
            else var row = 'oddrow';

            h += '<tr class='+row+'><td>'+
                '<input type=hidden name=attachment_filename_'+
                a['basename']+' value="'+htmlencode(a['filename'])+'">'+
                '<i>'+htmlencode(a['filename'])+
                '</i></td><td class=lpad align=right>'+filesize+
                '</td><td class=lpad>'+
                '<a href="javascript:removeattachment(\''+uniq+'\','+i+
                ')" style="font-size:small">remove</a></td></tr>\n';
        }
    }
    document.getElementById(uniq+'_filelist').innerHTML = h;
}

function addattachment(uniq,basename,filename,filesize) {
    var attachment = new Array();
    attachment['basename'] = ''+basename;
    attachment['filename'] = ''+filename;
    attachment['filesize'] = ''+filesize;
    attachments[uniq].push(attachment);

    if( attachment_callbacks[uniq])
        attachment_callbacks[uniq](uniq,filename);

    refresh_attachments( uniq);
}

function removeattachment(uniq,n) {
    if( !confirm('Are you sure you want to remove this attachment?')) return;
    attachments[uniq].splice( n, 1);
    refresh_attachments( uniq);
}

function htmlencode(str) {
    str = str.replace(/&/g, "&amp;");
    str = str.replace(/\"/g, "&quot;");
    str = str.replace(/\'/g, "&#039;");
    str = str.replace(/</g, "&lt;");
    str = str.replace(/>/g, "&gt;");
    return str;
}
