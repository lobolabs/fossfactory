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
$p = scrub( $_REQUEST["p"]);
$amt = "$_REQUEST[amt]";

apply_template("Project Created", array(
    array("name"=>"Project Created",
        "href"=>"createdproject.php?p=$p&amt=".urlencode($amt))
));

?>
<div class=results>
Thank you for creating a new FOSS Factory project.  Your payment of
<?=format_money($amt)?> has been received.  A receipt
has been emailed to you.  You may log into your account at
<a href="http://www.paypal.com/">www.paypal.com</a> to view details of
the transaction.

<p>
<a href="<?=projurl($p)?>">Continue to Project Page</a>
</p>
