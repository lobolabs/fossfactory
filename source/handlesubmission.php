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
$id = scrub($_REQUEST['id']);
$submissionid= intval($_REQUEST['submissionid']);
$accept=scrub($_REQUEST['accept']); 
if ($accept=='true') 
    list($rc,$err) = ff_acceptsubmission($username,$submissionid);
elseif($accept=='false')
    list($rc,$err) = ff_rejectsubmission($username,
        $submissionid,$_REQUEST["rejectreason"],0);
elseif($accept=='prejudice')
    list($rc,$err) = ff_rejectsubmission($username,$submissionid,'',1);
    
    header("Location: ".projurl($id,"tab=submissions"));
?>

