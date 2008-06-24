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
if( !ereg( "^/([0-9]*)/([0-9]*)", getenv("PATH_INFO"), $args)) exit;

ereg ("\\.(pdf|txt|png|jpeg|jpg|html|htm)$",strtolower($_SERVER['PHP_SELF']),$ags);

if($ags[1] == 'pdf')
    header("Content-type: application/pdf");
elseif($ags[1]=='png')
    header("Content-type: image/png");
elseif($ags[1]=='jpeg'||$ags[1]=='jpg')
    header("Content-type: image/jpeg");

ff_showpostattachment($args[1],$args[2]);
    
?>
