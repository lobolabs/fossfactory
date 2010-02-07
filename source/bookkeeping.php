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

if( isset($_REQUEST["fromy"])) {
    $from = mktime(0,0,0,intval($_REQUEST["fromm"]),
        1,intval($_REQUEST["fromy"]));
    $to = mktime(0,0,0,intval($_REQUEST["tom"])+1,
        1,intval($_REQUEST["toy"]));
} else {
    $now = time();
    $from = mktime(0,0,0,date("m",$now),1,date("Y",$now));
    $to = mktime(0,0,0,date("m",$now)+1,1,date("Y",$now));
}

apply_template("Bookkeeping",array(
    array("name"=>"Administration","href"=>"admin.php"),
    array("name"=>"Bookkeeping","href"=>"bookkeeping.php"),
));

function admin_gettransactionreport( $from=false, $to=false)
{
    $qu = sql_exec("select regexp_replace(account,':.*','') as acct,".
        "sum_money(change) from transaction_log ".
        (($from===false && $to===false)?"":"where true ").
        ($from===false?"":"and time >= ".intval($from)." ").
        ($to===false?"":"and time < ".intval($to)." ").
        "group by acct order by acct");
    if( $qu === false) return private_dberr();
    $report = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $report["$row[acct]"] = "$row[sum_money]";
    }
    return array(0,$report);
}

function admin_getpaypaltransactions( $currency=false, $from=false, $to=false)
{
    $qu = sql_exec("select xid,min(time) as time,".
            "subtract_money('',sum_money(change)) as change ".
            "from transaction_log where ".
            "xid in (select xid from transaction_log where ".
            "(account like 'anon-deposits:%' or ".
            "account like 'deposits:%' or ".
            "account like 'subscription-payments:%' or ".
            "account like 'withdrawals:%')".
            ($currency===false?"":
                " and change like '%".sql_escape($currency)."'").
            ($from===false?"":" and time >= ".intval($from)).
            ($to===false?"":" and time < ".intval($to)).
            ") and (account like 'anon-deposits:%' or ".
            "account like 'deposits:%' or ".
            "account='paypal-fee' or ".
            "account like 'subscription-payments:%' or ".
            "account like 'withdrawals:%') ".
            "group by xid order by xid");
    if( $qu === false) return private_dberr();
    $transactions = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $transactions[intval($row["xid"])] = array(
                "xid" => intval($row["xid"]),
                "time" => intval($row["time"]),
                "change" => "$row[change]");
    }

    return array(0,$transactions);
}

?>
<h1>Bookkeeping</h1>

<form method=get action="bookkeeping.php">
<p>
From:
<select name=fromm>
<? for( $m=1; $m <= 12; $m++) { ?>
<option value=<?=$m?><?=$m==date("m",$from)?" selected":""?>><?=date("F",mktime(0,0,0,$m,1,2000))?></option>
<? } ?>
</select>
<select name=fromy>
<? for( $y=2007; $y <= intval(date("Y")); $y++) { ?>
<option value=<?=$y?><?=$y==date("Y",$from)?" selected":""?>><?=$y?></option>
<? } ?>
</select>
To:
<select name=tom>
<? for( $m=1; $m <= 12; $m++) { ?>
<option value=<?=$m?><?=$m==date("m",$to-1)?" selected":""?>><?=date("F",mktime(0,0,0,$m,1,2000))?></option>
<? } ?>
</select>
<select name=toy>
<? for( $y=2008; $y <= intval(date("Y"))+1; $y++) { ?>
<option value=<?=$y?><?=$y==date("Y",$to-1)?" selected":""?>><?=$y?></option>
<? } ?>
</select>
<input type=submit>
</p>
</form>
<?

list($rc,$report) = admin_gettransactionreport($from,$to);

?>
<p>
<table border=1 cellspacing=0 cellpadding=3>
<tr><th align=left>Account Group</th>
<?
list($rc,$currencies) = ff_currencies();
foreach( $currencies as $code => $c)
    print "<th align=right>".htmlentities($code)."</th>";
?>
</tr>
<?
foreach( $report as $acct => $change) {
    print "<tr><td>".htmlentities($acct)."</td>";
    foreach( $currencies as $code => $c) {
        print "<td align=right>".format_for_entryfield(currency_value($change,$code))."</td>";
    }
    print "</tr>\n";
}
?>
</table>
</p>

<?
foreach( $currencies as $code => $c) {
    list($rc,$transactions) = admin_getpaypaltransactions($code,$from,$to);
?>
<p>
<h2><?=htmlentities($code)?> Transactions</h2>
<table border=1 cellspacing=0 cellpadding=3>
<tr>
<th align=left>time</th>
<th align=left>xid</th>
<th align=right>change</th></tr>
<?
foreach( $transactions as $xid => $trans) {
    print "<tr>";
    print "<td><b>".date("Y-m-d",$trans["time"])."</b> ".date("H:i T",$trans["time"])."</td>";
    print "<td>$xid</td>";
    print "<td align=right>".format_money($trans["change"])."</td>";
    print "</tr>\n";
}
?>
</table>
</p>
<?
}
?>
