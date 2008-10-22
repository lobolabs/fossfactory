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
/*
Error codes:

0 - Success
1 - Internal System Error
2 - No such object
3 - A member with that username already exists
4 - Invalid parameter
5 - Permission denied
6 - A member with that email address already exists
7 - Race condition
8 - Operation currently impossible
9 - Insufficient funds
*/

$GLOBALS["DATADIR"] = realpath(realpath(".")."/..")."/data";
if( !is_dir( $GLOBALS["DATADIR"])) {
    print "The data directory does not exist.<br>\n";
    print "Please create it as follows:<br>\n<br>\n";
    print "mkdir -m 1777 ".htmlentities(escapeshellarg($GLOBALS["DATADIR"]));
    exit;
}
if( (fileperms( $GLOBALS["DATADIR"]) & 07777) != 01777) {
    print "The data directory has incorrect permissions: ".
        substr("0000".decoct(fileperms($GLOBALS["DATADIR"]) & 07777),-4).
        "<br>\n";
    print "Please fix them as follows:<br>\n<br>\n";
    print "chmod 1777 ".htmlentities(escapeshellarg($GLOBALS["DATADIR"]));
    exit;
}

function sql_escape($str) {
    return pg_escape_string($str);
}

function sql_exec($query) {
    static $conn = false;
    if( $conn === false) {
        $dbname = "fossfactory";
        if(ereg("/www.fossfactory.org/~([^/]+)",$GLOBALS["SITE_URL"],$parts)) {
            $dbname = "sandbox_".$parts[1];
        }
        $conn = pg_connect("dbname=$dbname user=postgres");
        if( $conn === false) return false;
    }
    $rc = pg_exec($query);
    if( $rc === false) {
        $backtrace = debug_backtrace();
        $b = $backtrace[0];
        trigger_error("Error in $b[file] on line $b[line].<br>\n");
    }
    return $rc;
}

function sql_numrows( $qu) {
    return pg_numrows( $qu);
}

function sql_fetch_array( $qu, $row) {
    return pg_fetch_array( $qu, $row);
}

function sql_nextval($sequence) {
    $qu = sql_exec("select nextval('".sql_escape($sequence)."')");
    if( $qu === false) return false;
    $row = sql_fetch_array( $qu, 0);
    return intval($row["nextval"]);
}

function ff_getnewprojects($limit = 10) {
    $limit = (int)$limit;

    $sql = 'select * from projects where parent is null '.
        'order by id desc limit '.$limit;
    $qu = sql_exec( $sql );

    if( $qu === false ) return private_dberr();

    $projects = array();
    $num_prj = sql_numrows( $qu );
    for( $i = 0; $i < $num_prj; $i++ ) {
        $row = sql_fetch_array( $qu, $i );
        $projects['p'.$row['id']] = private_makeprojectrecord( $row );
    }

    return array( 0, $projects );
}

function ff_findprojects($searchkeyword, $sort, $limit,$offset) {
	if (!$searchkeyword) { 
		$qustring = "select * from projects where parent is null";
	} else {
		$qustring = "select * from projects where parent is null and ".
            "(lower(name) like '%".
            scrub(strtolower($searchkeyword))."%'".
			"or lower(reqmts) like '%".scrub(strtolower($searchkeyword))."%')";
	}

	if ($sort) $sort = "order by ".scrub($sort);
	else {
        list($rc,$currencies) = ff_currencies();
        if( $rc) {
            $sort = "order by currency_value(bounty,'CAD') desc";
        } else {
            $sort = "order by 0";
            foreach( $currencies as $code => $currency) {
                $sort .= "+currency_value(bounty,'$code')*".
                    "$currency[exchange_rate]";
            }
            $sort .= " desc";
        }
    }

	if ($limit) $limit = "limit ".intval($limit);
	else $limit ="limit 200";

	if ($offset) $offset = "offset ".intval($offset);
	else $offset = '';


	$qustring .= " $sort $limit $offset";
	$qu = sql_exec($qustring);

    if( $qu === false) return private_dberr();
	if(sql_numrows($qu)==0) 
		return array(2,"no such project");

    $projects = array();
	for ($i=0;$i<sql_numrows($qu);$i++) {
		$row = sql_fetch_array($qu,$i);
        $projects["p$row[id]"] = private_makeprojectrecord( $row);
	}
	return array(0,$projects);
}

function ff_suggestusername()
{
    $username = sql_nextval( "username_seq");
    if( $username === false) return private_dberr();
    return array(0,"u$username");
}

function private_encrypt_password( $password)
{
    $salt = substr(sha1(time().$password),0,10)."/";
    $encpwd = $salt.sha1("$salt$password");

    // If the password seems easy to guess, make a note of it.
    if( strlen($password) < 8 || ereg("^[0-9]*$",$password) ||
        ereg("^[a-zA-Z]*$",$password)) {
        $encpwd .= ":easy";
    }

    return $encpwd;
}

function ff_createmember( $username, $password, $name='', $email='')
{
    if( ereg("[^a-zA-Z0-9]", $username))
        return array(4,"Invalid username: $username");

    if( $username === '') {
        list($rc,$username) = ff_suggestusername();
        if( $rc) return array($rc,$username);
    }

    $encpwd = private_encrypt_password( $password);

    $qu = @sql_exec( "insert into members ".
        "(username,signedup,password,name,email) values ".
        "('$username',".time().",'".sql_escape($encpwd).
        "','".sql_escape($name)."','".sql_escape($email)."')");
    if( $qu === false) {
        // Check whether the given username is taken
        $qu = sql_exec("select * from members ".
            "where lower(username)='".strtolower($username)."'");
        if( $qu === false) return private_dberr();
        if( sql_numrows( $qu) > 0) {
            return array(3,"Username already exists: $username");
        }
        // Check whether the given email address is taken
        $qu = sql_exec("select * from members where ".
            "lower(email)='".sql_escape(strtolower($email))."'");
        if( $qu === false) return private_dberr();
        if( sql_numrows( $qu) > 0) {
            return array(6,"Email address already exists: $email");
        }
        return private_dberr();
    }
    return array(0,$username);
}

$GLOBALS["PRIVATE_MEMBER_CACHE"] = array();

function ff_getmemberinfo( $username_or_email)
{
    if( strpos($username_or_email,"@")) {
        $column = "lower(email)";
        $value = strtolower($username_or_email);
    } else {
        $column = "username";
        $value = scrub( $username_or_email);
    }

    if( isset( $GLOBALS["PRIVATE_MEMBER_CACHE"][$value]))
        return array(0,$GLOBALS["PRIVATE_MEMBER_CACHE"][$value]);

    $qu = sql_exec( "select * from members ".
        "where $column='".sql_escape($value)."'");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0)
        return array(2,"No such member: $username_or_email");

    $row = sql_fetch_array( $qu, 0);

    // Strip off the 'easy' indicator if it exists.
    $encpwd = $row["password"];
    $easypwd = 0;
    if( substr( $encpwd, -5) === ':easy') {
        $encpwd = substr( $encpwd, 0, -5);
        $easypwd = 1;
    }

    $current_sponsorships = '';
    if( ereg("[1-9]",$row["total_sponsorships"])) {
        // Compute the current sponsorships
        $qu = sql_exec("select sum_money(amount) from member_donations ".
            "where member='".sql_escape($row["username"])."'");
        if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
        $row2 = sql_fetch_array($qu,0);
        $current_sponsorships = "$row2[sum_money]";
    }

    $memberinfo = array(
        "username" => $row["username"],
        "auth" => $row["auth"],
        "encpwd" => $encpwd,
        "easypwd" => $easypwd,
        "signedup" => intval($row["signedup"]),
        "name" => $row["name"],
        "email" => $row["email"],
		"reserve"=> $row["reserve"],
        "prefcharity" => intval($row["prefcharity"]),
        "current_sponsorships" => $current_sponsorships,
        "total_sponsorships" => $row["total_sponsorships"],
        "total_earnings" => $row["total_earnings"],
        "total_deductions" => $row["total_deductions"],
        "subscription_amount" => $row["subscription_fee"],
        "subscription_time" => intval($row["subscription_time"]),
        "subscription_freq" => $row["type"],
        "last_subscr_time" => intval($row["last_subscr_time"]),
        "payment_due" => intval($row["payment_due"]));

    $GLOBALS["PRIVATE_MEMBER_CACHE"][$row["username"]] = $memberinfo;
    $GLOBALS["PRIVATE_MEMBER_CACHE"][strtolower($row["email"])] = $memberinfo;

    return array(0,$memberinfo);
}

function ff_setmemberinfo( $username_or_email, $name=false,
    $email=false, $password=false, $prefcharity=false)
{
    // Make sure the user actually exists
    list($rc,$memberinfo) = ff_getmemberinfo( $username_or_email);
    if( $rc) return array($rc,$memberinfo);

    $jobs = array();
    if( $name !== false) $jobs[] = "name='".sql_escape($name)."'";
    if( $email !== false) $jobs[] = "email='".sql_escape($email)."'";
    if( $password !== false) {
        $encpwd = private_encrypt_password( $password);
        $jobs[] = "password='".sql_escape($encpwd)."'";
        $jobs[] = "resetcode=''";
    }
    if( $prefcharity !== false) {
        $prefcharity = intval($prefcharity);

        if( $prefcharity != 0) {
            // Make sure that the selected charity exists
            list($rc,$charities) = ff_getcharities();
            if( !isset($charities[$prefcharity])) {
                return array(2,"No such charity: $prefcharity");
            }
        }
        $jobs[] = "prefcharity=$prefcharity";
    }

    if( sizeof($jobs)) {
        $qu = sql_exec( "update members set ".join(",",$jobs).
            " where lower(username)='".
            sql_escape(strtolower($memberinfo["username"]))."'");
        if( $qu === false) return private_dberr();
    }

    // Clear the cache
    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][$memberinfo["username"]]);
    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][strtolower($memberinfo["email"])]);

    return array(0,"Success");
}

function ff_ensureprojectuserdata( $projectid, $username )
{
    $nid = (int)substr ( $projectid, 1 );
    
    // Check for project existance:
    $qu = sql_exec( 'select id from projects where id='.$nid );
    if( $qu === false ) return private_dberr();
    if( sql_numrows( $qu ) != 1 )
        return array( 2, 'No such project' );

    $exists = false;

    $qu = sql_exec( "select count(*) from project_user_data where project=".$nid." and member='".sql_escape( $username )."'" );
    if( $qu !== false ) {
        $row = sql_fetch_array( $qu, 0 );
        $exists = (bool)$row['count'];
    }

    if( !$exists )
        $qu = sql_exec( "insert into project_user_data (project, member) values (".$nid.", '".sql_escape( $username )."')" );
    
    return array( 0, 'Success' );
}

function ff_hasvoted( $username, $projectid )
{
    $nid = (int)substr ( $projectid, 1 );
    
    $qu = sql_exec( "select COUNT(*) from project_user_data where".
        " member='".sql_escape($username)."' and project=".$nid." and has_voted>0" );
    if ( $qu === false ) return private_dberr();
    $row = sql_fetch_array( $qu, 0 );
    
    return array(0,(bool)$row['count']);
}

function ff_setvote( $username, $projectid, $value )
{
    $nid = (int)substr ( $projectid, 1 );
    $value = (bool)$value;
    $old_value = ff_hasvoted( $username, $projectid );
    
    // Sanity check:
    if ($value != $old_value[1])
    {
        list( $rc, $msg ) = ff_ensureprojectuserdata( $projectid, $username );
        if( $rc != 0 )
            return array( 0, 'Success' );
        
        $qu = sql_exec( "update project_user_data set has_voted=".($value ? 1 : 0)." where project=".$nid." and member='".sql_escape( $username )."'" );
        if( $qu === false) return private_dberr();
    }
    
    return array( 0, 'Success' );
}

function ff_setfundingvote( $username, $projectid, $vote_for_more ) {
    $nid = (int)substr ( $projectid, 1 );
    $value = (bool)$vote_for_more;
    
    // Check project existance:
    list( $rc, $msg ) = ff_ensureprojectuserdata( $projectid, $username );
    if( $rc != 0 )
        return array( $rc, $msg );
    
    // Check if one can vote (funding_goal_orig > 0):
    $qu = sql_exec( "select funding_goal_orig from projects where id=".$nid );
    if( $qu === false ) return private_dberr();
    $row = sql_fetch_array( $qu, 0 );
    if( !( $row[0] > 0 ) )
        return array( 5, 'Can\'t vote at given time' );

    // Retrive old vote:
    $qu = sql_exec( "select prev_funding_vote from project_user_data where project=".$nid." and member='".sql_escape( $username )."'" );
    if( $qu === false ) return private_dberr();
    $row = sql_fetch_array( $qu, 0 );
    $prev_value = $row[0];

    // Sanity check:
    if (($value ? 1 : -1) != (int)$prev_value)
    {
        // Update project:
        $qu = sql_exec( "update projects ".
            "set funding_goal=mult_round_money( funding_goal, 1 ".
            ($value ? '*' : '/').
            " ( 1.02 + 0.18 * exp( -0.02 * funding_votes ) ) )".
            ((int)$prev_value == 0 ? ", funding_votes=funding_votes+1":"").
            " where id=".$nid );
        if( $qu === false ) return private_dberr();

        $qu = sql_exec( "update project_user_data set prev_funding_vote=".($value ? 1 : -1)." where project=".$nid." and member='".sql_escape( $username )."'" );
        if( $qu === false ) return private_dberr();
    }
    
    return array( 0,'Success' );
}

function ff_setfundinggoal( $username, $projectid, $amount ) {
    $nid = (int)substr ( $projectid, 1 );
    $value = (int)$amount > 0 ? $amount : null;
    
    // Check if funding_goal_orig is 0 and that the user is the project lead:
    $qu = sql_exec( "select funding_goal_orig from projects where id=".$nid." and creator='".sql_escape( $username )."'" );
    if( $qu === false ) return private_dberr();
    if( sql_numrows( $qu ) == 0 )
        return array( 2, 'Project not found' );
    $row = sql_fetch_array( $qu, 0 );
    if( $row[0] > 0 )
        return array( 5, 'Can\'t change funding amount' );
    
    $qu = sql_exec( "update projects set funding_goal_orig='".$value."', funding_goal='".$value."' where id=".$nid );
    if( $qu === false ) return private_dberr();
    
    return array( 0, 'Success' );
}

function ff_updateexchangerates()
{
    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);
    foreach( $currencies as $name => $details) {
        if( $name === "USD" || $name === "FFC") continue;

        $url = "http://www.newyorkfed.org/rss/feeds/fxrates12_$name.xml";
        $xml = file_get_contents( $url);
        if( $xml === false) return array(1,"Read error: $url");

        if( ereg("([0-9.]+) *$name *= *1 *USD",$xml,$regs)) {
            $rate = "round(1.0/".$regs[1].",8)";
        } else if( ereg("([0-9.]+) *USD *= *1 *$name",$xml,$regs)) {
            $rate = $regs[1];
        } else {
            return array(1,"Parse error: $url");
        }

        $qu = sql_exec("update currencies ".
            "set exchange_rate=$rate,updated=".time().
            " where code='$name'");
        if( $qu === false) return private_dberr();
    }
    return array(0,"Successfully updated ".(sizeof($currencies)-1)." exchange rates.");
}

function ff_tallyvotes()
{
    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);

    $qu = sql_exec("select pud.project,sum(case when m.subscription_fee~'[1-9]' then 2 else 1 end) as votes from project_user_data as pud left join members as m on pud.member = m.username where pud.has_voted>0 group by pud.project order by votes desc,project limit 10");
    if( $qu  === false) return private_dberr();

    $featured = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        list($rc,$projectinfo) = ff_getprojectinfo("p$row[project]");
        if( $rc) return array($rc,$projectinfo);

        $featured[] = array(
            "id" => intval($row["project"]),
            "votes" => intval($row["votes"]),
            "name" => $projectinfo["name"],
            "abstract" => ereg_replace("\n.*","",$projectinfo["reqmts"]),
            "direct_bounty" => $projectinfo["direct_bounty"],
            "bounty" => $projectinfo[bounty]);
    }

    $qu = sql_exec("begin");
    if( $qu  === false) return private_dberr();

    $qu = sql_exec("delete from featured_projects");
    if( $qu  === false) return private_dberr(1);

    foreach( $featured as $project) {
        $usd_value = "0";
        foreach( $currencies as $code => $currency) {
            $usd_value .= "+".currency_value($project['bounty'],$code).
                "*$currency[exchange_rate]";
        }

        $qu = sql_exec("insert into featured_projects ".
            "(id,votes,name,abstract,direct_bounty,bounty,usd_value) values ".
            "($project[id],$project[votes],'".sql_escape($project["name"]).
            "','".sql_escape($project["abstract"]).
            "','$project[direct_bounty]','$project[bounty]',$usd_value)");
        if( $qu  === false) return private_dberr(1);
    }

    return private_commit();
}

function ff_checkresetcode( $username_or_email, $code)
{
    if( strpos($username_or_email,"@")) {
        $column = "lower(email)";
        $value = strtolower($username_or_email);
    } else {
        $column = "username";
        $value = scrub( $username_or_email);
    }

    $qu = sql_exec( "select resetcode from members ".
        "where $column='".sql_escape($value)."'");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0)
        return array(2,"No such member: $username_or_email");

    $row = sql_fetch_array( $qu, 0);

    if( !ereg( "^([^:]*):([0-9]*)$", $row["resetcode"], $parts))
        return array(2,"There is no reset code.");

    if( $code !== $parts[1]) {
        return array(4,"Incorrect reset code.");
    }

    $time = intval($parts[2]);
    if( $time < time() - 60*60*12) {
        return array(5,"The reset code has expired.");
    }

    return array(0,"Success");
}

function ff_getresetcode( $username_or_email)
{
    // Make sure the user actually exists
    list($rc,$memberinfo) = ff_getmemberinfo( $username_or_email);
    if( $rc) return array($rc,$memberinfo);

    // Invent a new reset code
    $code = substr(sha1($username_or_email.mt_rand( 0, 1000000000)),4,20);

    $qu = sql_exec( "update members set resetcode='$code:".time()."' ".
        "where lower(username)='".
        sql_escape(strtolower($memberinfo["username"]))."'");
    if( $qu === false) return private_dberr();

    return array(0,$code);
}

function ff_setsubscription($username, $amount, $freq, $sponsorships=false)
{
    $amount = scrubmoney($amount);

    if( $amount !== false && strpos($amount,"-") !== false)
        return array(4,"Invalid monthly sponsorship amount: $amount");

    if( $freq !== 'monthly' && $freq !== 'weekly') {
        return array(4,"Invalid payment frequency: $freq");
    }

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("select * from members where ".
        "lower(username)='".sql_escape(strtolower($username))."' for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        sql_exec("rollback");
        return array(2,"No such member: $username");
    }
    $row = sql_fetch_array( $qu, 0);

    $due = intval($row["payment_due"]);
    if( !ereg("[1-9]",$row["subscription_fee"])) {
        // This is a new subscription
        $time = time();
        $due = $time;
    } else {
        $time = intval($row["subscription_time"]);
    }

    $qu = sql_exec("update members set ".
        ($amount===false?"":"subscription_fee='$amount',").
        "subscription_time=$time,last_subscr_time=null,".
        "payment_due=$due, type='$freq' where lower(username)='".
        sql_escape(strtolower($username))."'");
    if( $qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][$username]);
    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][strtolower($row["email"])]);

    if( $sponsorships !== false) {
        $qu = sql_exec("delete from subscriptions where ".
            "username='".sql_escape($username)."'");
        if( $qu === false) return private_dberr(1);

        $seq = 0;
        foreach( $sponsorships as $id => $n) {
            $qu = sql_exec("insert into subscriptions ".
                "(username,projectid,sequence,amount) values ".
                "('".sql_escape($username)."',".intval(substr($id,1)).",".
                (++$seq).",'".scrubmoney($n)."')");
            if( $qu === false) return private_dberr(1);
        }
    }

    if( $amount !== false) {
        $factor = (ereg("[1-9]",$amount) ? 2 : 1);
        $ffid = sql_nextval("fix_factors_queue_seq");
        if( $ffid === false) return private_dberr(1);
        $qu = sql_exec("insert into fix_factors_queue (id,member,factor) ".
            "values ($ffid,'".sql_escape($username)."',$factor)");
        if( $qu === false) return private_dberr(1);
    }

    $rc = private_commit();
    if( $rc[0]) return $rc;

    // If these don't succeed, don't worry about it.
    // They get called from a cron job anyway.
    ff_fixfactors();

    return array(0,"Success");
}

function ff_cancelsubscription($username) {
    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("update members set ".
        "subscription_fee=null ".
        "where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    $ffid = sql_nextval("fix_factors_queue_seq");
    if( $ffid === false) return private_dberr(1);
    $qu = sql_exec("insert into fix_factors_queue (id,member,factor) ".
        "values ($ffid,'".sql_escape($username)."',1)");
    if( $qu === false) return private_dberr(1);

    $rc = private_commit();
    if( $rc[0]) return $rc;

    // Don't worry if this doesn't succeed -- it's called from a cron job.
    ff_fixfactors();

    return array(0,"Success");
}

//shows details about the projects this member is subscribed to
function ff_showsubscriptions($username) {
    $subscriptions = array();

    $qu=sql_exec("select * from subscriptions where ".
        "username = '".sql_escape($username)."' order by sequence");
	if ($qu===false) return private_dberr();

	for ($i=0;$i<sql_numrows($qu);$i++) {
		$row = sql_fetch_array($qu,$i);
		$subscriptions["p$row[projectid]"] = array(
            'id' => "p$row[projectid]",
            'amount' => $row['amount']);
	}

	return array(0,$subscriptions);
}

function ff_checkpassword( $uname_or_email_or_encpwd, $password)
{
    if( strpos( $uname_or_email_or_encpwd, "/") === false) {
        list($rc,$memberinfo) = ff_getmemberinfo($uname_or_email_or_encpwd);
        if( $rc) return array($rc,$memberinfo);
        $encpwd = $memberinfo["encpwd"];
    } else {
        $encpwd = $uname_or_email_or_encpwd;
    }

    if( !ereg( "^(.*/)(.*)$", $encpwd, $args))
        return array(4,"Invalid encrypted password: $encpwd");
    if( $args[2] !== sha1("$args[1]$password"))
        return array(5,"Wrong Password");
    return array(0,"Success");
}

// This function does not lock any existing tables.
//
function private_createattachments( $attachments, $postid=0, $projectid=0) {
    if(is_array($attachments)) {
        if( $postid == 0) {
            // We need a post ID even if there is no such post.
            $postid = sql_nextval("posts_id_seq");
            if( $postid === false) return private_dberr();
        }

        // Make sure the attachments directory exists
        @mkdir( "$GLOBALS[DATADIR]/attachments");
        $destdir = "$GLOBALS[DATADIR]/attachments/$postid";
        @mkdir( $destdir);

        $i=0;
        foreach ($attachments as $attachment) {
            $i++;
            if( @copy($attachment["pathname"],"$destdir/$i") === false)
                return array(4, "Can't rename file: $attachment[pathname]");
            $filesize = intval(filesize("$destdir/$i"));
            $qu = sql_exec("insert into post_attachments".
                "(postid,projectid,seq,filename,filesize,description)".
                "values ($postid,".($projectid?$projectid:'null').",$i,'".
                sql_escape($attachment['filename']).
                "',$filesize,'".sql_escape($attachment['description'])."')");
            if( $qu === false) return private_dberr();
        }
    }

    return array(0,"Success");
}

// This function might lock the following tables in this order:
//
// <posts>
// <projects>
// <duties>
//
function ff_createpost( $topicid, $subject, $body, $parent='',
    $owner='', $name='',$attachments=false, $watchthread=1, $url='')
{
    $id = sql_nextval("posts_id_seq");
    if( $id === false) return private_dberr();

    if( $parent) {
        $ancestry = private_get_ancestry( $parent);
        if( $ancestry === false) return private_dberr();
        $ancestry .= "$parent/";
    } else {
        $ancestry = scrub($topicid)."/";
    }

    $depth = strlen(ereg_replace("[^/]","",$ancestry))-1;

    $status = (strpos($body,"\n/-/-/-/-/-begin-diff-/-/-/-/-/\n")===false)
        ?"null":"'pending'";

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();
    $attachmentsize=sizeof($attachments);
    $qustring = "insert into posts ".
        "(id,time,owner,ownername,ancestry,depth,descendants,".
        "subject,body,status,numattachments) ".
        "values ".
        "($id,".time().",'".sql_escape($owner)."','".sql_escape($name).
        "','$ancestry',$depth,0,'".sql_escape($subject)."','".
        sql_escape($body)."',$status,$attachmentsize)";
    $qu = sql_exec($qustring);

    if( $qu === false) return private_dberr(1);

    list($rc,$err) = private_createattachments( $attachments, $id);
    if( $rc) return private_dberr( $rc, $err);

    $thread = ereg_replace("^([^/]*/[^/]*)/.*$","\\1","$ancestry$id/");

    if( $owner !== '') {
        if( $watchthread) {
            list($rc,$err) = al_createwatch("thread-$thread", $owner);
            if( $rc) return private_dberr($rc,$err);
        } else {
            list($rc,$err) = al_destroywatch2("thread-$thread", $owner);
            if( $rc) return private_dberr($rc,$err);
        }
    }

    // Update all of the ancestors' descendant counts
    while( ereg("^(.*/)([^/]*)/$",$ancestry,$args)) {
        $qu = sql_exec("update posts set descendants=descendants+1 ".
            "where id=".intval($args[2]));
        if( $qu === false) return private_dberr(1);

        $ancestry = $args[1];
        $depth --;
    }

    if( $status === "'pending'") {
        // This post is a change proposal.  We need to create a duty
        // for the project lead to make a decision on the proposal.

        // Get the numeric project ID
        // HACK - this violates encapsulation
        $nid = intval(ereg_replace("^[^0-9]*","",$topicid));

        list($rc,$deadline) = private_createduty(
            $nid, "change proposal", $id, 129600);
        if( $rc) return private_dberr($rc,$deadline);
    }

    $url .= (strpos($url,"?") !== false ? "&" : "?")."post=$id";

    if( substr($topicid,0,6) == 'reqmts') {
        $project = substr($topicid,6);
        list($rc,$projinfo) = ff_getprojectinfo($project);
        if( $rc == 0) {
            if( $status === "'pending'") {
                // Notify the project lead of the change proposal
                $macros = array(
                    "projectname" => $projinfo["name"],
                    "deadline" => date("D F j, H:i:s T",$deadline),
                );
                $tag = ($deadline?"newduty2":"newduty");
                $rc = al_triggerevent( "lead:$project",
                    "$url&requser=$projinfo[lead]",
                    "$tag-changeproposal", $macros);
                if( $rc[0]) return private_dberr($rc[0],$rc[1]);

                // Trigger a project news event
                $macros = array(
                    "projectname" => $projinfo["name"],
                );
                $rc = al_triggerevent(
                    "watch:$project-news,watch:thread-$thread\\".
                    "member:".scrub($owner).",lead:$project",
                    $url, "pnews-changeproposal", $macros, 2);
                if( $rc[0]) return private_dberr($rc[0],$rc[1]);
            } else {
                // Trigger a project news event
                $macros = array(
                    "projectname" => $projinfo["name"],
                );
                $rc = al_triggerevent(
                    "watch:$project-news,watch:thread-$thread\\".
                    "member:".scrub($owner),
                    $url, "pnews-newpost", $macros, 5);
                if( $rc[0]) return private_dberr($rc[0],$rc[1]);
            }
        }
    } else {
        // Inform anybody who is watching the thread
        $rc = al_triggerevent( "watch:thread-$thread\\member:".scrub($owner),
            $url, "forumpost", array(), 5);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    }

    return private_commit($id);
}

//topicid: topic of discussion, ie reqmts$id, proj$id, spec$id etc..
//parent: the ID whose children, grandchildren..etc you want to fetch, 
//parent: if false, then this means you want to fetch the top level posts
//depth: the number of levels (generations) down you want to fetch
//returns array of posts, look at private_makepostrecord to see contents of each element of 
//the posts array
function ff_getposts( $topicid=false, $parent=false, $depth=false)
{
    $ancestry = scrub($topicid)."/";
    if( $parent !== false) {
        $parent = scrub($parent);
        $ancestry = private_get_ancestry( $parent);
        if( $ancestry === false) return private_dberr();
        $ancestry .= "$parent/";
    }

    $posts = array();
    $toplevel = array();

    if( $depth === false) {
        $qu = sql_exec("select * from posts where".
            " ancestry like '$ancestry%' order by id");
        if( $qu === false) return private_dberr();

        private_add_post( &$posts, &$toplevel, $qu);
    } else {
        $mindepth = strlen(ereg_replace("[^/]","",$ancestry))-1;

        for( $i=$mindepth; $i < $mindepth+$depth; $i++) {
            $qu = sql_exec("select * from posts where".
                " (depth||'/'||ancestry) like '$i/$ancestry%' ".
                " order by id");
            if( $qu === false) return private_dberr();
            if( sql_numrows( $qu) == 0) break;

            private_add_post( &$posts, &$toplevel, $qu);
        }
    }

    return array(0,$toplevel);
}

function private_add_post( &$posts, &$toplevel, $qu)
{
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $id = intval($row["id"]);
        $parent = private_get_parent($row["ancestry"]);

        $posts[$id] = private_makepostrecord( $row);

        if( isset( $posts[$parent])) {
            $posts[$parent]["children"][$id] =& $posts[$id];
        } else {
            $toplevel[$id] =& $posts[$id];
        }
    }
}
//returns post array
//id: id of post 
//topicid: id of topic (ie reqmts$id, proj$id, spec$id ..etc)
//ancestors: array containing the IDs of ancestors
//ie $post['ancestors'][0] is the ID of the most distance 
//ancestor, $post['ancestors'][1] is the child of $post['ancestors']
//time: time of postage
//ownername: name of writer of post 
//owner: id of writer of post
//parent: id of post whose children, grandchildren are to be fetched
//subject: subject of post
//body: body of post
//status: status of post (ie accepted, rejected, pending or null)
//descendants: array containing the IDs of the descendants
function ff_getpostinfo( $postid)
{
    $qu = sql_exec("select * from posts where id=".intval($postid));
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) return array(2,"No such post: $postid");
    $row = sql_fetch_array( $qu, 0);
    return array(0,private_makepostrecord( $row));
}

$GLOBALS["PRIVATE_POST_ANCESTRY"] = array();

function private_get_ancestry( $post) {
    if( !isset( $GLOBALS["PRIVATE_POST_ANCESTRY"][$post])) {
        list($rc,$err) = ff_getpostinfo( $post);
        if( $rc) return false;
    }
    return $GLOBALS["PRIVATE_POST_ANCESTRY"][$post];
}

function private_makepostrecord( $row)
{
    $GLOBALS["PRIVATE_POST_ANCESTRY"][intval($row["id"])] = $row["ancestry"];
    $ancestors = ereg_replace("^[^/]*/","",$row["ancestry"]);
    $ancestors = $ancestors===''?array():explode('/',substr($ancestors,0,-1));
    $attachments = null;
    //check if post has attachment files
    if ($row['numattachments']>0) {
        $attachmentqu = sql_exec("select * from post_attachments where postid=$row[id]");
        for ($i=0;$i<sql_numrows($attachmentqu);$i++) {
            $arow = sql_fetch_array($attachmentqu,$i);
            $attachments[] = array('seq' => $arow['seq'],
                                   'filename' => $arow['filename'],
                                   'filesize' => $arow['filesize'],
                                   'description' => $arow['description']
                                  );
        }
    }

    return array(
        "id" => intval($row["id"]),
        "topicid" => ereg_replace("/.*","",$row["ancestry"]),
        "ancestors" => $ancestors,
        "time" => intval($row["time"]),
        "owner" => "$row[owner]",
        "ownername" => "$row[ownername]",
        "parent" => private_get_parent($row["ancestry"]),
        "subject" => "$row[subject]",
        "body" => "$row[body]",
        "status" => "$row[status]",
        "descendants" => intval($row["descendants"]),
        "children" => array(),
        "attachments" => $attachments);
}

function private_get_parent( $ancestry)
{
    return ereg("/([^/]*)/$",$ancestry,$args)?intval($args[1]):0;
}

$GLOBALS["PRIVATE_PROJECT_INFO"] = array();

function ff_saveprojectdraft( $creator, $name, $fundgoal, $reqmts, $draftid=false)
{
    // Silently enforce the maximum allowed value for the PGsql integer:
    if( $fundgoal > 2147483647 )
        $fundgoal = 2147483647;
    
    if( $draftid === false) {
        $id = sql_nextval("projects_id_seq");
        if( $id === false) return private_dberr();

        // Create the new record
        $qu = sql_exec("insert into draft_projects ".
            "(id,modified,creator,name,funding_goal,reqmts)".
            " values ($id,".time().",'".sql_escape($creator)."','".
            sql_escape($name)."','".( (int)$fundgoal )."','".
            sql_escape($reqmts)."')");
        if( $qu === false) return private_dberr();

        return array(0,"p$id");
    }

    $qu = sql_exec( "begin");
    if( $qu === false) return private_dberr();

    $id = substr($draftid,1);

    // Make sure the draft exists
    $qu = sql_exec("select id from draft_projects ".
        "where id=$id and creator='".sql_escape($creator)."' for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0)
        return private_dberr(2,"No such draft: $draftid/$creator");

    // Update the info
    $qu = sql_exec("update draft_projects set modified=".time().
        ",name='".sql_escape($name)."',reqmts='".sql_escape($reqmts)."',".
        "funding_goal='".( (int)$fundgoal )."' where id=$id");
    if( $qu === false) return private_dberr(1);

    return private_commit("p$id");
}

function ff_getprojectdrafts( $creator)
{
    $qu = sql_exec("select * from draft_projects ".
        "where creator='".sql_escape($creator)."' order by modified desc,id");
    if( $qu === false) return private_dberr();
    $drafts = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $drafts["p$row[id]"] = array(
            "id" => "p$row[id]",
            "modified" => intval($row["modified"]),
            "name" => $row["name"],
            "reqmts" => $row["reqmts"],
            "funding_goal" => $row["funding_goal"],
        );
    }
    return array(0,$drafts);
}

function ff_deleteprojectdraft( $draftid, $creator)
{
    $id = intval(substr($draftid,1));
    $qu = sql_exec("delete from draft_projects ".
        "where id=$id and creator='".sql_escape($creator)."'");
    if( $qu === false) return private_dberr();
    return array(0,"Success");
}

// This function may lock the following tables in this order:
//
// <projects>
// <members>
// <duties>
// 
function ff_createproject( $creator, $name,
    $reqmts, $parent='', $attachments=false, $draftid=false,
    $priority='subproject',$lead=false,$allotment=false)
{
    if( $draftid !== false) {
        $nid = intval(substr($draftid,1));
    } else {
        $nid = sql_nextval( "projects_id_seq");
        if( $nid === false) return private_dberr();
    }
    $id = "p$nid";
    $pnid = intval(substr($parent,1));
    $now = time();

    $qu = sql_exec( "begin");
    if( $qu === false) return private_dberr();

    if( $draftid !== false) {
        $drid = intval(substr($draftid,1));

        // Make sure the draft still exists
        $qu=sql_exec("select * from draft_projects where id=$drid for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) == 0) return private_dberr(2,"No such draft");

        // Delete the draft
        $qu = sql_exec("delete from draft_projects where id=$drid");
        if( $qu === false) return private_dberr(1);
    }

    // If there's a parent, get a lock on it.
    $root = $nid;
    $zeroes = "";
    if( $parent !== '') {
        $qu = sql_exec("select root,bounty_portions ".
            "from projects where id=$pnid for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) != 1)
            return private_dberr(2,"No such parent project: $parent");
        $row = sql_fetch_array( $qu, 0);
        $root = intval($row["root"]);
        $zeroes = ereg_replace("[^,]+","",$row["bounty_portions"]).",";
    }

    list($rc,$err) = private_createattachments( $attachments, 0, $nid);
    if( $rc) return private_dberr($rc,$err);

    if( $lead === false) $lead = $creator;
    if( $name === '') $name = "Bug p$nid";

    $qu = sql_exec( "insert into projects ".
        "(id,parent,creator,time,name,reqmts,bounty,direct_bounty,allotment,".
        "bounty_portions,held_amounts,priority,root,lead,status,".
        "numattachments) values ".
        "($nid,".($parent===''?"null":$pnid).
        ",'".sql_escape($creator)."',$now,'".sql_escape($name).
        "','".sql_escape($reqmts)."','','',".
        ($allotment===false?"null":intval($allotment)).
        ",'$zeroes','$zeroes',".
        "'".sql_escape($priority)."',$root,'".sql_escape($lead).
        "','pending',".(is_array($attachments)?sizeof($attachments):0).")");
    if( $qu === false) return private_dberr(1);

    // Make sure the project lead is watching the project
    $rc = al_createwatch( "p$nid-news", $lead);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    if( $lead !== $creator) {
        // The creator should be watching it too.
        $rc = al_createwatch( "p$nid-news", $creator);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    }

    if( $parent !== '') {
        list($rc,$projinfo)=ff_getprojectinfo($parent);
        if( $rc) return array($rc,$projinfo);

        if( $allotment === false) {
            list($rc,$deadline) = private_createduty(
                $pnid, "new-subproject", $nid, 129600);
            if( $rc) return private_dberr($rc,$deadline);

            // Inform the parent project lead of his allotment duty
            $macros = array(
                "projectname" => $name,
                "parentname" => $projinfo["name"],
                "deadline" => date("D F j, H:i:s T",$deadline),
            );
            $url = projurl($parent,"tab=subprojects&requser=".
                $projinfo["lead"]);
            $tag = ($deadline?"newduty2":"newduty");
            $rc = al_triggerevent( "lead:$parent", $url,
                "$tag-newsubproject", $macros);
            if($rc[0]) return $rc;
        } else {
            $rc = private_updatechildbounties( $pnid);
            if( $rc[0]) return $rc;
        }

        $macros = array(
            "projectname" => $name,
            "parentname" => $projinfo["name"],
            "requirements" => $reqmts,
        );
        $url = projurl($id);
        $rc = al_triggerevent( "watch:$parent-news\\member:".scrub($creator).
            ($allotment === false ? ",lead:$parent" : ""),
            $url, "pnews-newsubproject", $macros, 3);
        if($rc[0]) return $rc;
    } else {
        // Trigger a "top-level project created" event notification
        $macros = array(
            "projectname" => $name,
            "requirements" => $reqmts,
        );
        $url = projurl($id);
        $rc = al_triggerevent( "watch:news\\member:".scrub($creator),
            $url, "news-newproject", $macros, 3);
        if($rc[0]) return $rc;
    }

    return private_commit($id);
}

function ff_getprojectcolor($projectid) {
    $nid = intval(substr($projectid,1));
    $qu = sql_exec("select * from projects where id=$nid");
    if ($qu === false) return private_dberr();
    $row = sql_fetch_array ($qu,0);

    $color='';
    if ($row['status']=='submitted') $color='FFFF80';
    elseif($row['status']=='accept') $color='FFB050';
    elseif($row['status']=='complete') $color='A0A0FF';
    else  $color='FFFFFF';
    return array(0,$color);
}

function private_makeprojectrecord( $row)
{
    $id = "p$row[id]";

    $GLOBALS["PRIVATE_PROJECT_INFO"][$id] = array(
        "id" => $id,
        "parent" => intval($row["parent"]),
        "root" => intval($row["root"]),
        "name" => $row["name"],
        "reqmts" => $row["reqmts"],
        "reqmts_seq" => intval($row["reqmts_seq"]),
        "priority" => $row["priority"],
        "direct_bounty" => $row["direct_bounty"],
        "indirect_bounty" => $row["indirect_bounty"],
        "allotment" => intval($row["allotment"]),
        "allotted" => ("$row[allotment]"===''?false:true),
        "bounty" => $row["bounty"],
        "creator" => $row["creator"],
        "created" => intval($row["time"]),
        "delete_time" => intval($row["delete_time"]),
        "lead" => $row["lead"],
        "status" => $row["status"],
        "numattachments" => intval($row['numattachments']),
        "payout_time" => intval($row['payout_time']),
        "funding_goal" => $row['funding_goal_orig'] > 0 ? $row['funding_goal'] : 0,
    );

    return $GLOBALS["PRIVATE_PROJECT_INFO"][$id];
}

function ff_getprojectinfo( $id_or_ids) {
    if( !is_array($id_or_ids)) {
        list($rc,$projects) = ff_getprojectinfo( array($id_or_ids));
        if( $rc) return array($rc,$projects);
        if( sizeof($projects) == 0)
            return array(2,"No such project: $id_or_ids");
        return array(0,$projects[$id_or_ids]);
    }

    $lookup = array();
    $projects = array();
    foreach( $id_or_ids as $id) {
        $nid = intval(substr($id,1));
        $id = "p$nid";

        if( isset( $GLOBALS["PRIVATE_PROJECT_INFO"][$id])) {
            $projects[$id] = $GLOBALS["PRIVATE_PROJECT_INFO"][$id];
        } else {
            $lookup[] = $nid;
        }
    }

    if( sizeof($lookup) > 0) {
        sort($lookup);
        $qu = sql_exec( "select *,".
            "subtract_money(bounty,direct_bounty) as indirect_bounty ".
            "from projects where id in (".join(",",$lookup).")");
        if( $qu === false) return private_dberr();

        for( $i=0; $i < sql_numrows( $qu); $i++) {
            $row = sql_fetch_array( $qu, $i);
            $projects["p$row[id]"] = private_makeprojectrecord( $row);
        }
    }

    return array(0,$projects);
}

function ff_getleadprojects($username) {
	$qu=sql_exec("select * from projects ".
        "where lead='".sql_escape($username)."'");
	if ($qu===false) return private_dberr();

    $projects = array();
    for ($i=0;$i<sql_numrows($qu);$i++) {
        $row=sql_fetch_array($qu,$i);
        $projects["p$row[id]"] = private_makeprojectrecord($row);
    }
	return array(0,$projects);
}

function ff_getsponsors( $project, $min_usd_value="1") {
    $min_usd_value = "$min_usd_value";
    if( $min_usd_value === '' || ereg("[^0-9]",$min_usd_value))
        return array(4,"Invalid min_usd_value: $min_usd_value");

    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);

    $usd_value = "0";
    foreach( $currencies as $code => $currency) {
        $usd_value .= "+currency_value(amount,'$code')*".
            "$currency[exchange_rate]";
    }

    $nid = intval(substr($project,1));

    $qu = sql_exec("select member,amount,".
        "round(credits*0.01) as credits,assignee ".
        "from member_donations where project=$nid ".
        "and $usd_value >= $min_usd_value order by $usd_value desc");
    if( $qu === false) return private_dberr();

    $sponsors = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $sponsors[$row["member"]] = array(
            "username" => $row["member"],
            "amount" => $row["amount"],
            "credits" => $row["credits"],
            "assignee" => $row["assignee"]);
    }

    return array(0,$sponsors);
}

function ff_memberdonations($username, $project=false) {
    $qu=sql_exec("select *,round(credits*0.01) as fewer_credits ".
        "from member_donations ".
        "where member='".sql_escape($username)."'".
        ($project===false?'':" and project=".intval(substr($project,1))));
    if ($qu===false) return private_dberr();

    $donations = array();
    for($i=0;$i<sql_numrows($qu);$i++) {
        $row=sql_fetch_array($qu,$i);
        if( $row["amount"] == 0) continue;
        $donations["p$row[project]"] = array(
            "id" => "p$row[project]",
            "amount" => $row["amount"],
            "factor" => intval($row["factor"]),
            "credits" => $row["fewer_credits"],
            "assignee" => $row["assignee"]);
    }
    return array(0,$donations);
}

function ff_getreqmtshistory( $id)
{
    $nid = intval(substr($id,1));

    $qu = sql_exec("select * from project_reqmts_history ".
        "where id=$nid order by time");
    if( $qu === false) return private_dberr();

    $history = array();

    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $history[] = array(
            "time" => intval($row["time"]),
            "subject" => "$row[subject]",
            "postid" => intval($row["postid"]),
            "patch" => "$row[patch]",
            "action" => "$row[action]");
    }

    return array(0,$history);
}

// This function may lock the following tables in this order:
//
// <posts>
// <projects>
// <duties>
// 
function ff_rejectreqmtschange( $username, $id, $subject, $postid)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    list($rc,$row) = private_begin_authorize( $username, $id);
    if( $rc) return array($rc,$row);

    $qu = sql_exec( "insert into project_reqmts_history ".
        "(id,time,subject,postid,action) values ".
        "($nid,".time().",'".sql_escape($subject)."',".
        intval($postid).",'reject')");
    if( $qu === false) return private_dberr(1);

    $qu = sql_exec( "update posts set status='rejected' ".
        "where id=".intval($postid));
    if( $qu === false) return private_dberr(1);

    // Delete the corresponding duty if there is one.
    $rc = private_destroyduty( $nid, "change proposal", intval($postid));
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    $macros = array(
        "projectname" => $row["name"],
    );
    $url = projurl($id,"post=".intval($postid));
    $rc = al_triggerevent( "watch:$id-news\\member:".scrub($username),
        $url, "pnews-changerejected", $macros, 3);
    if( $rc[0]) return $rc;

    return private_commit();
}

function ff_renameproject( $username, $id, $newname)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    list($rc,$row) = private_begin_authorize( $username, $id);
    if( $rc) return array($rc,$row);

    $qu = sql_exec( "update projects set name='".sql_escape($newname).
        "' where id=$nid");
    if( $qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_PROJECT_INFO"][$id]);

    return private_commit();
}

// This function schedules the given project for deletion.
// The deletion time is either in one hour or in four days, depending
// on whether anybody other than the project lead is watching the project.
function ff_deleteproject( $username, $id)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    list($rc,$row) = private_begin_authorize( $username, $id);
    if( $rc) return array($rc,$row);

    // Make sure the project is in pending status
    if( $row["status"] !== 'pending')
        return private_dberr(8,"Only pending projects can be deleted");

    // Make sure the project has no child projects
    $qu = sql_exec("select id from projects where parent=$nid limit 1");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) > 0)
        return private_dberr(8,"Can't delete.  Delete subprojects first");

    // By default, delete the project in one hour.
    $now = time();
    $deltime = $now + 3600;  // In one hour by default

    // If anyone is watching this project or if it has any direct sponsors
    // then the deletion time should be in 4 days.
    $qu = sql_exec("select eventid from watches ".
        "where eventid='$id-news' and username != '".
        sql_escape($username)."' limit 1");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) > 0) $deltime = $now + 3600*24*4;
    else {
        $qu = sql_exec("select member from member_donations ".
            "where project=$nid and amount~'[1-9]' limit 1");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) > 0) $deltime = $now + 3600*24*4;
    }

    // Mark the project for deletion
    $qu = sql_exec("update projects set delete_time=$deltime where id=$nid");
    if( $qu === false) return private_dberr(1);

    // Inform anyone watching the project
    $macros = array(
        "projectname" => $row["name"],
        "deltime" => date("D F j, H:i:s T",$deltime),
    );
    $url = projurl($id);
    $rc = al_triggerevent( "watch:$id-news\\member:".scrub($username),
        $url, "pnews-deletingproject", $macros);
    if($rc[0]) return $rc;

    return private_commit();
}

// This function cancels the deletion of the given project.
function ff_canceldeleteproject( $username, $id)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    if( "$username" === '') {
        return private_dberr(5,"You are not logged in.");
    }

    $qu = sql_exec( "select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such project: $id");
    }
    $row = sql_fetch_array( $qu, 0);

    // Make sure the project is scheduled for deletion
    if( !intval($row["delete_time"]))
        return private_dberr(8,"Project not scheduled for deletion");

    $qu = sql_exec("update projects set delete_time=null where id=$nid");
    if( $qu === false) return private_dberr(1);

    // Inform anyone watching the project
    $macros = array(
        "projectname" => $row["name"],
        "canceller" => $username,
    );
    $url = projurl($id);
    $rc = al_triggerevent( "watch:$id-news\\member:".scrub($username),
        $url, "pnews-canceldeletingproject", $macros);
    if($rc[0]) return $rc;

    return private_commit();
}

//sets the allotment value for child 
//username: the logged in user
//parentid: id of parent project
//childid: id of child project
//allotment: permille, an integer between 0 and 1000
function ff_setallotment($username,$parentid,$childid,$allotment) {
    $parentnid = intval(substr($parentid,1));
    $childnid = intval(substr($childid,1)); 
    $allotment = intval($allotment);

    list($rc,$row) = private_begin_authorize( $username, $parentid);
    if( $rc) return array($rc,$row);

    $qu = sql_exec("update projects ".
        "set allotment=$allotment where id=$childnid"); 
    if ($qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_PROJECT_INFO"]["p$childnid"]);

    $rc = private_updatechildbounties( $parentnid, $row["bounty_portions"]);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    // Delete the bounty allotment duty if there is one.
    $rc = private_destroyduty( $parentnid, "new-subproject", $childnid);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    return private_commit();
}

function ff_setpriority($username,$parentid,$childid,$priority) {
    $childnid = intval(substr($childid,1));

    list($rc,$row) = private_begin_authorize( $username, $parentid);
    if( $rc) return array($rc,$row);

    $qu = sql_exec("update projects ".
        "set priority='".sql_escape($priority)."' where id=$childnid");
    if ($qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_PROJECT_INFO"]["p$childnid"]);

    return private_commit();
}

// This function must be called in a transaction.
// It acquires the following lock:
//   <projects>
function private_adjust_project_bounty( $nid, $change)
{
    // Make sure the project isn't already complete
    $qu = sql_exec( "select *,add_money(subtract_money(direct_bounty,".
        "regexp_replace(held_amounts,'.*,','')),'$change') as margin ".
        "from projects where id=$nid for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) {
        return array(2,"No such project: p$nid");
    }
    $row = sql_fetch_array( $qu, 0);
    if( $row["status"] == 'complete') {
        return array(5,"Can't sponsor complete project: p$nid");
    }

    // If the margin has any negative currencies, that means that somebody
    // is trying to withdraw more money than is allowed by the hold
    // (at least in that currency).
    if( strpos( $row["margin"], "-") !== false) {
        return array(8,"Can't extract funds due to a hold.");
    }

    $qu = sql_exec( "update projects set ".
        "bounty=add_money(bounty,'$change'),".
        "bounty_portions=regexp_replace(bounty_portions,'[^,]*$',".
            "add_money(direct_bounty,'$change')),".
        "direct_bounty=add_money(direct_bounty,'$change') where id=$nid");
    if( $qu === false) return private_dberr();

    unset($GLOBALS["PRIVATE_PROJECT_INFO"]["p$nid"]);

    return private_updatechildbounties( $nid);
}

function private_lock_from_root( $projectid)
{
    $qu = sql_exec("select parent from projects where id=$projectid");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0) return array(2,"No such project: $projectid");
    $row = sql_fetch_array( $qu, 0);

    $depth = 0;
    if( intval($row["parent"])) {
        $rc = private_lock_from_root( intval($row["parent"]));
        if( $rc[0]) return $rc;
        $depth = 1 + $rc[1];
    }

    $qu = sql_exec("select id from projects where id=$projectid for update");
    if( $qu === false) return private_dberr();

    return array(0,$depth);
}

// Only call this in a transaction while holding a lock on all ancestors.
//
// This function assumes that the held amounts on all children are
// already correct.  It updates the held amounts for the given project,
// and for all ancestors (as needed).
function private_update_held_amounts( $projectid)
{
    $qu = sql_exec("select held_amounts,parent from projects ".
        "where id=$projectid for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) return array(2,"No such project: $projectid");
    $row = sql_fetch_array( $qu, 0);
    $old_held_amounts = substr(ereg_replace(
        "\\+0[A-Z]+","","+$row[held_amounts]"),1);
    $parent = intval($row["parent"]);
    $zeroes = ereg_replace("[^,]+","",$row["held_amounts"]);

    // We don't need to lock the children because we can safely
    // assume that any other task that would change anything relevant
    // would first lock the parent project (which we've already locked).
    $qu = sql_exec("select max_money_list(held_amounts) from (".
            "select held_amounts from submissions ".
                "where projectid=$projectid and held_amounts~'[1-9]' union ".
            "select coalesce(sum_money_list(".
                "regexp_replace(held_amounts,',[^,]*','')),'$zeroes') ".
                "from projects where parent=$projectid) foo");
    if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    $new_held_amounts = substr(ereg_replace(
        "\\+0[A-Z]+","","+$row[max_money_list]"),1);

    if( $new_held_amounts !== $old_held_amounts) {
        $qu = sql_exec("update projects set ".
            "held_amounts='$new_held_amounts' where id=$projectid");
        if( $qu === false) return private_dberr();
        if( $parent) {
            $rc = private_update_held_amounts( $parent);
            if( $rc[0]) return $rc;
        }
    }

    return private_updatechildbounties( $projectid);
}

function ff_releaseholds()
{
    $now = time();
    $qu = sql_exec("select * from release_holds_queue ".
        "where time <= $now order by time limit 1000");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $time = intval($row["time"]);
        $submission = intval($row["submission"]);
        $projectid = intval($row["projectid"]);

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        $rc = private_lock_from_root( $projectid);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);

        // Watch out for race conditions.  Make sure
        // that the hold is still supposed to expire.
        $qu2 = sql_exec("select id from submissions ".
            "where id=$submission and hold_endtime=$time for update");
        if( $qu2 === false) return private_dberr(1);
        if( sql_numrows($qu2) == 0) {
            // The submission status has changed.
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr();
        } else {
            $qu2 = sql_exec("update submissions set ".
                "held_amounts=regexp_replace(held_amounts,'[^,]+','','g'),".
                "hold_endtime=null where id=$submission");
            if( $qu2 === false) return private_dberr(1);

            $rc = private_update_held_amounts( $projectid);
            if( $rc[0]) return private_dberr($rc[0],$rc[1]);

            $rc = private_commit();
            if( $rc[0]) return $rc;
        }

        // It's okay if this fails
        sql_exec("delete from release_holds_queue ".
            "where time=$time and submission=$submission");
    }

    return array(0,"Success");
}

function ff_distributecommunitypot() {
    // Get the list of featured projects
    list($rc,$featured) = ff_getfeaturedprojects();
    if( $rc) return array($rc,$featured);
    if( sizeof($featured) == 0) return array(0,"No featured projects.");

    // Compute the total number of votes
    $nvotes = 0;
    foreach( $featured as $p) $nvotes += $p["votes"];

    // Get the amount in the community pot
    $qu = sql_exec("select * from communitypot limit 1000");
    if( $qu === false) return private_dberr();

    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);

        // Randomly select a project to sponsor.
        // The random selection is weighted by the number of
        // votes for the project.
        $v_index = mt_rand( 0, $nvotes-1);
        foreach( $featured as $p) {
            if( $p["votes"] > $v_index) break;
            $v_index -= $p["votes"];
        }
        $nid = intval(substr($p["id"],1));

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        // Make sure the community pot record still exists
        $qu2 = sql_exec("select * from communitypot ".
            "where xid=$row[xid] for update");
        if( $qu2 === false) return private_dberr(1);

        // Increase the project's bounty
        $rc = private_adjust_project_bounty( $nid, $row["amount"]);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);

        // Delete the community pot record
        $qu2 = sql_exec("delete from communitypot where xid=$row[xid]");
        if( $qu2 === false) return private_dberr(1);

        $rc = private_commit();
        if( $rc[0]) return $rc;
    }

    return array(0,"Distributed $i community deductions.");
}

// This function acquires the following locks:
//   1. <member_donations>
//   3. <members>
function ff_fixfactors() {
    $qu = sql_exec("select * from fix_factors_queue order by id limit 1000");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $id = intval($row["id"]);
        $username = $row["member"];
        $newfactor = intval($row["factor"]);

        $qu2 = sql_exec("select * from member_donations where ".
            "member='".sql_escape($username)."' and factor != $newfactor");
        if( $qu2 === false) return private_dberr();
        for( $j=0; $j < sql_numrows($qu2); $j++) {
            $row2 = sql_fetch_array($qu2,$j);
            $nid = intval($row2["project"]);

            $qu3 = sql_exec("begin");
            if( $qu3 === false) return private_dberr();

            // Lock the record that we want to fix
            $qu3 = sql_exec("select * from member_donations where ".
                "project=$nid and member='".sql_escape($username).
                "' for update");
            if( $qu3 === false) return private_dberr(1);
            $row3 = sql_fetch_array($qu3,0);
            $amount = $row3["amount"];
            $oldfactor = intval($row3["factor"]);
            $assignee = $row3["assignee"];

            if( $oldfactor != $newfactor) {
                // Make sure that the member's subscription status
                // hasn't changed, because this function's logic is not
                // guaranteed to work unless we promise not to supercede
                // subsequent status changes.
                $qu3 = sql_exec("select subscription_fee from members ".
                    "where username='".sql_escape($username)."' for update");
                if( $qu3 === false) return private_dberr(1);
                $row3 = sql_fetch_array($qu3,0);
                $factor = (ereg("[1-9]",$row3["subscription_fee"]) ? 2 : 1);
                if( $factor != $newfactor) {
                    // The subscription status has changed
                    $qu3 = sql_exec("rollback");
                    if( $qu3 === false) return private_dberr();
                    
                    // It is no longer this task's responsibility to update
                    // this member's credit factors, so we can move on to
                    // the next queued task.
                    break;
                }

                // Compute the base credits based on the current amount
                $calculation = "0";
                list($rc,$currencies) = ff_currencies();
                if( $rc) return private_dberr($rc,$currencies);
                foreach( $currencies as $name => $details) {
                    $ratio = $details["credit_ratio"];
                    $calculation .= "+round(".
                        currency_value($amount,$name)."*$ratio)";
                }

                $qu3 = sql_exec("update member_donations ".
                    "set factor=$newfactor,credits=$newfactor*($calculation) ".
                    "where ".
                    "project=$nid and member='".sql_escape($username)."'");
                if( $qu3 === false) return private_dberr(1);
            }

            $rc = private_commit();
            if( $rc[0]) return $rc;
        }

        // Now delete the queued task
        // It doesn't particularly matter if this transaction fails.
        @sql_exec("delete from fix_factors_queue where id=$id");
    }

    return array(0,"Fixed factors for $i members.");
}

function ff_setsponsorship($projectid,$username,$amount,$is_delta=false)
{
    $amount = scrubmoney($amount);
    $nid = intval(substr($projectid,1));
    $username = scrub($username);
    return private_setsponsorship($nid,$username,$amount,$is_delta);
}

// This function acquires the following locks:
//   <sponsorship_queue>
//   <projects>
//   <member_donations>
//   <members>
function private_setsponsorship($nid, $username, $amount,
    $is_delta=false, $queue_xid=false, $desc="Set sponsorship", $random=false)
{
    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Determine the factor based on whether the member is a subscriber
    $qu = sql_exec("select subscription_fee from members ".
        "where username='".sql_escape($username)."' for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0)
        return private_dberr(2,"No such member: $username");
    $row = sql_fetch_array( $qu, 0);
    $factor = (ereg("[1-9]",$row["subscription_fee"])?2:1);

    // Make sure the member_donations record exists
    // This query may fail if the record already exists.  But if it
    // fails, then we will need to verify the existence of the record.
    $qu = @sql_exec( "insert into member_donations ".
        "(member,project,amount,factor,assignee,credits) values ".
        "('".sql_escape($username)."',$nid,'',$factor,'".
        sql_escape($username)."',0)");
    if( $qu === false) {
        $qu = sql_exec("rollback");
        if( $qu === false) return private_dberr();

        // Make sure that the record exists
        $qu = sql_exec( "select * from member_donations where ".
            "project=$nid and member='".sql_escape($username)."'");
        if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    } else {
        $rc = private_commit();
        if( $rc[0]) return $rc;
    }

    // Get a transaction ID
    $xid = sql_nextval("transaction_seq");
    if( $xid === false) return private_dberr(1);
    $split = 0;

    $now = time();

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Lock the project record
    $qu = sql_exec("select id from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0)
    	return private_dberr(2,"No such project: p$nid");

    // Figure out the delta and the net sponsorship amounts.
    $qu = sql_exec("select ".($is_delta?
        "add_money(amount,'$amount') as net,'$amount' as delta ":
        "'$amount' as net,subtract_money('$amount',amount) as delta ").
        "from member_donations where ".
        "project=$nid and member='".sql_escape($username).
        "' for update");
    if( $qu === false || sql_numrows( $qu) == 0) return private_dberr(1);
    $row = sql_fetch_array( $qu, 0);
    $net = $row["net"];
    $delta = $row["delta"];

    if( strpos($net,"-") !== false)
        return private_dberr(9,"Negative sponsorships not allowed.");

    if( $queue_xid !== false) {
        // Lock the sponsorship queue record
        $qu = sql_exec("select * from sponsorship_queue ".
            "where xid=$queue_xid and projectid".
            ($random?" is null":"=$nid")." for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) == 0) {
            $qu = sql_exec("rollback");
            if( $qu === false) return private_dberr(1);
            return array(0,"Queue entry already complete.");
        }
    }

    // Subtract the money from the person's reserve
    $qu = sql_exec("update members set ".
        "reserve=subtract_money(reserve,'$delta'),".
        "total_sponsorships=add_money(total_sponsorships,'$delta') ".
        "where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    // Let's make sure we're not sponsoring more money than we have
    $qu = sql_exec("select reserve,email from members ".
        "where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0)
        return private_dberr(2,"No such member: $username");
    $row = sql_fetch_array( $qu, 0);
    if( strpos($row["reserve"], "-") !== false) {
        return private_dberr(9,"Not enough money in reserve");
    }

    $qu = sql_exec("insert into transaction_log ".
        "(xid,split,time,account,change,description) values ".
        "($xid,".(++$split).",$now,'reserve:$username',".
        "subtract_money('','$delta'),'".sql_escape($desc)."')");
    if( $qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][$username]);
    unset($GLOBALS["PRIVATE_MEMBER_CACHE"][strtolower($row["email"])]);

    // Compute the base credits based on the given amount
    $calculation = "0";
    list($rc,$currencies) = ff_currencies();
    if( $rc) return private_dberr($rc,$currencies);
    foreach( $currencies as $name => $details) {
        $ratio = $details["credit_ratio"];
        $calculation .= "+round(".currency_value($net,$name)."*$ratio)";
    }

    $qu = sql_exec("update member_donations set amount='$net',".
        "credits=factor*($calculation) ".
        "where project=$nid and member='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    $qu = sql_exec("insert into transaction_log ".
        "(xid,split,time,account,change,description) values ".
        "($xid,".(++$split).",$now,'sponsorship:$username:p$nid',".
        "'$delta','".sql_escape($desc)."')");
    if( $qu === false) return private_dberr(1);

    $rc = private_adjust_project_bounty( $nid, $delta);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    if( $queue_xid !== false) {
        // Delete the record
        $qu = sql_exec("delete from sponsorship_queue ".
            "where xid=$queue_xid and projectid".
            ($random?" is null":"=$nid"));
        if( $qu === false) return private_dberr(1);
    }

    return private_commit();
}

// This function assigns a project sponsorship to a specified user.  This
// is the mechanism by which a group of small donors can take control
// of a project, by pooling their sponsorships toward a single assignee.
// This function acquires the following locks:
//   1. <member_donations>
//   3. <members>
function ff_assigndonation($projectid,$username,$assignee)
{
    $nid = intval(substr($projectid,1));

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("select * from member_donations where ".
        "project=$nid and member='".sql_escape($username).
        "' for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) return private_dberr(2,"No such donation");
    $row = sql_fetch_array( $qu, 0);
    $oldassignee = $row["assignee"];

    if( $oldassignee === $assignee) return private_commit();

    $qu = sql_exec("update member_donations set assignee='".
        sql_escape($assignee)."' where project=$nid and member='".
        sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    if( $assignee !== $username) {
        // Make sure that the assignee is a real member.
        $qu = sql_exec("select username from members ".
            "where username='".sql_escape($assignee)."' for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) == 0)
            return private_dberr(2,"No such member: $assignee");
    }

    return private_commit();
}

// This function returns a list of members who are ostensibly interested
// in becoming the lead for the given project.  At a minimum, it
// includes everyone who has at least one other person's project credits
// assigned to them.
function ff_leadcandidates($projectid) {
    $nid = intval(substr($projectid,1));

    $qu = sql_exec( "select distinct assignee from member_donations where ".
        "project=$nid and member != assignee");
    if( $qu === false) return private_dberr(1);

    $candidates = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $candidates[] = $row["assignee"];
    }

    return array(0,$candidates);
}

// This function records a sponsorship in a member's reserve.  The sponsorship
// funds must already be in our possession before this function is invoked.
//
// If $donation_id is passed, then this function will return error 7 if
// there has already been another sponsorship with the same ID within the last
// 24 hours or so.  (Transaction records older than 24 hours may be
// deleted, so older repeats are not guaranteed to be caught.)
//
// This function acquires the following locks:
//   1. <members>
//   2. <recent_donation_ids> (on a new record)
function ff_receivefunds( $username, $amount, $donation_id=false, $fee='',
    $is_subscr_payment=false, $payer_name=false, $payer_email=false,
    $payer_country=false, $payer_zip=false, $ff_pays_fee=false)
{
    $amount = scrubmoney($amount);
    $fee = scrubmoney($fee);

    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);

    $code = ereg_replace("[^A-Z]","",$amount);
    if( !isset( $currencies[$code]))
        return array(1,"Unknown currency: $code");

    $currency = $currencies[$code];

    if( strpos($amount,"-") !== false)
        return array(4,"Negative sponsorships not allowed");
    if( !ereg("[1-9]",$amount))
        return array(4,"Zero sponsorship not allowed");

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("select * from members ".
        "where username='".sql_escape($username)."' for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0)
        return private_dberr(2,"No such user: $username");
    $row = sql_fetch_array( $qu, 0);

    $gross = "add_money('$amount','$fee')";

    // Get a transaction ID
    $xid = sql_nextval("transaction_seq");
    if( $xid === false) return private_dberr(1);
    $split = 0;
    $now = time();
    $desc = $is_subscr_payment ? "Monthly Sponsorship Payment" : "Deposit";

    if( $is_subscr_payment) {
        $due = intval($row["payment_due"]);
        if( $due == 0) $due = $now;
        $d = explode(" ",date("H i s n j Y",$due));
        if( $row["type"] == 'weekly')
            $nextpayment = mktime($d[0],$d[1],$d[2],$d[3],$d[4]+7,$d[5]);
        else
            $nextpayment = mktime($d[0],$d[1],$d[2],$d[3]+1,$d[4],$d[5]);
    }

    if( $is_subscr_payment) {
        // Delete subscriptions to completed projects.
        // If this is not done here, then the payments to the completed
        // transactions will simply default into the person's reserve.
        // That situation can still occur, since the payments don't happen
        // in this transaction.  However, putting this code here makes
        // that situation much less likely.
        $qu = sql_exec("delete from subscriptions where ".
            "username='".sql_escape($username)."' and projectid in ".
            "(select id from projects where status='complete' and id in ".
                "(select projectid from subscriptions ".
                    "where username='".sql_escape($username)."'))");
        if( $qu === false) return private_dberr();

        $remaining = $amount;
        $qu = sql_exec("select * from subscriptions ".
            "where username='".sql_escape($username)."' order by sequence");
        if( $qu === false) return private_dberr(1);
        for( $i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array($qu,$i);

            if( !ereg("[1-9]",$remaining)) break;

            // Determine the proper amount to contribute
            $qu2 = sql_exec("select ".
                "subtract_money('$remaining','$row[amount]') as remaining");
            if( $qu2 === false || sql_numrows($qu2) != 1)
                return private_dberr(1);
            $row2 = sql_fetch_array($qu2,0);
            if( strpos($row2["remaining"],"-") === false) {
                $contrib = $row["amount"];
                $remaining = $row2["remaining"];
            } else {
                $contrib = $remaining;
                $remaining = '';
            }

            $qu2 = sql_exec("insert into sponsorship_queue ".
                "(xid,projectid,username,amount) values ".
                "($xid,$row[projectid],'".sql_escape($username).
                "','$contrib')");
            if( $qu2 === false) return private_dberr(1);
        }

        // If there's any amount remaining, it should go to a featured project.
        if( ereg("[1-9]",$remaining)) {
            $qu = sql_exec("insert into sponsorship_queue ".
                "(xid,projectid,username,amount) values ".
                "($xid,null,'".sql_escape($username)."','$remaining')");
            if( $qu === false) return private_dberr(1);
        }
    }

    if( $donation_id) {
        $qu = @sql_exec( "insert into recent_donation_ids (id,xid,time) ".
            "values ('".sql_escape($donation_id)."',$xid,$now)");
        if( $qu === false) {
            sql_exec("rollback");

            // Check whether the failure is due to the record already existing
            $qu = sql_exec( "select * from recent_donation_ids ".
                "where id='".sql_escape($donation_id)."'");
            if( $qu === false || sql_numrows($qu) == 0) return private_dberr();

            return array(7,"Sponsorship $donation_id already exists.");
        }
    }

    $qu = sql_exec( "update members set ".
        ($is_subscr_payment ?
            "last_subscr_gross=$gross,".
            "last_subscr_net='$amount',".
            "last_subscr_time=$now,".
            "last_subscr_xid=$xid,".
            "subscription_fee=$gross,".
            "payment_due=$nextpayment," : "").
        "reserve=add_money(reserve,'$amount')".
        " where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    if( ereg("[1-9]",$amount)) {
        $qu = sql_exec("insert into transaction_log ".
            "(xid,split,time,account,change,description) values ".
            "($xid,".(++$split).",$now,'reserve:$username',".
            "'$amount','$desc')");
        if( $qu === false) return private_dberr(1);
    }

    if( ereg("[1-9]",$fee)) {
        $qu = sql_exec("insert into transaction_log ".
            "(xid,split,time,account,change,description) values ".
            "($xid,".(++$split).",$now,'paypal-fee',".
            "'$fee','$desc')");
        if( $qu === false) return private_dberr(1);
    }

    $account = ($is_subscr_payment?"subscription-payments":"deposits").
        ":$payer_email:$payer_name:$payer_country:$payer_zip";

    $qu = sql_exec("insert into transaction_log ".
        "(xid,split,time,account,change,ref,description) values ".
        "($xid,".(++$split).",$now,'".sql_escape($account).
        "',subtract_money('',$gross),'".
        sql_escape($donation_id)."','$desc')");
    if( $qu === false) return private_dberr(1);

    $rc = private_commit();
    if( $rc[0]) return $rc;

    // Don't worry if this fails because it's called from a cron job
    ff_distributemonthlysponsorships();

    return array(0,"Success");
}

function ff_distributemonthlysponsorships() {
    list($rc,$featured) = ff_getfeaturedprojects();
    if( $rc) return array($rc,$featured);

    // Compute the total number of votes
    $nvotes = 0;
    foreach( $featured as $p) $nvotes += $p["votes"];

    $qu=sql_exec("select * from sponsorship_queue limit 100");
    if ($qu===false) return private_dberr();

    $count = 0;
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);

        if( intval($row["projectid"]) == 0) {
            if( sizeof($featured) == 0) {
                // The money will have to stay in the person's reserve
                $rc = array(2,"No featured projects");
            } else {
                // Randomly select a featured project
                $v_index = mt_rand( 0, $nvotes-1);
                foreach( $featured as $p) {
                    if( $p["votes"] > $v_index) break;
                    $v_index -= $p["votes"];
                }
                $nid = intval(substr($p["id"],1));

                // Transfer the money into the selected project.
                $rc = private_setsponsorship( $nid,$row["username"],
                    $row["amount"],true,$row["xid"],"Monthly Sponsorship",1);
                if( $rc[0] == 1) return $rc;
            }
        } else {
            // Transfer the money
            $rc = private_setsponsorship($row["projectid"],$row["username"],
                $row["amount"],true,$row["xid"],"Monthly Sponsorship");
            if( $rc[0] == 1) return $rc;
        }

        if( $rc[0]) {
            // There is some non-transient problem.  Just delete the record.
            // (egs, project already complete, or insufficient funds)
            $qu2 = sql_exec("delete from sponsorship_queue ".
                "where xid=$row[xid] and projectid".
		($row["projectid"]?"=$row[projectid]":" is null"));
            if( $qu2 === false) return private_dberr();
        } else $count ++;
    }

    return array(0,"Successfully distributed $count monthly sponsorships.");
}

function ff_receivefakesubscriptionpayments()
{
    return array(0,"Function obsolete");
}

function ff_numcredits( $id, $username)
{
    $nid = intval(substr($id,1));
    $qu = sql_exec("select coalesce(sum(round(credits*0.01)),0) as credits ".
        "from member_donations where ".
        "project=$nid and assignee='".sql_escape($username)."'");
    if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    return array(0,$row["credits"]);
}

function ff_cansupplant( $id, $username)
{
    list($rc,$projinfo) = ff_getprojectinfo( $id);
    if( $rc) return array($rc,$projinfo);

    if( $username === $projinfo["lead"])
        return array(0,"You are already the project lead");

    list($rc,$mycredits) = ff_numcredits( $id, $username);
    if( $rc) return array($rc,$mycredits);

    list($rc,$hiscredits) = ff_numcredits( $id, $projinfo["lead"]);
    if( $rc) return array($rc,$hiscredits);

    if( $mycredits <= $hiscredits) return array(5,"Not enough credits");

    return array(0,"You rock!");
}

// This function may lock the following tables in this order:
//
// <projects>
// <post_attachments>
// <posts>
// <duties>
//
function ff_setprojectreqmts($username, $id, $oldseq, $newreqmts,
    $subject, $postid,$attachments)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    $oldseq = intval($oldseq);

    list($rc,$row) = private_begin_authorize( $username, $id);
    if( $rc) return array($rc,$row);

    if( $oldseq != intval($row["reqmts_seq"])) {
        sql_exec( "rollback");
        return array(7,"Somebody else changed the requirements before us");
    }

    // Make sure there are no requirements change disputes deliberating.
    $qu = sql_exec("select id from disputes where projectid=$nid ".
        "and type='badchange' and status='deliberating'");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) > 0) return private_dberr(8,
        "Can't set reqmts while a reqmts dispute is deliberating.");

    // Get an up-to-date diff of the changes
    include_once("diff.php");
    list($rc,$patch) = diffText( $row["reqmts"], $newreqmts);
    if( $rc) return private_dberr($rc,$patch);

    $qu = sql_exec( "update projects set ".
        "reqmts='".sql_escape($newreqmts)."',".
        "numattachments=".
        (intval($row["numattachments"])+sizeof($attachments)).",".
        "reqmts_seq=".($oldseq+1)." where id=$nid");
    if( $qu === false) return private_dberr(1);
    
    //reference this project inside the postattachment 
	$qu = sql_exec("update post_attachments set projectid=$nid where postid=".intval($postid));
	if ($qu===false) return private_dberr(1);

    $qu = sql_exec( "insert into project_reqmts_history ".
        "(id,time,subject,postid,action,patch) values ".
        "($nid,".time().",'".sql_escape($subject)."',".
        intval($postid).",'accept','".sql_escape($patch)."')");
    if( $qu === false) return private_dberr(1);

    $qu = sql_exec( "update posts set status='accepted' ".
        "where id=".intval($postid));
    if( $qu === false) return private_dberr(1);

    // Delete the corresponding duty if there is one.
    $rc = private_destroyduty( $nid, "change proposal", intval($postid));
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    unset($GLOBALS["PRIVATE_PROJECT_INFO"][$id]);

    $macros = array(
        "projectname" => $row["name"],
    );
    $url = projurl($id,"post=".intval($postid));
    $rc = al_triggerevent( "watch:$id-news\\member:".scrub($username),
        $url, "pnews-changeaccepted", $macros);
    if($rc[0]) return $rc;

    return private_commit($oldseq+1);
}

// This function may lock the following tables in this order:
//
// <projects>
// <duties>
//
function ff_supplantlead( $id, $username)
{
    $nid = intval(substr($id,1));
    $id = "p$nid";

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec( "select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) return private_dberr(2,"No such project: $id");
    $row = sql_fetch_array( $qu, 0);
    $oldauth = $row["lead"];
    $status = $row["status"];

    // You can always supplant yourself.
    if( $oldauth === $username) {
        sql_exec( "rollback");
        return array(0,"Success");
    }

    if( $oldauth != '') {
        // Compute the number of credits for each of the two contenders.

        // I'm not too worried about doing these two queries atomically.
        // I'm pretty sure there's no practical way of exploiting this
        // race condition.
        list($rc,$mycredits) = ff_numcredits( $id, $username);
        if( $rc) return private_dberr($rc,$mycredits);
        list($rc,$hiscredits) = ff_numcredits( $id, $oldauth);
        if( $rc) return private_dberr($rc,$hiscredits);

        if( $mycredits <= $hiscredits)
            return private_dberr(5,"You have too few credits.");
    } else {
        $rc = private_setdutydeadline( $nid, $status);
        if( $rc[0]) {
            sql_exec("rollback");
            return $rc;
        }
    }

    // Set the lead
    $qu = sql_exec( "update projects set lead='".
        sql_escape($username)."' where id=$nid");
    if( $qu === false) return private_dberr(1);

    unset($GLOBALS["PRIVATE_PROJECT_INFO"][$id]);

    // Make sure the new lead is watching the project
    $rc = al_createwatch( "p$nid-news", $username);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    // Trigger a notification event
    $macros = array(
        "projectname" => $row["name"],
        "oldlead" => $oldauth,
        "newlead" => $username);
    $event = ($oldauth ? 'supplant' : 'nosupplant');
    $url = projurl("p$nid");
    $rc = al_triggerevent( "watch:$id-news\\member:".scrub($username),
        $url, "pnews-$event", $macros);
    if($rc[0]) return $rc;

    return private_commit();
}

// This function may lock the following tables in this order:
//
// <projects>
// <duties>
//
function ff_resignlead( $id, $username)
{
    $nid = intval(substr($id,1));

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec( "select lead from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0)
        return private_dberr(2,"No such project: $id");
    $row = sql_fetch_array( $qu, 0);
    if( $row["lead"] === '' || $row["lead"] !== $username)
        return private_dberr(5,"You are not the project lead");

    $rc = private_removelead( $nid);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    return private_commit();
}

// Only call this function in a transaction.
// This function may lock the following tables in this order:
//
// <projects>
// <duties>
//
function private_removelead( $nid)
{
    $qu = sql_exec("update projects set lead='' where id=$nid");
    if( $qu === false) return private_dberr();

    unset($GLOBALS["PRIVATE_PROJECT_INFO"]["p$nid"]);

    // Clear the deadlines, since there's no leader anyway.
    // This is also necessary to prevent the cron job from processing
    // meaningless deadlines.
    $qu = sql_exec("update duties set deadline=null ".
        "where project=$nid and deadline is not null");
    if( $qu === false) return private_dberr();

    return array(0,"Success");
}

function ff_getsubprojects( $id)
{
    $nid = intval(substr($id,1));

    $qu = sql_exec( "select * from projects where parent=$nid order by id");
    if( $qu === false) return private_dberr();

    $children = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $children["p$row[id]"] = private_makeprojectrecord( $row);
    }

    return array(0,$children);
}

function ff_getnominalprojectpath( $id)
{
    $nid = intval(substr($id,1));
    $path = "/$id";
    while(1) {
        $qu = sql_exec("select parent from projects where id=$nid");
        if( $qu === false) return private_dberr();
        if( sql_numrows( $qu) == 0) return array(2,"No such project: $id");
        $row = sql_fetch_array( $qu, 0);
        $nid = intval( $row["parent"]);
        if( $nid == 0) return array(0,$path);
        $path = "/p$nid$path";
    }
}

function ff_getprojectgraph( $root)
{
    $nroot = intval(substr($root,1));

    $children = array();

    // Ordering by descending ID ensures that children will be processed
    // before their parents.  This is necessary to make sure the graph
    // comes together correctly.  This guarantee may go away in the future
    // if we ever allow project adoption.
    $qu = sql_exec( "select id,parent,name,status from projects ".
        "where root=$nroot order by id desc");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $id = intval($row["id"]);
        $children[intval($row[parent])]["p$id"] = array(
            "id" => "p$id", "name" => $row["name"],
            "status" => $row["status"],
            "children" => isset($children[$id]) ? $children[$id] : array());
    }

    if( !isset( $children[0])) return array(2,"No such project: $root");

    return array(0,$children[0]);
}

function private_childcmp( $a, $b) {
    if( $b["allotment"] == 0) return ($a["allotment"] == 0 ? 0 : 1);
    if( $a["allotment"] == 0) return -1;

    $depth = $GLOBALS["private_childcmp_depth"];
    $currency = $GLOBALS["private_childcmp_currency"];

    $p_a = ereg_replace("^.*[^0-9]([0-9]+)$currency.*$",
        "\\1",":".$a["priority"][$depth]);
    $p_b = ereg_replace("^.*[^0-9]([0-9]+)$currency.*$",
        "\\1",":".$b["priority"][$depth]);

    // Do a big-integer comparison.
    if( strlen($p_a) < strlen($p_b)) return 1;
    if( strlen($p_a) > strlen($p_b)) return -1;
    if( $p_a < $p_b) return 1;
    if( $p_a > $p_b) return -1;
    return 0;
}

// Only call this function within a transaction.  If you pass the bounty
// parameter, then make sure you also are holding a lock on the project
// record.
function private_updatechildbounties( $parent, $bounty_portions=false)
{
    if( $bounty_portions === false) {
        $qu = sql_exec("select bounty_portions from projects ".
            "where id=$parent for update");
        if( $qu === false) return private_dberr();
        if( sql_numrows($qu) != 1) return array(2,"No such project: p$parent");
        $row = sql_fetch_array( $qu, 0);
        $bounty_portions = $row["bounty_portions"];
    }
    if( is_string( $bounty_portions)) {
        $bounty_portions = explode(',',$bounty_portions);
    }

    $children = array();
    $qu = sql_exec("select ".
        "case when allotment=0 then '' else ".
            "mult_round_money_list(held_amounts,".
            "10000000000000000/allotment) end as priority,".
        "id,allotment,direct_bounty,held_amounts,bounty_portions ".
        "from projects where parent=$parent for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0) return array(0,"Success");
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $children[intval($row["id"])] = array(
            "id" => intval($row["id"]),
            "priority" => explode(',',$row["priority"]),
            "allotment" => intval($row["allotment"]),
            "direct_bounty" => $row["direct_bounty"],
            "held_amounts" => explode(',',$row["held_amounts"]),
            "new_bounty_portions" => array(),
            "new_bounty" => '',
            "bounty_portions" => $row["bounty_portions"]);
    }

    $new_bounty_str = "";
    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);
    foreach( $currencies as $name => $details) {
        for( $i=0; $i < sizeof($bounty_portions); $i++) {
            // For each depth level and currency, we need to sort the
            // children according to how likely they are to require more
            // than their bounty portion (due to held amounts).  Then
            // with a single traversal of the children (per depth level
            // and currency), we can split up the bounty appropriately
            // without overpromising anyone.
            $GLOBALS["private_childcmp_depth"] = $i;
            $GLOBALS["private_childcmp_currency"] = $name;
            uasort( $children, private_childcmp);

            $bounty = ereg_replace("^.*[^0-9]([0-9]+)$name.*$",
                "\\1", ":".$bounty_portions[$i]);
            if( substr($bounty,0,1) === ':') $bounty = "0";
            $rem = 1000;

            foreach( $children as $id => &$child) {
                if( $rem <= 0) break;

                // We're just using the database to do our bigint math.
                // This is brutally inefficient.
                if( $bounty === '0') $amt = "0";
                else {
                    $qu = sql_exec("select floor($bounty::numeric*".
                        "$child[allotment]/$rem) as amt");
                    if( $qu === false || sql_numrows($qu) != 1)
                        return private_dberr();
                    $row = sql_fetch_array($qu,0);
                    $amt = $row["amt"];
                }
                if( !isset($child["new_bounty_portions"][$i]))
                    $child["new_bounty_portions"][$i] = "";
                if( $amt !== '0') {
                    if( $child["new_bounty_portions"][$i] !== "")
                        $child["new_bounty_portions"][$i] .= "+";
                    $child["new_bounty_portions"][$i] .= "${amt}$name";
                }

                // We're just using the database to do our bigint math.
                // This is brutally inefficient.
                $qu = sql_exec("select ".
                    ($amt==='0'?"'$child[new_bounty]'":
                        "add_money('$child[new_bounty]','$amt$name')").
                        " as new_bounty,".
                    "$bounty-greatest($amt,currency_value('".
                        $child["held_amounts"][$i]."','$name')) ".
                        "as bounty");
                if( $qu === false || sql_numrows($qu) != 1)
                    return private_dberr();
                $row = sql_fetch_array($qu,0);
                $child["new_bounty"] = $row["new_bounty"];
                $bounty = $row["bounty"];

                $rem -= $child["allotment"];
            }
            unset($child);
        }
    }

    foreach( $children as $id => &$child) {
        $child["new_bounty_portions"][] = $child["direct_bounty"];
        $childportions = join(',',$child["new_bounty_portions"]);

        if( $childportions !== $child["bounty_portions"]) {
            $qu = sql_exec("update projects set bounty=".
                "add_money('$child[new_bounty]','$child[direct_bounty]'),".
                "bounty_portions='$childportions' where id=$id");
            if( $qu === false) return private_dberr();

            unset($GLOBALS["PRIVATE_PROJECT_INFO"]["p$id"]);

            $rc = private_updatechildbounties(
                $id, $child["new_bounty_portions"]);
            if( $rc[0]) return $rc;
        }
    }

    return array(0,"Success");
}

function ff_getfeaturedprojects()
{
    $qu = sql_exec( "select * from featured_projects ".
        "order by usd_value desc,votes desc,id");
    if( $qu === false) return private_dberr();
    $featured = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $featured["p$row[id]"] = array(
            "id" => "p$row[id]",
            "votes" => intval($row["votes"]),
            "name" => $row["name"],
            "abstract" => $row["abstract"],
            "direct_bounty" => $row["direct_bounty"],
            "bounty" => $row["bounty"]);
    }
    return array(0,$featured);
}

function private_dberr( $rollback=0,$err="Database access error") {
    if( $rollback) sql_exec("rollback");
    $backtrace = debug_backtrace();
    return array($rollback?$rollback:1,
        "$err (line ".$backtrace[0]["line"].")");
}

function private_commit( $retval="Success") {
    $qu = sql_exec("commit");
    if( $qu === false) return private_dberr(1);
    return array(0,$retval);
}

function ff_gettext( $textid, $macros)
{
    if( $textid == 'rejected-subject') {
        $text = "Submission Rejected";
    } else if( $textid == 'rejected-body') {
        $text = "Your code submission to ".
            "project '%PROJECTNAME%' has been rejected.  ".
            "This means that your submission does not meet the project ".
            "requirements.\n".
            "The project lead has provided the following explanation:\n".
            "  \"%REASON%\"\n".
            "If you disagree with this assessment, it's important that ".
            "you resolve the confusion with the project lead before ".
            "resubmitting your code.";
    } else if( $textid == 'prejudice-subject') {
        $text = "Submission Rejected with Prejudice";
    } else if( $textid == 'prejudice-body') {
        $text = "Your code submission to ".
            "project '%PROJECTNAME%' has been rejected with prejudice.  ".
            "This means that your submission does not appear to ".
            "be a serious or significant attempt to meet the project ".
            "requirements.\n".
            "If you disagree with this assessment, it's important that ".
            "you resolve the confusion with the project lead before ".
            "resubmitting your code.";
    } else if( $textid == 'resetpwd-subject') {
        $text = "FOSS Factory Password Reset";
    } else if( $textid == 'resetpwd-body') {
        $text = "A password reset of your FOSS Factory member account has\n".
            "been requested.  If you did not make the request, then please\n".
            "ignore this message.  Otherwise, click on the following link\n".
            "to reset your password:\n\n".
            "  %LINK%";
    } else if( $textid == 'newduty-submission-subject') {
        $text = "[NEW DUTY] Code submitted for '%PROJECTNAME%'";
    } else if( $textid == 'newduty-submission-body') {
        $text = "A code submission has been made for project ".
            "'%PROJECTNAME%' by user '%SUBMITTER%'.  As project ".
            "lead, you are now responsible for deciding whether ".
            "the submission should be accepted or rejected.  The ".
            "deadline for this task has not yet been assigned.";
    } else if( $textid == 'newduty2-submission-subject') {
        $text = "[NEW DUTY] Code submitted for '%PROJECTNAME%'";
    } else if( $textid == 'newduty2-submission-body') {
        $text = "A code submission has been made for project ".
            "'%PROJECTNAME%' by user '%SUBMITTER%'.  As project ".
            "lead, you are now responsible for deciding whether ".
            "the submission should be accepted or rejected.  You have ".
            "until %DEADLINE% to make your decision.";
    } else if( $textid == 'pnews-submission-subject') {
        $text = "Code submitted for '%PROJECTNAME%'";
    } else if( $textid == 'pnews-submission-body') {
        $text = "A code submission has been made for project ".
            "'%PROJECTNAME%' by user '%SUBMITTER%'.";
    } else if( $textid == 'newduty-newsubproject-subject') {
        $text = "[NEW DUTY] Allotment of funds needed for '%PROJECTNAME%'";
    } else if( $textid == 'newduty-newsubproject-body') {
        $text = "A new subproject '%PROJECTNAME%' has been created for ".
            "project '%PARENTNAME%'.  As project lead for ".
            "the parent project, you need to decide what percentage of ".
            "funds to allot to it, if any.  The deadline for this task ".
            "has not yet been assigned.";
    } else if( $textid == 'newduty2-newsubproject-subject') {
        $text = "[NEW DUTY] Allotment of funds needed for '%PROJECTNAME%'";
    } else if( $textid == 'newduty2-newsubproject-body') {
        $text = "A new subproject '%PROJECTNAME%' has been created for ".
            "project '%PARENTNAME%'.  As project lead for ".
            "the parent project, you need to decide what percentage of ".
            "funds to allot to it, if any.  You have until %DEADLINE% ".
            "to make your decision.";
    } else if( $textid == 'pnews-newsubproject-subject') {
        $text = "New subproject created for '%PARENTNAME%'";
    } else if( $textid == 'pnews-newsubproject-body') {
        $text = "A new subproject '%PROJECTNAME%' has been created for ".
            "project '%PARENTNAME%'.  The initial requirements are as ".
            "follows:\n%REQUIREMENTS%";
    } else if( $textid == 'news-newproject-subject') {
        $text = "New project created: '%PROJECTNAME%'";
    } else if( $textid == 'news-newproject-body') {
        $text = "A new top-level project '%PROJECTNAME%' has been created.  ".
            "The initial requirements are as follows:\n%REQUIREMENTS%";
    } else if( $textid == 'newduty-newdispute-subject') {
        $text = "[NEW DUTY] Complaint filed about project '%PROJECTNAME%'";
    } else if( $textid == 'newduty-newdispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "User %USERNAME% has filed a complaint ".
            "regarding project '%PROJECTNAME%'.  As the ".
            "project lead, you must respond to %USERNAME%'s claims.  ".
            "Alternately, if you feel that there is no valid case, ".
            "you may forward the complaint on to an ".
            "arbiter without comment.  Note that if you choose ".
            "to comment, then %USERNAME% will be given ".
            "an opportunity to respond to your remarks.  ".
            "The deadline for this task has not yet been assigned.";
    } else if( $textid == 'newduty2-newdispute-subject') {
        $text = "[NEW DUTY] Complaint filed about project '%PROJECTNAME%'";
    } else if( $textid == 'newduty2-newdispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "User %USERNAME% has filed a complaint ".
            "regarding project '%PROJECTNAME%'.  As the ".
            "project lead, you have until %DEADLINE% to respond to ".
            "%USERNAME%'s claims.  ".
            "Alternately, if you feel that there is no valid case, ".
            "you may forward the complaint on to an ".
            "arbiter without comment.  Note that if you choose ".
            "to comment, then %USERNAME% will be given ".
            "an opportunity to respond to your remarks.";
    } else if( $textid == 'newduty-dispute-subject') {
        $text = "[NEW DUTY] Respond to dispute about project '%PROJECTNAME%'";
    } else if( $textid == 'newduty-dispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "User %USERNAME% has added further comment ".
            "to the dispute about project '%PROJECTNAME%'.  ".
            "You again have the option to either ".
            "add more remarks, or to forward the dispute to an ".
            "arbiter without comment.  Note that if you choose ".
            "to comment, then %USERNAME% will be given ".
            "another opportunity to respond to your remarks.  ".
            "The deadline for this task has not yet been assigned.";
    } else if( $textid == 'newduty2-dispute-subject') {
        $text = "[NEW DUTY] Respond to dispute about project '%PROJECTNAME%'";
    } else if( $textid == 'newduty2-dispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "User %USERNAME% has added further comment ".
            "to the dispute about project '%PROJECTNAME%'.  ".
            "You again have the option to either ".
            "add more remarks, or to forward the dispute to an ".
            "arbiter without comment.  Note that if you choose ".
            "to comment, then %USERNAME% will be given ".
            "another opportunity to respond to your remarks.  ".
            "This task must be accomplished before %DEADLINE%.";
    } else if( $textid == 'continuedispute-subject') {
        $text = "[NEW DUTY] Your dispute about project '%PROJECTNAME%'";
    } else if( $textid == 'continuedispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "The project lead has responded to your complaint ".
            "about project '%PROJECTNAME%'.  ".
            "You now have the option to either ".
            "add more remarks, or to forward the dispute for ".
            "arbitration without further comment.  Note that if you ".
            "choose to comment, then the project lead will be given ".
            "another opportunity to respond to your remarks.";
    } else if( $textid == 'pnews-newdispute-subject') {
        $text = "Complaint filed against project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-newdispute-body') {
        $text = "User %USERNAME% has filed a complaint regarding project ".
            "'%PROJECTNAME%'.  The text of the complaint follows:\n".
            "Subject: %SUBJECT%\n%BODY%";
    } else if( $textid == 'newduty-changeproposal-subject') {
        $text = "[NEW DUTY] Proposed requirements change for '%PROJECTNAME%'";
    } else if( $textid == 'newduty-changeproposal-body') {
        $text = "A requirements change has been proposed for project ".
            "'%PROJECTNAME%'.  As the project lead, you must ".
            "decide whether to accept or reject the proposal.  The ".
            "deadline for this task has not yet been assigned.";
    } else if( $textid == 'newduty2-changeproposal-subject') {
        $text = "[NEW DUTY] Proposed requirements change for '%PROJECTNAME%'";
    } else if( $textid == 'newduty2-changeproposal-body') {
        $text = "A requirements change has been proposed for project ".
            "'%PROJECTNAME%'.  As the project lead, you must ".
            "decide whether to accept or reject the proposal.  You have ".
            "until %DEADLINE% to make your decision.";
    } else if( $textid == 'pnews-changeproposal-subject') {
        $text = "Proposed requirements change for '%PROJECTNAME%'";
    } else if( $textid == 'pnews-changeproposal-body') {
        $text = "A requirements change has been proposed for project ".
            "'%PROJECTNAME%'.";
    } else if( $textid == 'pnews-changeaccepted-subject') {
        $text = "Requirements changed for '%PROJECTNAME%'";
    } else if( $textid == 'pnews-changeaccepted-body') {
        $text = "A requirements change proposal for project ".
            "'%PROJECTNAME%' has been accepted.";
    } else if( $textid == 'pnews-deletingproject-subject') {
        $text = "Deleting project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-deletingproject-body') {
        $text = "The FOSS Factory project '%PROJECTNAME%' has been ".
            "scheduled to be deleted on %DELTIME%.  In case you have any ".
            "objections, you may prevent the deletion using the ".
            "'Cancel Deletion' link on the project page.";
    } else if( $textid == 'pnews-canceldeletingproject-subject') {
        $text = "Cancelled deleting project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-canceldeletingproject-body') {
        $text = "The scheduled deletion of FOSS Factory project ".
            "'%PROJECTNAME%' has been cancelled by user '%CANCELLER%'.";
    } else if( $textid == 'pnews-changerejected-subject') {
        $text = "Requirement change for '%PROJECTNAME%' rejected";
    } else if( $textid == 'pnews-changerejected-body') {
        $text = "A requirements change proposal for project ".
            "'%PROJECTNAME%' has been rejected.";
    } else if( $textid == 'forumpost-subject') {
        $text = "Forum discussion";
    } else if( $textid == 'forumpost-body') {
        $text = "Somebody has posted to a forum that you are watching.";
    } else if( $textid == 'pnews-newpost-subject') {
        $text = "Discussion of project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-newpost-body') {
        $text = "Somebody has made a post to discuss project ".
            "'%PROJECTNAME%'.";
    } else if( $textid == 'pnews-supplant-subject') {
        $text = "New project lead for project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-supplant-body') {
        $text = "Project '%PROJECTNAME%' is now being led by user ".
            "%NEWLEAD%.  The previous project lead was %OLDLEAD%.";
    } else if( $textid == 'pnews-nosupplant-subject') {
        $text = "New project lead for project '%PROJECTNAME%'";
    } else if( $textid == 'pnews-nosupplant-body') {
        $text = "Project '%PROJECTNAME%' is now being led by user ".
            "%NEWLEAD%.  The position of project lead was previously vacant.";
    } else if( $textid == 'plaintiff-subject') {
        $text = "[NEW DUTY] Continue your dispute about project '%PROJECTNAME%'";
    } else if( $textid == 'plaintiff-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "The project lead has responded to your ".
            "complaint.  It's now your turn to either add more ".
            "remarks, or to forward the dispute to an arbiter.  ".
            "Note that if you choose to comment, the project ".
            "lead will be given another opportunity to respond.";
    } else if( $textid == 'misseddeadline-subject') {
        $text = "[IMPORTANT] Missed FOSS Factory duty deadline";
    } else if( $textid == 'misseddeadline-body') {
        $text = "Hi %NAME%.\n\n".
            "Please note that, due to a missed deadline, you have ".
            "been removed from the position of project lead for ".
            "project \"%PROJECTNAME%\".  The deadline was %DEADLINE%.  ".
            "You may still be able to reclaim the position at your ".
            "convenience.  In the meantime, the role of project lead ".
            "is open to any interested person.";
    } else if( $textid == 'leadousted-subject') {
        $text = "FOSS Factory project lead removed";
    } else if( $textid == 'leadousted-body') {
        $text = "Due to a missed duty deadline, FOSS Factory member %EXLEAD% ".
            "has been removed from the position of project lead for project ".
            "\"%PROJECTNAME%\".  The position is currently open to anyone ".
            "interested.\n\n".
            "Note that this action is not intended as punishment, but ".
            "rather as a way of ensuring that projects can not be abandoned ".
            "while there are interested parties.  As such, %EXLEAD% is not ".
            "banned from the position.";
    } else if( $textid == 'disputedefendant-subject') {
        $text = "Dispute decided for project \"%PROJECTNAME%\"";
    } else if( $textid == 'disputedefendant-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "A dispute regarding project \"%PROJECTNAME%\" has been decided ".
            "in favour of the project lead.";
    } else if( $textid == 'disputereject-subject') {
        $text = "Dispute decided for project \"%PROJECTNAME%\"";
    } else if( $textid == 'disputereject-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "By order of the arbiter, the previously accepted submission ".
            "for project \"%PROJECTNAME%\" has now been rejected.  This ".
            "means that the submission failed to meet all project ".
            "requirements.";
    } else if( $textid == 'disputeaccept-subject') {
        $text = "Dispute decided for project \"%PROJECTNAME%\"";
    } else if( $textid == 'disputeaccept-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "By order of the arbiter, a previously rejected submission ".
            "for project \"%PROJECTNAME%\" has now been accepted.";
    } else if( $textid == 'disputecancelchange-subject') {
        $text = "Dispute decided for project \"%PROJECTNAME%\"";
    } else if( $textid == 'disputecancelchange-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "By order of the arbiter, a previously accepted requirements ".
            "change for project \"%PROJECTNAME%\" has been reverted.";
    } else if( $textid == 'resolveconflict-subject') {
        $text = "[NEW DUTY] Merge conflict in change dispute";
    } else if( $textid == 'resolveconflict-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "Because of recent (possibly unrelated) changes made to the ".
            "project requirements, the change that you are disputing is ".
            "no longer automatically revertible by the system.  You need ".
            "to show the system how to revert the change.";
    } else if( $textid == 'newactivedispute-subject') {
        $text = "[ARBITER] New active dispute";
    } else if( $textid == 'newactivedispute-body') {
        $text = "Subject of complaint: %SUBJECT%\n\n".
            "A dispute over project %PROJECTNAME% has been concluded ".
            "and is awaiting a decision by an arbiter.";
    } else if( $textid == 'withdrawalrequest-subject') {
        $text = "[ADMIN] A withdrawal was requested";
    } else if( $textid == 'withdrawalrequest-body') {
        $text = "A funds withdrawal was requested by user %USERNAME%.  ".
            "Please make sure that it gets paid within one business day.";
    } else {
        return array(2,"No such text ID: $textid");
    }

    // Perform macro replacement
    $text = preg_replace("|%([A-Z][A-Z0-9]*)%|e",
        "\$macros[strtolower('\\1')]", $text);

    return array(0,$text);
}

function ff_createsession()
{
    $time = time();
    list($rc,$secret) = ff_config("secret");
    if( $rc) return array($rc,$secret);

    $seq = sql_nextval( "sid_seq");
    if( $seq === false) return private_dberr();

    $sid = "$seq-".substr(sha1("$secret/$seq/$time"),0,12);
    $secure_sid = "$seq-".sha1("$secret-secure/$seq/$time");

    $qu = sql_exec("insert into sessions (sid,secure_sid,time,accesstime) ".
        "values('$sid','$secure_sid',$time,$time)");
    if( $qu === false) return private_dberr();

    return array(0,$sid,$secure_sid);
}

function ff_getsessioninfo( $sid)
{
    $sid = scrub($sid);
    $qu = sql_exec( "select * from sessions where sid='$sid'");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) return array(2,"No such session: $sid");

    $row = sql_fetch_array( $qu, 0);

    // Freshen up the access time if necessary
    $time = time();
    if( intval( $row["accesstime"]) < $time-60) {
        $qu = sql_exec( "update sessions ".
            "set accesstime=$time where sid='$sid'");
        if( $qu === false) return private_dberr();
    }

    return array(0,array(
        "sid" => $row["sid"],
        "secure_sid" => $row["secure_sid"],
        "time" => intval($row["time"]),
        "username" => "$row[username]",
        "auth" => "$row[auth]"));
}

function ff_setsessioninfo( $sid, $username=false)
{
    $sid = scrub($sid);
    $changes = array();

    if( $username !== false) {
        if( $username !== '') {
            list($rc,$memberinfo) = ff_getmemberinfo($username);
            if( $rc) return array($rc,$memberinfo);
            $changes[] = "username='".sql_escape($memberinfo["username"])."'";
            $changes[] = "auth=".($memberinfo["auth"]?
                "'".sql_escape($memberinfo["auth"])."'":"null");
        } else {
            $changes[] = "username=''";
            $changes[] = "auth=null";
        }
    }

    if( sizeof($changes)) {
        $qu = sql_exec( "update sessions set ".
            join(',',$changes)." where sid='$sid'");
        if( $qu === false) return private_dberr();
    }

    return array(0,"Success");
}

function ff_currencies()
{
    if( !isset( $GLOBALS["FF_CURRENCIES"])) {
        $qu = sql_exec( "select * from currencies order by code");
        if( $qu === false) return private_dberr();
        if( sql_numrows($qu) == 0) return array(1,"No currencies defined");

        $GLOBALS["FF_CURRENCIES"] = array();
        for( $i=0; $i < sql_numrows( $qu); $i++) {
            $row = sql_fetch_array( $qu, $i);
            $currency = array(
                "code" => $row["code"],
                "name" => $row["name"],
                "decimal_places" => intval($row["decimal_places"]),
                "prefix" => $row["prefix"],
                "credit_ratio" => $row["credit_ratio"],
                "exchange_rate" => $row["exchange_rate"]);
            $currency["multiplier"] =
                intval("1".str_repeat("0",$currency["decimal_places"]));
            $currency["mincontrib"] =
                ceil(1/$currency["credit_ratio"])*$currency["multiplier"];
            $GLOBALS["FF_CURRENCIES"][$row["code"]] = $currency;
        }

        // Add a fake currency except on the production system
        if( $_SERVER["HTTP_HOST"] !== 'www.fossfactory.org' ||
            substr($_SERVER["SCRIPT_NAME"],1,1) === '~') {
            $GLOBALS["FF_CURRENCIES"]["FFC"] = array(
                "code" => "FFC",
                "name" => "FOSS Factory Clams",
                "decimal_places" => 2,
                "prefix" => "FFC ",
                "credit_ratio" => 1,
                "exchange_rate" => 0.9,
                "multiplier" => 100,
                "mincontrib" => 100);
        }
    }
    return array(0,$GLOBALS["FF_CURRENCIES"]);
}

function ff_config( $name, $default=false)
{
    if( !isset( $GLOBALS["FF_CONFIG"])) {
        $qu = sql_exec( "select * from config");
        if( $qu === false) return private_dberr();

        $GLOBALS["FF_CONFIG"] = array();
        for( $i=0; $i < sql_numrows( $qu); $i++) {
            $row = sql_fetch_array( $qu, $i);
            $GLOBALS["FF_CONFIG"]["$row[name]"] = "$row[value]";
        }
    }
    if( !isset( $GLOBALS["FF_CONFIG"][$name])) {
        if( $default !== false) return array(0,$default);
        return array(2,"No such config variable: $name");
    }

    return array(0,$GLOBALS["FF_CONFIG"][$name]);
}

// This function creates a new dispute. The plaintiff is the member with the
// specified username. The defendant is the project lead of the project
// with the given ID.
//
// This function locks the following tables in this order:
//
//  <projects>
//  <duties>
// 
function ff_createdispute( $projectid, $username, $type, $object, $body)
{
    if( $type === 'badaccept') {
        $subject = "An invalid submission was accepted";
    } else if( $type === 'badreject') {
        $subject = "A valid submission was rejected";
    } else if( $type === 'badchange') {
        $subject = "The requirements were changed in a bad way";
    } else {
        return array(4,"Unknown complaint type: $type");
    }

    list($rc,$projinfo) = ff_getprojectinfo( $projectid);
    if( $rc) return array($rc,$projinfo);

    list($rc,$memberinfo) = ff_getmemberinfo( $username);
    if( $rc) return array($rc,$memberinfo);

    $did = sql_nextval( "disputes_id_seq");
    if( $did === false) return private_dberr();

    $nid = intval(substr($projectid,1));

    list($rc,$sep) = private_disputeseparator($did);
    if( $rc) return array($rc,$sep);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec( "insert into disputes ".
        "(id,projectid,plaintiff,subject,body,status,type,object,created) ".
        "values ($did,$nid,'".sql_escape($username)."','".
        sql_escape($subject)."','','plaintiff',".
        "'$type','".sql_escape($object)."',".time().")");
    if( $qu === false) return private_dberr();

    // Add the initial argument
    // NOTE: Although private_addargument() locks the <disputes> table,
    // ff_createdispute() doesn't have to claim to lock that table because
    // in this case it's a newly inserted record that is being locked.
    list($rc,$deadline) = private_addargument($did,$username,$body,$sep);
    if( $rc) return array($rc,$deadline);

    //notify the lead of this dispute
    $macros = array(
        "projectname" => $projinfo["name"],
        "username" => $username,
        "deadline" => date("D F j, H:i:s T",$deadline),
        "subject" => $subject,
        "body" => $body,
    );
    $url = "dispute.php?id=d$did&requser=$projinfo[lead]";
    $tag = ($deadline?"newduty2":"newduty");
    $rc = al_triggerevent("lead:p$nid",$url,"$tag-newdispute",$macros);
    if( $rc[0]) return $rc;

    //notify everyone else of this dispute
    $macros = array(
        "projectname" => $projinfo["name"],
        "username" => $username,
        "subject" => $subject,
        "body" => $body,
    );
    $url = "dispute.php?id=d$did";
    $rc = al_triggerevent( "watch:p$nid-news\\".
        "member:".scrub($username).",lead:p$nid",
        $url, "pnews-newdispute", $macros, 2);
    if( $rc[0]) return $rc;

    list($rc,$err) = private_commit();
    if( $rc) return array($rc,$err);

    return array(0,"d$did");
}

function private_disputeseparator( $did) {
    list($rc,$secret) = ff_config("secret");
    if( $rc) return array($rc,$secret);
    return array(0,"\n--".sha1("$secret--$did")."--\n");
}

// This will return an associative array containing the following fields:
// - "disputeid" => The dispute ID
// - "projectid" => The project ID
// - "plaintiff" => The username of the plaintiff
// - "subject" => The subject of the dispute
// - "status" => the status of the dispute, one of
//         "plaintiff","defendant","cancelled",
//         "waiting","conflict","deliberating","decided"
// - "arguments" => A zero-based sequential array of arguments.  Each argument
//   is an associative array containing the following items:
//     - "body" => The written body of the argument
//     - "username" => The username of the member who posted the argument.
//       This will always be either the plaintiff or the project lead.
//     - "time" => The Unix timestamp when the argument was posted.
function ff_getdisputeinfo( $disputeid)
{
    $did = intval(substr($disputeid,1));

    $qu = sql_exec( "select * from disputes where id=$did");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) return array(2,"No such dispute: $disputeid");

    $row = sql_fetch_array( $qu, 0);

    list($rc,$sep) = private_disputeseparator($did);
    if( $rc) return array($rc,$sep);

    $pieces = ($row["body"]==="") ?
        array() : explode($sep,$row["body"]);

    $arguments = array();
    foreach( $pieces as $piece) {
        if( ereg("^([0-9]+)/([^\n]+)\n(.*)$", $piece, $args)) {
            $arguments[] = array(
                "body" => $args[3],
                "username" => $args[2],
                "time" => intval($args[1]));
        } else {
            $arguments[] = array("body" => $piece);
        }
    }

    return array(0,array(
        "disputeid" => "d$did",
        "created" => intval($row["created"]),
        "concluded" => intval($row["concluded"]),
        "assignedto" => "$row[assignedto]",
        "decided" => intval($row["decided"]),
        "decision" => "$row[decision]",
        "projectid" => "p".intval($row["projectid"]),
        "plaintiff" => "$row[plaintiff]",
        "subject" => "$row[subject]",
        "status" => "$row[status]",
        "type" => "$row[type]",
        "object" => "$row[object]",
        "arguments" => $arguments));
}

function ff_getprojectdisputes( $projectid)
{
    $nid = intval(substr($projectid,1));

    $qu = sql_exec( "select * from disputes where projectid=$nid");
    if( $qu === false) return private_dberr();

    $disputes = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $disputes[] = array(
            "disputeid" => "d$row[id]",
            "created" => intval($row["created"]),
            "concluded" => intval($row["concluded"]),
            "assignedto" => "$row[assignedto]",
            "decided" => intval($row["decided"]),
            "decision" => "$row[decision]",
            "projectid" => "p".intval($row["projectid"]),
            "plaintiff" => "$row[plaintiff]",
            "subject" => "$row[subject]",
            "type" => "$row[type]",
            "object" => "$row[object]",
            "status" => "$row[status]");
    }

    return array(0,$disputes);
}

function ff_getactivedisputes()
{
    $qu = sql_exec( "select * from disputes where status='deliberating' ".
        "order by assignedto,concluded,id");
    if( $qu === false) return private_dberr();

    $disputes = array();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $disputes[] = array(
            "disputeid" => "d$row[id]",
            "created" => intval($row["created"]),
            "concluded" => intval($row["concluded"]),
            "assignedto" => "$row[assignedto]",
            "decided" => intval($row["decided"]),
            "decision" => "$row[decision]",
            "projectid" => "p".intval($row["projectid"]),
            "plaintiff" => "$row[plaintiff]",
            "subject" => "$row[subject]",
            "type" => "$row[type]",
            "object" => "$row[object]",
            "status" => "$row[status]");
    }

    return array(0,$disputes);
}

// This function starts or stops the submission clock depending on the
// presence and/or status of existing disputes.  In general, the clock
// should be stopped whenever an active dispute is not awaiting action
// from the plaintiff.
//
// This function must be called in a transaction.
// It locks the following tables:
//
// <projects>
// (<disputes> is not locked, but relies on protection of <projects> lock)
// <submissions> (The accepted submission, if any)
//
function private_submissionclock( $nid)
{
    $qu = sql_exec("select * from projects where id=$nid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    $status = "$row[status]";
    $payout_time = intval($row["payout_time"]);

    if( $status !== 'accept') return array(0,"There is nothing to do");

    // Check for disputes in a state that should keep the
    // submission clock stopped.
    $qu = sql_exec("select id from disputes where projectid=$nid and ".
        "status in ('defendant','waiting','deliberating') limit 1");
    if( $qu === false) return private_dberr();

    if( sql_numrows($qu) > 0) {
        // Make sure the clock is stopped
        if( $payout_time == 0) return array(0,"The clock is already stopped");

        $qu = sql_exec("update projects set payout_time=0 where id=$nid");
        if( $qu === false) return private_dberr();

        $remaining = $payout_time - time();
        if( $remaining < 1) $remaining = 1;

        $qu = sql_exec("update submissions set ".
            "payout_time_remaining=$remaining ".
            "where projectid=$nid and status='accept'");
        if( $qu === false) return private_dberr();
    } else {
        // Make sure the clock is running
        if( $payout_time) return array(0,"The clock is already running");

        $qu = sql_exec("select * from submissions ".
            "where projectid=$nid and status='accept' for update");
        if( $qu === false) return private_dberr();
        if( sql_numrows($qu) != 1) {
            return array(1,"Missing or too many accepted submissions");
        }
        $row = sql_fetch_array($qu,0);
        $payout_time = time() + intval($row["payout_time_remaining"]);
        $acceptid = intval($row["id"]);

        $qu = sql_exec("update projects ".
            "set payout_time=$payout_time where id=$nid");
        if( $qu === false) return private_dberr();

        $qu = sql_exec("update submissions ".
            "set payout_time_remaining=0 where id=$acceptid");
        if( $qu === false) return private_dberr();
    }

    return array(0,"Success");
}

// This function will append the next argument to the end of a dispute, and
// alert the other player that it is his turn to respond. $argument should
// be a plain-text string. This function will return error code 5
// (Permission Denied) if the username is not the participant whose turn it
// is.
//
// This function locks the following tables in this order:
//
// <projects>
// <disputes>
// <submissions> (the accepted submission, if any)
// <duties>
//
function ff_addargument( $disputeid, $username, $argument)
{
    $did = intval(substr($disputeid,1));

    list($rc,$sep) = private_disputeseparator($did);
    if( $rc) return array($rc,$sep);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    list($rc,$deadline) = private_addargument(
        $did, $username, $argument, $sep);
    if( $rc) return private_dberr($rc,$deadline);

    $qu = sql_exec( "select * from disputes where id=$did");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such dispute: d$did");
    }
    $row = sql_fetch_array( $qu, 0);
    $projectid = intval($row["projectid"]);
    $status = $row["status"];
    $subject = $row["subject"];
    $plaintiff = $row["plaintiff"];

    $qu = sql_exec( "select * from projects where id=$projectid");
    if( $qu === false || sql_numrows( $qu) == 0) return private_dberr(1);
    $row = sql_fetch_array( $qu, 0);

    if( $status === 'defendant') {
        //notify the lead of this dispute
        $macros = array(
            "projectname" => $row["name"],
            "username" => $username,
            "deadline" => date("D F j, H:i:s T",$deadline),
            "subject" => $subject,
        );
        $url = "dispute.php?id=d$did&requser=$row[lead]";
        $tag = ($deadline?"newduty2":"newduty");
        $rc = al_triggerevent( "lead:p$projectid", $url,
            "$tag-dispute", $macros);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    } else {
        //notify the plaintiff that the project lead has responded.
        $macros = array(
            "projectname" => $row["name"],
            "username" => $username,
            "subject" => $subject,
        );
        $url = "dispute.php?id=d$did&requser=$plaintiff";
        $rc = al_triggerevent( "member:$plaintiff", $url,
            "continuedispute", $macros);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    }

    return private_commit();
}

// Must be called in a transaction.
// Acquires locks on records in the following tables, in this order:
//  <projects>
//  <disputes>
//  <submissions> (the accepted submission, if any)
//  <duties>
//
function private_addargument( $did, $username, $argument, $sep)
{
    // Get the project ID.  Don't get a lock on the dispute record yet,
    // because we need a lock on the project record first.
    $qu = sql_exec( "select * from disputes where id=$did");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) {
        return array(2,"No such dispute: d$did");
    }
    $row = sql_fetch_array( $qu, 0);
    $projectid = intval($row["projectid"]);

    // Determine the project lead, and get a lock on the project record
    // in order to prevent deadlocks.
    $qu = sql_exec("select lead from projects where id=$projectid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    $lead = "$row[lead]";

    // Now that we've locked the project record we can re-fetch the
    // information from the dispute record.
    $qu = sql_exec( "select * from disputes where id=$did for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) {
        return array(2,"No such dispute: d$did");
    }
    $row = sql_fetch_array( $qu, 0);

    $body = $row["body"];
    if( $body !== '') $body .= $sep;
    $body .= time()."/$username\n$argument";

    $deadline = 0;

    // Whose turn is it?
    if( $row["status"] == 'plaintiff') {
        if( $username !== $row["plaintiff"]) {
            return array(5,"It's $row[plaintiff]'s turn to respond.");
        }

        $qu = sql_exec( "update disputes set ".
            "status='defendant',body='".sql_escape($body)."' where id=$did");
        if( $qu === false) return private_dberr();

        list($rc,$deadline) = private_createduty(
            $projectid, "dispute-defendant", $did, 129600);
        if( $rc) return array($rc,$deadline);
    } else if( $row["status"] == 'defendant') {
        if( $username !== $lead) {
            return array(5,"It's $lead's turn to respond.");
        }

        $qu = sql_exec( "update disputes set ".
            "status='plaintiff',body='".sql_escape($body)."' where id=$did");
        if( $qu === false) return private_dberr();

        $rc = private_destroyduty($projectid, "dispute-defendant", $did);
        if( $rc[0]) return array($rc[0],$rc[1]);
    } else {
        return array(5,"The dispute status is '$row[status]'");
    }

    list($rc,$err) = private_submissionclock( $projectid);
    if( $rc) return array($rc,$err);

    return array(0,$deadline);
}

// This function will conclude the given dispute, and alert an arbiter that
// it is ready for his review. This function will return error code 5
// (Permission Denied) if the username is not the participant whose turn it
// is.
//
// This function locks the following tables in this order:
//
// <projects>
// <disputes>
//
function ff_concludedispute( $disputeid, $username)
{
    include_once("diff.php");

    $did = intval(substr($disputeid,1));

    $qu = sql_exec( "select projectid from disputes where id=$did");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such dispute: $disputeid");
    }
    $row = sql_fetch_array( $qu, 0);
    $projectid = intval($row["projectid"]);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $rc = private_concludedispute( $projectid, $did, $username);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    return private_commit();
}

// This function should be called from a transaction.  It locks the
// following tables in this order:
//
// <projects>
// <disputes>
//
function private_concludedispute( $projectid, $did, $username)
{
    $qu = sql_exec("select lead,reqmts,name,status from projects ".
        "where id=$projectid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    $lead = $row["lead"];
    $reqmts = $row["reqmts"];
    $projectname = $row["name"];
    $projectstatus = $row["status"];

    $qu = sql_exec( "select * from disputes where id=$did for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) {
        return array(2,"No such dispute: d$did");
    }
    $row = sql_fetch_array( $qu, 0);

    // Whose turn is it?
    if( $row["status"] == 'plaintiff') {
        if( $username !== $row["plaintiff"]) {
            return array(5,"It's $row[plaintiff]'s turn to respond.");
        }
    } else if( $row["status"] == 'defendant') {
        $projectid = intval($row["projectid"]);

        if( $username !== $lead) {
            return array(5,"It's $row[lead]'s turn to respond.");
        }

        $rc = private_destroyduty( $projectid, "dispute-defendant", $did);
        if( $rc[0]) return $rc;
    } else if( $row["status"] != 'waiting') {
        return array(5,"The dispute status is '$row[status]'");
    }

    $status = "deliberating";
    if( $row["type"] == 'badchange') {
        // The dispute is over a requirements change

        // If there is another requirements change dispute in conflict or
        // deliberating state, then this dispute should just enter the
        // 'waiting' state.
        $qu = sql_exec("select id from disputes where ".
            "projectid=$projectid and type='badchange' and ".
            "status in ('conflict','deliberating') limit 1");
        if( sql_numrows( $qu) > 0) {
            $status = "waiting";
        } else {
            include_once("diff.php");

            // If the proposed patch no longer merges cleanly then the dispute
            // should enter the 'conflict' state.
            $patch = ereg_replace("^[^:]*:","",$row["object"]);
            list($rc,$before) = patchText( $reqmts, $patch, 1);
            if( $rc == 7) $status = "conflict";
            else if( $rc) return array($rc,$before);
        }
    }

    if( $status == 'conflict' && $row["status"] != 'plaintiff') {
        // Notify the plaintiff that the merge requires conflict resolution
        $macros = array(
            "projectname" => $projectname,
            "subject" => $row["subject"],
        );
        $url = "dispute.php?id=d$did&requser=$row[plaintiff]";
        $rc = al_triggerevent( "member:$row[plaintiff]", $url,
            "resolveconflict", $macros);
        if( $rc[0]) return $rc;
    }

    $qu = sql_exec( "update disputes set status='$status'".
        ($row["concluded"] ? "" : ",concluded=".time()).
        " where id=$did");
    if( $qu === false) return private_dberr();

    list($rc,$err) = private_submissionclock( $projectid);
    if( $rc) return array($rc,$err);

    if( $status == 'deliberating' && $row["type"] == 'badchange') {
        // Remove any deadlines on change proposals, since it is now
        // impossible for the project lead to accept them.
        $rc = private_setdutydeadline( $projectid, $projectstatus);
        if( $rc[0]) return $rc;
    }

    if( $status == 'deliberating') {
        // Notify the arbiters that there's a new dispute pending
        $macros = array(
            "projectname" => $projectname,
            "subject" => $row["subject"],
        );
        $url = "arbitration.php";
        $rc = al_triggerevent( "arbiter:", $url, "newactivedispute", $macros);
        if( $rc[0]) return $rc;
    }

    return array(0,"Success");
}

// This function tries to resolve the merge conflict for a given dispute.
// If the provided patch still doesn't merge cleanly then the dispute will
// remain in the conflict state.
//
// This function locks the following tables in this order:
//
// <projects>
// <disputes>
//
function ff_resolvemergeconflict( $disputeid, $username, $patch)
{
    include_once("diff.php");

    $did = intval(substr($disputeid,1));

    $qu = sql_exec( "select projectid from disputes where id=$did");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such dispute: $disputeid");
    }
    $row = sql_fetch_array( $qu, 0);
    $projectid = intval($row["projectid"]);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("select reqmts,status from projects ".
        "where id=$projectid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr(1);
    $row = sql_fetch_array( $qu, 0);
    $reqmts = $row["reqmts"];
    $projectstatus = $row["status"];

    $qu = sql_exec( "select * from disputes where id=$did for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such dispute: $disputeid");
    }
    $row = sql_fetch_array( $qu, 0);

    if( $row["status"] != 'conflict') {
        return private_dberr(5,"The dispute is not in the conflict state");
    }

    if( $username !== $row["plaintiff"]) {
        return private_dberr(5,"Only the plaintiff can resolve the conflict");
    }

    // If the new patch still doesn't merge cleanly then stay
    // in the conflict state.
    $status = "deliberating";
    list($rc,$before) = patchText( $reqmts, $patch, 1);
    if( $rc == 7) $status = "conflict";
    else if( $rc) return private_dberr($rc,$before);

    $object = intval($row["object"]).":$patch";

    $qu = sql_exec( "update disputes set ".
        "object='".sql_escape($object)."',status='$status' where id=$did");
    if( $qu === false) return private_dberr(1);

    list($rc,$err) = private_submissionclock( $projectid);
    if( $rc) return private_dberr($rc,$err);

    if( $status == 'deliberating' && $row["type"] == 'badchange') {
        // Remove any deadlines on change proposals, since it is now
        // impossible for the project lead to accept them.
        $rc = private_setdutydeadline( $projectid, $projectstatus);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    }

    return private_commit();
}

// This function will cancel the given dispute. It will return error code 5
// (Permission Denied) if the username is not the username of the
// plaintiff.
function ff_canceldispute( $disputeid, $username)
{
    $did = intval(substr($disputeid,1));

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    $qu = sql_exec( "select * from disputes where id=$did for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such dispute: $disputeid");
    }
    $row = sql_fetch_array( $qu, 0);

    if( $username !== $row["plaintiff"]) {
        return private_dberr(5,"$username is not the plaintiff.");
    }

    if( $row["status"] == 'cancelled') {
        sql_exec("rollback");
        return array(0,"Already cancelled");
    }

    $qu = sql_exec( "update disputes set status='cancelled' where id=$did");
    if( $qu === false) return private_dberr(1);

    $rc = private_destroyduty( $row["projectid"], "dispute-defendant", $did);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    return private_commit();
}

// This function begins a transaction and ensures that the given user
// is the project lead for the given project ID.  It locks the
// project record to ensure that the lead can't change during the
// course of the transaction.
function private_begin_authorize( $username, $id)
{
    $nid = intval(substr($id,1));

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    if( "$username" === '') {
        return private_dberr(5,"You are not logged in.");
    }

    $qu = sql_exec( "select * from projects ".
        "where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows( $qu) == 0) {
        return private_dberr(2,"No such project: $id");
    }
    $row = sql_fetch_array( $qu, 0);

    if( $username !== $row["lead"]) {
        return private_dberr(5,"You are not the project lead.");
    }

    return array(0,$row);
}

//code submission functions:ff_submitcode,ff_getsubmissions,ff_getsubmissioninfo
//                          ff_acceptsubmission,ff_rejectsubmission


//-stores the info in the database
//-alerts the lead to view submitted code
//-$files is an array of associative arrays.  Each record contains the following fields:
//    'pathname' => The absolute location of the file. (Eg, /tmp/uploads/SHAA08e2)
//    'filename' => The filename to use on the site. (Eg, foobar-1.3.0.tar.gz)
//    'description' => A brief description of the file's purpose.
function ff_submitcode($username, $files, $comments, $projectID) {

    $nid = intval(substr($projectID,1));

    // Make sure the submissions directory exists
    @mkdir( "$GLOBALS[DATADIR]/submissions");

    $submissionID = sql_nextval( "submissions_id_seq");
    if( $submissionID === false) return private_dberr();

    $destdir = "$GLOBALS[DATADIR]/submissions/$submissionID";
    mkdir( $destdir);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // We need to lock all of the project's ancestors in order
    // to be able to update held amounts safely.
    $rc = private_lock_from_root( $nid);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    $qu = sql_exec("select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu)==0) return private_dberr(2,"No such project: p$nid");
    $row = sql_fetch_array($qu,0);
    $projectname = $row['name'];
    $status = $row["status"];
    if( $status === 'complete')
        return private_dberr(5,"Project already complete");
    $reqmts = $row["reqmts"];
    $bounty_portions = $row["bounty_portions"];
    $lead = $row["lead"];

	$i=0;
    foreach ($files as $key => $file) {
		$i++;
        $filesize = filesize($file['pathname']);
        if( @copy($file['pathname'],"$destdir/$i") === false)
            return private_dberr(4, "Can't rename file: $file[pathname]");
        $qu = sql_exec("insert into submission_files ".
            "(id,seq,filename,filesize,time,description) ".
            "values ($submissionID,$i,'".
            sql_escape($file['filename'])."',$filesize,".time().
            ",'".sql_escape($file['description'])."')");
        if( $qu === false) return private_dberr(1);
    }

    // If there is a current submission by this same member whose
    // amounts are still on hold, then we can use its held amounts.
    $qu = sql_exec("select max_money_list(held_amounts) from (".
        "select held_amounts from submissions ".
            "where projectid=$nid and username='".sql_escape($username).
            "' and held_amounts ~ '[1-9]' union ".
        "select '$bounty_portions') foo");
    if( $qu === false || sql_numrows($qu) != 1) return private_dberr(1);
    $row = sql_fetch_array($qu,0);
    $held_amounts = $row["max_money_list"];

    $qu = sql_exec("insert into submissions ".
        "(id,projectid,username,time,comments,".
        "status,numfiles,reqmts,held_amounts) ".
        "values ($submissionID,$nid,'".sql_escape($username)."',".time().
        ",'".sql_escape($comments)."','pending',".sizeof($files).
        ",'".sql_escape($reqmts)."','$held_amounts')");
    if( $qu === false) return private_dberr(1);

    if( $status === 'pending') {
        $qu = sql_exec("update projects set status='submitted' where id=$nid"); 
        if ($qu===false) return private_dberr(1);
    }

    list($rc,$err) = private_submissionclock( $nid);
    if( $rc) return private_dberr($rc,$err);

    list($rc,$err) = private_update_held_amounts( $nid);
    if( $rc) return private_dberr($rc,$err);

    list($rc,$deadline) = private_createduty(
        $nid, "code submission", $submissionID, 432000);
    if( $rc) return private_dberr($rc,$deadline);

    //trigger the event for the project authorities
    //of the parent of the project that will be created
    $macros = array(
        "projectname" => $projectname,
        "submitter" => $username,
        "deadline" => date("D F j, H:i:s T",$deadline)
    );
    $url = projurl("p$nid","tab=submissions&requser=$lead");
    $tag = ($deadline?"newduty2":"newduty");
    $rc = al_triggerevent("lead:p$nid",$url,"$tag-submission",$macros);
    if($rc[0]) return $rc;

    // Notify anyone watching the project
    // that code has been submitted for it
    $macros = array(
        "projectname" => $projectname,
        "submitter" => $username,
    );
    $url = projurl("p$nid","tab=submissions");
    $rc = al_triggerevent( "watch:p$nid-news\\".
        "member:".scrub($username).",member:$lead",
        $url, "pnews-submission", $macros);
    if($rc[0]) return $rc;

    return private_commit($submissionID);
}

//-returns an array of submission descriptions, look at the next function:
function ff_getsubmissions($projID){
    $nid = intval(substr($projID,1));
    $submissionsqu = sql_exec("select * from submissions where projectid = $nid order by id");
    if($submissionsqu === false) return private_dberr();
   
    //get all the submissions of this project
    $submissions = array();
    for( $i=0; $i < sql_numrows( $submissionsqu); $i++) {
        $submissionsrow = sql_fetch_array($submissionsqu,$i);
        //for each submission, get the submission files
        $filesqu = sql_exec("select filename,filesize,description from submission_files where id=".intval($submissionsrow['id']));
        if($filesqu === false) return private_dberr();
            $files = array();
		for ($j=0;$j<sql_numrows($filesqu);$j++) {
        	$filesrow = sql_fetch_array($filesqu,$j);
        
        	$files[] = array ("filename" => $filesrow['filename'],
            	               "filesize" => $filesrow['filesize'],
                	           "description" => $filesrow['description']
                    	       );
		}

        $submissions[] = array("id" => $submissionsrow['id'],
                                "username" => $submissionsrow['username'],
                                "files" => $files,
                                "date" => $submissionsrow['time'],
                                "comments" => $submissionsrow['comments'],
                                "rejectreason" => $submissionsrow['reject_reason'],
                                "reqmts" => $submissionsrow['reqmts'],
                                "status" => $submissionsrow['status']
                                );
    }
    return array(0,$submissions);
}

function ff_getrelcodeinfo($projectid,$status) {
	//find out who is being evaluated now
    $nid = intval(substr($projectid,1));
	$qu=sql_exec("select username,id from submissions where status='".sql_escape($status)."' and projectid=$nid order by time asc limit 1");
	if($qu===false) return private_dberr();
    if( sql_numrows( $qu) == 0) return array(2,"No current submissions");
	$row=sql_fetch_array($qu,0);
	$qu2=sql_exec("select username from submissions where status='".sql_escape($status)."'".
				"and projectid=$nid  order by time asc");
	if($qu2===false) return private_dberr();
	$evalcodeinfo= array('username'=>$row['username'],
						 'numothersubmissions'=>(sql_numrows($qu2)-1),
                         'submissionid'=>$row['id']);
	return array(0,$evalcodeinfo);
}
function ff_getsuccessfulsubmitter($projectid) {
    $nid = intval(substr($projectid,1));
	//the successful bidder is the last one who got his submission approved 
	$qu=sql_exec("select username from submissions where projectid=$nid ".
				"and status='complete' order by time desc limit 1");	
	if ($qu===false) return private_dberr();
	$row=sql_fetch_array($qu,0);
	$qu2=sql_exec("select payout_time from projects where id=$nid");
	$row2=sql_fetch_array($qu2,0);
	$successfulsubmitter = array('username'=>$row['username'],
								 'payout_time'=>$row2['payout_time']);
	return array(0,$successfulsubmitter);
}


function ff_showsubmissionfile( $submissionID, $seq) {
    return private_showfile('submissions',$submissionID,$seq);
}

function ff_showpostattachment($attachmentID,$seq) {
    return private_showfile('attachments',$attachmentID,$seq); 
}
//displays a list of attachments along with [view] and [download] 
//note that postid can be an id of a post, or an id of a project 
//note: postid must be of type int
function ff_listattachments($postid) {
    $qu = sql_exec("select * from post_attachments where postid = ".intval($postid));
    if ($qu===false) return private_dberr();
    for ($i=0;$i<sql_numrows($qu);$i++) {
       $row = sql_fetch_array($qu,$i);
       echo "".htmlentities($row['filename'])."&nbsp;<a href='displayfile.php/".$row['postid']."/".($i+1)."/".urlencode($row['filename'])."'>[download]</a>&nbsp;";
       echo "<a href='viewfile.php/".$row['postid']."/".($i+1)."/".urlencode($row['filename'])."'>[view]</a><br>\n";
    }
    return array(0,"attachments displayed successfully");
}

function ff_listprojectattachments($projectid) {
    $nid = intval(substr($projectid,1));
    $qu = sql_exec("select * from post_attachments where projectid = $nid");
    if ($qu===false) return private_dberr();
    for ($i=0;$i<sql_numrows($qu);$i++) {
       $row = sql_fetch_array($qu,$i);
       echo "".htmlentities($row['filename'])."&nbsp;<a href='displayfile.php/$row[postid]/$row[seq]/".urlencode($row['filename'])."'>[download]</a>&nbsp;";
       echo "<a href='viewfile.php/$row[postid]/$row[seq]/".urlencode($row['filename'])."'>[view]</a><br>\n";
    }
    return array(0,"attachments displayed successfully");
}

//scans the body for filenames and makes them linkable
function ff_attachtobody($postid,$body) {
    $qu = sql_exec("select * from post_attachments where postid=".intval($postid));
    if ($qu===false) return private_dberr();
    for ($i=0;$i<sql_numrows($qu);$i++) {
        $arow = sql_fetch_array($qu,$i);
        $expr =str_replace('.','\.',$arow['filename']);
        $body = ereg_replace($expr,"<a href='viewfile.php/$arow[postid]/".($i+1)."/".urlencode($arow['filename'])."'>".htmlentities($arow['filename'])."</a>",$body);
    }
    return array(0,$body);
}

function ff_attachtoproject($projectid,$body) {
    $nid = intval(substr($projectid,1));
    $qu = sql_exec("select * from post_attachments where projectid=$nid");
    if ($qu===false) return private_dberr();
    for ($i=0;$i<sql_numrows($qu);$i++) {
        $arow = sql_fetch_array($qu,$i);
        $expr =str_replace('.','\.',$arow['filename']);
        $body = ereg_replace($expr,"<a href='viewfile.php/$arow[postid]/$arow[seq]/".urlencode($arow['filename'])."'>".htmlentities($arow['filename'])."</a>",$body);
    }
    return array(0,$body);
}

//scans the requirements body for filenames and makes them linkable
function ff_attachtoreqmt($projectid,$body) {
    $nid = intval(substr($projectid,1));
    $qu = sql_exec("select * from post_attachments where postid=$nid");
    if ($qu===false) return private_dberr();
    for ($i=0;$i<sql_numrows($qu);$i++) {
        $arow = sql_fetch_array($qu,$i);
        $expr =str_replace('.','\.',$arow['filename']);
        $body = ereg_replace($expr,"<a href='viewfile.php/$arow[postid]/".($i+1)."/".urlencode($arow['filename'])."'>".htmlentities($arow['filename'])."</a>",$body);
    }
    return array(0,$body);


}

function private_showfile($directory,$id,$seq) {
    $submissionID = intval($submissionID);
    $seq = intval($seq);

    $file = "$GLOBALS[DATADIR]/$directory/$id/$seq";

    if( @readfile( $file) === false) {
        if( !is_dir( dirname( $file)))
            return array(2,"No such file: $id");
        if( !is_file( $file))
            return array(2,"No such file: $id/$seq");
        return array(1,"Error reading file $file");
    }

    return array(0,"Success");
}

//-returns an associative array with the following elements:
//submissionid
//username - the username of the submitter
//files - An array of file records.  Each record contains:
//    'filename' => The filename
//    'filesize' => The file size in bytes
//    'description' => A brief description of the file's purpose.
//date
//comments
//status
function ff_getsubmissioninfo($submissionID) {
    $submissionqu = sql_exec("select * from submissions where id = ".intval($submissionID));
    if($submissionqu === false) return private_dberr();
    if( sql_numrows( $submissionqu) == 0) return array(2,"No such submission: $submissionID");

    $filesqu = sql_exec("select filename,filesize,description  from submission_files where id=".intval($submissionID));
    if($filesqu === false) return private_dberr();
	
	for ($i=0;$i<sql_numrows;$i++) {
	   $filesrow = sql_fetch_array($filesqu,$i);
       $files[] = array ("filename" => $filesrow['filename'],
                           "filesize" => $filesrow['filesize'],
                           "description" => $filesrow['description']
                           );
       }

    $submissionrow = sql_fetch_array( $submissionqu, 0);
    $submission = array("submissionid" => $submissionrow['id'],
                            "username" => $submissionrow['username'],
                            "files" => $files,
                            "date" => $submissionrow['time'],
                            "comments" => $row['comments'],
                            "status" => $row['status']
                            );
    return array(0,$submission);
}

function ff_applydisputedecision( $username, $disputeid, $decision)
{
    $did = intval(substr($disputeid,1));

    // Determine the project ID
    $qu = sql_exec("select * from disputes where id=$did");
    if( $qu === false) return private_dberr();
    $row = sql_fetch_array( $qu, 0);
    $nid = intval($row["projectid"]);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Lock the project record to prevent deadlocks
    $qu = sql_exec("select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0) return private_dberr(2,"No such project: $nid");
    $row = sql_fetch_array( $qu, 0);
    $projectname = $row["name"];

    // Now that we've locked the project record we can safely get info
    // from the disputes record
    $qu = sql_exec("select * from disputes where id=$did for update");
    if( $qu === false) return private_dberr(1);
    $row = sql_fetch_array( $qu, 0);

    // Make sure that the person doing the action is the arbiter
    if( $row["assignedto"] !== "arbiter:$username")
        return private_dberr(5,"Unauthorized");

    if( $decision === 'reject') {
        // Make sure the dispute type is correct.
        if( $row["type"] !== 'badaccept')
            return private_dberr(4,"Invalid decision string");

        // Reject the submission
        $rc = private_rejectsubmission( $nid,
            intval($row["object"]), "Outcome of dispute $disputeid.");
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    } else if( $decision === 'accept') {
        // Make sure the dispute type is correct.
        if( $row["type"] !== 'badreject')
            return private_dberr(4,"Invalid decision string");

        // Accept the submission
        $rc = private_acceptsubmission( $nid,
            intval($row["object"]), "Outcome of dispute $disputeid.");
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    } else if( $decision === 'cancelchange') {
        // Make sure the dispute type is correct.
        if( $row["type"] !== 'badchange')
            return private_dberr(4,"Invalid decision string");

        // Get the patch and post ID
        $patch = ereg_replace("^[^:]*:","",$row["object"]);
        $postid = intval($row["object"]);

        // Change the status of the original post to "Rejected".
        $rc = private_revertrequirementschange(
            $nid, "Reverted change due to a dispute", $postid, $patch);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);
    } else if( $decision === 'defendant') {
        // Nothing to do
    } else {
        return private_dberr(4,"Unknown decision type: $decision");
    }

    // Mark the dispute as decided
    $qu = sql_exec("update disputes set decided=".time().
        ",decision='$decision',status='decided' where id=$did");
    if( $qu === false) return private_dberr(1);

    // Inform interested parties
    $rc = al_triggerevent("watch:p$nid-news\\member:".scrub($username),
        "dispute.php?id=d$did", "dispute$decision", array(
            "subject" => $row["subject"],
            "projectname" => $projectname));
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    if( $row["type"] == 'badchange') {
        // If there are disputes in the waiting state then set one free.
        $qu = sql_exec("select id from disputes where projectid=$nid ".
            "and status='waiting' order by concluded,id limit 1");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) > 0) {
            $row = sql_fetch_array($qu,0);
            $rc = private_concludedispute( $nid, intval($row["id"]), "");
            if( $rc[0]) return private_dberr($rc[0],$rc[1]);
        }
    }

    return private_commit();
}

// Accepts the given submission.  $username is the username of the user
// performing the action.  The following tables are locked in this order:
// <projects>
// <submissions> (just the specified submission record)
// <submissions> (all smaller submission ids)
// <submissions> (the currently accepted later submission, if any)
// <duties>
function ff_acceptsubmission($username, $submissionid) {
    $submissionid = intval($submissionid);

    // Get the project ID
    $qu = sql_exec("select * from submissions where id=$submissionid");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0)
        return array(2,"No such submission: $submissionid");
    $row = sql_fetch_array($qu,0);
    $nid = intval($row["projectid"]);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Get some information about the project
    $qu = sql_exec("select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0) return private_dberr(2,"No such project: p$nid");
    $row = sql_fetch_array($qu,0);
    $projectlead = $row["lead"];

    // Make sure that the person doing the action is the project lead
    if( "$username"==='' || $username !== $projectlead)
        return private_dberr(5,"Only the project lead can accept submissions");

    $rc = private_acceptsubmission( $nid, $submissionid);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    $rc = private_destroyduty( $nid, "code submission", $submissionid);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    return private_commit();
}

function private_acceptsubmission( $nid, $submissionid)
{
    // We need a lock on the project record, to avoid possible race conditions
    $qu = sql_exec("select * from projects where id=$nid for update");
    if ($qu===false) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $status = $row["status"];
    $payout_time = intval($row["payout_time"]);
    $projectstatus = $row["status"];
    $bounty_portions = $row["bounty_portions"];

    // If the project is already in a completed state, then this
    // transaction must fail.
    if( $projectstatus === 'complete')
        return array(5,"The project is already complete");

    // Get the submission information
    $qu = sql_exec("select * from submissions ".
        "where id=$submissionid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $remaining = intval($row["payout_time_remaining"]);
    $status = $row["status"];
    $held_amounts = $row["held_amounts"];

    if( $status === 'accept') return array(0,"Already accepted");

    // Make sure there are no earlier submissions in pending or accepted state
    $qu = sql_exec("select * from submissions ".
        "where projectid = $nid and id < $submissionid for update");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        if( $row["status"] === 'pending')
            return array(5,"An earlier submission is still pending");
        if( $row["status"] === 'accept')
            return array(5,"An earlier submission was accepted");
    }

    if( $projectstatus === 'accept') {
        // Some later submission is currently accepted.  We need
        // to revert it back to pending.

        $qu = sql_exec("select * from submissions ".
            "where projectid=$nid and status='accept' for update");
        if( $qu === false) return private_dberr();
        if( sql_numrows($qu) != 1) {
            return array(1,"Missing or too many accepted submissions");
        }
        $row = sql_fetch_array($qu,0);
        $aremaining = intval($row["payout_time_remaining"]);
        $acceptedid = intval($row["id"]);

        if( $payout_time) {
            // Freeze the clock on the other submission just in case
            // it becomes accepted again later.
            $aremaining = $payout_time - time();
            if( $aremaining < 1) $aremaining = 1;
        }

        $qu = sql_exec("update submissions ".
            "set status='pending',payout_time_remaining=$aremaining ".
            "where id=$acceptedid");
        if( $qu === false) return private_dberr();
    }

    // If there are no held amounts then set the held amounts
    // to the current bounty portions.  This makes things a bit
    // more fair for people whose submission was rejected
    // (and the hold expired) or rejected with prejudice, then
    // re-accepted later.
    if( !ereg("[1-9]",$held_amounts)) $held_amounts = $bounty_portions;

    $qu = sql_exec("update submissions set ".
        "status='accept',held_amounts='$held_amounts' ".
        "where id=$submissionid");
    if( $qu === false) return private_dberr();

    if( $remaining) {
        $payout_time = time() + $remaining;
    } else {
        $d = explode(" ",date("H i s n j Y"));
        $payout_time = mktime($d[0]+conf("cooloffperiod"),
            $d[1],$d[2],$d[3],$d[4],$d[5]);
    }

    $qu = sql_exec("update projects set status='accept',".
        "payout_time=$payout_time  where id=$nid");
    if( $qu === false) return private_dberr();

    list($rc,$err) = private_submissionclock( $nid);
    if( $rc) return array($rc,$err);

    return array(0,"Success");
}

//rejects the given submission.  $username is the username of the user performing the action.
//If $withprejudice is 1, then the submission is rejected with prejudice.  This means that in the opinion of the project lead, the submission was not serious, and is not even close to qualifying for acceptance.
function ff_rejectsubmission($username,$submissionid,$reason,$withprejudice=0)
{
    $submissionid = intval($submissionid);

    // Get the project ID
    $qu = sql_exec("select * from submissions where id=$submissionid");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0)
        return array(2,"No such submission: $submissionid");
    $row = sql_fetch_array($qu,0);
    $nid = intval($row["projectid"]);
    $submitter = $row["username"];

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Get some information about the project
    $qu = sql_exec("select * from projects where id=$nid for update");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0) return private_dberr(2,"No such project: $nid");
    $row = sql_fetch_array($qu,0);
    $projectname = $row["name"];

    // Make sure that the person doing the action is the project lead
    if( "$username"==="" || $username != $row["lead"])
        return private_dberr(5,"Only the project lead can do that");

    $rc = private_rejectsubmission($nid,$submissionid,$reason,$withprejudice);
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    $url = projurl("p$nid", $withprejudice ? '' : "tab=submissions#tabs");

    $rc = al_triggerevent("member:$submitter",
        $url, $withprejudice?"prejudice":"rejected",
        array("projectname"=>$projectname, "reason"=>$reason));
    if( $rc[0]) return private_dberr($rc[0],$rc[1]);

    $rc = private_commit();

    if( $withprejudice) {
        // Try releasing the holds right away.  It's okay if this
        // fails because it'll happen in a cron job very soon.
        ff_releaseholds();
    }

    return $rc;
}

// Must be called in a transaction.  This function locks the following
// tables in this order:
// <projects>
// <submissions>
function private_rejectsubmission($nid,$submissionid,$reason,$withprejudice=0)
{
    // We need a lock on the project record, to avoid possible race conditions
    $qu = sql_exec("select * from projects where id=$nid for update");
    if ($qu===false) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $status = $row["status"];
    $payout_time = intval($row["payout_time"]);

    // Get the submission information
    $qu = sql_exec("select * from submissions ".
        "where id=$submissionid for update");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $subm_status = $row["status"];
    $remaining = intval($row["payout_time_remaining"]);

    // Set the submission status to indicate that it has been rejected
    $hold_endtime = time() + ($withprejudice ? 0 : 3600 * conf('holdperiod'));
    $qu = sql_exec("update submissions set ".
        "hold_endtime=$hold_endtime,".
        "status='".($withprejudice?"prejudice":"reject").
        "',reject_reason='".sql_escape($reason)."' ".
        "where id=$submissionid");
    if ($qu===false) return private_dberr();

    if( ($status !== 'complete' && $status !== 'accept') ||
        $subm_status === $status)
    {
        //find out if there are other submitted files pending
        $qu = sql_exec("select status from submissions ".
            "where projectid = $nid and status='pending' for update");
        $status = (sql_numrows($qu) > 0) ? 'submitted' : 'pending';

        $qu = sql_exec("update projects set status='$status' where id=$nid");
        if( $qu === false) return private_dberr();
    }

    if( $subm_status === 'accept' && $payout_time)
    {
        // We need to record how much time was remaining on the payout clock.
        // This ensures that if the project lead is undecided
        // (accept; reject; accept; etc.) then the submitter won't be
        // significantly penalized.  We might consider adding a margin here
        // for good luck.

        $remaining = $payout_time - time();
        if( $remaining < 1) $remaining = 1;

        $qu = sql_exec("update submissions ".
            "set payout_time_remaining=$remaining where id=$submissionid");
        if( $qu === false) return private_dberr();

        $qu = sql_exec("update projects set payout_time=null where id=$nid");
        if( $qu === false) return private_dberr();
    }

    // Schedule a time for the hold to be released.
    $qu = sql_exec("insert into release_holds_queue ".
        "(time,submission,projectid) values ".
        "($hold_endtime,$submissionid,$nid)");
    if( $qu === false) return private_dberr();

    return private_destroyduty( $nid, "code submission", intval($submissionid));
}

// Must be called in a transaction.
// Locks the following tables:
//
// <projects>
// <posts>
function private_revertrequirementschange($nid,$subject,$postid,$patch)
{
    $postid = intval($postid);

    // Get a lock on the project record
    $qu = sql_exec("select * from projects where id=$nid for update");
    if ($qu===false) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $reqmts = $row["reqmts"];
    $projname = $row["name"];

    // Apply the patch
    include_once("diff.php");
    list($rc,$reqmts) = patchText( $reqmts, $patch, 1);
    if( $rc) return array($rc,$reqmts);

    $qu = sql_exec("update project_reqmts_history ".
        "set action='reject' where id=$nid and postid=$postid");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("update posts set status='rejected' where id=$postid");
    if( $qu === false) return private_dberr();

    $qu = sql_exec("update projects ".
        "set reqmts='".sql_escape($reqmts)."',reqmts_seq=reqmts_seq+1 ".
        "where id=$nid");
    if( $qu === false) return private_dberr();

    list($rc,$err) = private_submissionclock( $nid);
    if( $rc) return array($rc,$err);

    return array(0,"Success");
}

// This must be called in a transaction, while holding a lock on the
// corresponding record in the projects table.
// It locks the following tables:
//   <duties>
function private_setdutydeadline( $nid, $projectstatus)
{
    $cond = "";

    // Check whether there are any change disputes deliberating.
    // If so, then we shouldn't assign a deadline to a change proposal.
    $qu = sql_exec("select id from disputes where projectid=$nid and ".
        "type='badchange' and status='deliberating'");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) > 0) {
        $cond .= " and type != 'change proposal'";
    }

    // We should also never assign a deadline to a code submission
    // if the corresponding project is in the accepted state.
    if( $projectstatus === 'accept') {
        $cond .= " and type != 'code submission'";
    }

    // Determine which duty should have a deadline, if any
    $qu = sql_exec("select * from duties where project=$nid$cond ".
        "order by project,seq limit 1");
    if( $qu === false) return private_dberr();
    if( sql_numrows( $qu) == 0) {
        $seq = -1;
        $deadline = 0;
    } else {
        $row = sql_fetch_array( $qu, 0);
        $seq = $row["seq"];
        $deadline = time() + intval($row["time_allotment"]);
    }

    // Set the deadline on the chosen duty, and clear any other deadlines.
    // If the chosen duty already had a deadline, then leave it alone.
    // Note that this will only update records that are already correct.
    $qu = sql_exec("update duties ".
        "set deadline=(case when seq=$seq then $deadline else null end) ".
        "where project=$nid and ".
            "((seq = $seq and deadline is null) or ".
             "(seq != $seq and deadline is not null))");
    if( $qu === false) return private_dberr();

    return array(0,"Success");
}

// This must be called in a transaction.
// It locks the following tables:
//   <projects>
//   <duties>
function private_createduty( $nid, $type, $info, $time_allotment)
{
    $seq = sql_nextval("duties_seq");
    if ($seq===false) return private_dberr();

    // Get a lock on the project record.  This record's lock is used to
    // control write access to the entire corresponding section of the
    // duties table.
    $qu = sql_exec("select status from projects where id=$nid for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0) return array(2,"No such project: p$nid");
    $row = sql_fetch_array( $qu, 0);
    $status = $row["status"];

    $qu = sql_exec("insert into duties ".
        "(seq,project,type,info,created,time_allotment) values ".
        "($seq,$nid,'$type','$info',".time().",$time_allotment)");
    if( $qu === false) return private_dberr();

    $rc = private_setdutydeadline( $nid, $status);
    if( $rc[0]) return $rc;

    $qu = sql_exec("select deadline from duties ".
        "where project=$nid and seq=$seq");
    if( $qu === false || sql_numrows($qu) == 0) return private_dberr();
    $row = sql_fetch_array($qu,0);

    return array(0,intval($row["deadline"]));
}

// This must be called in a transaction.
// It locks the following tables:
//   <projects>
//   <duties>
function private_destroyduty( $nid, $type, $info)
{
    // Get a lock on the project record.  This record's lock is used to
    // control write access to the entire corresponding section of the
    // duties table.
    $qu = sql_exec("select status from projects where id=$nid for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0) return array(2,"No such project: p$nid");
    $row = sql_fetch_array( $qu, 0);
    $status = $row["status"];

    // Delete the record
    $qu = sql_exec("delete from duties where ".
        "project=$nid and type='$type' and info='$info'");
    if( $qu === false) return private_dberr();

    return private_setdutydeadline( $nid, $status);
}

function ff_enforcedutydeadlines()
{
    $now = time();

    $qu2 = sql_exec("select * from duties ".
        "where deadline < $now order by deadline limit 1000");
    if( $qu2 === false) return private_dberr();

    $count = 0;
    for( $i=0; $i < sql_numrows( $qu2); $i++) {
        $row = sql_fetch_array($qu2,$i);
        $nid = intval($row["project"]);
        $seq = intval($row["seq"]);
        $deadline = intval($row["deadline"]);

        $qu = sql_exec("begin");
        if( $qu === false) return private_dberr(1);

        // Lock the projects table record
        $qu = sql_exec("select lead,name from projects ".
            "where id=$nid for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) == 0) return private_dberr(1);
        $row = sql_fetch_array($qu,0);
        $lead = $row["lead"];
        if( !$lead) {
            // No project lead -- there shouldn't be a deadline.
            error_log("Processing deadline with no project lead");
            $qu = sql_exec("rollback");
            if( $qu === false) return private_dberr(1);
            continue;
        }
        $projectname = $row["name"];

        $qu = sql_exec("select * from duties where project=$nid and ".
            "seq=$seq and deadline=$deadline for update");
        if( $qu === false) return private_dberr(1);
        if( sql_numrows($qu) == 0) {
            // The deadline has been met in the nick of time
            $qu = sql_exec("rollback");
            if( $qu === false) return private_dberr(1);
            continue;
        }

        // Now oust the project lead
        $rc = private_removelead( $nid);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);

        list($rc,$memberinfo) = ff_getmemberinfo($lead);
        if( $rc) return private_dberr($rc,$memberinfo);

        // Notify everybody that the lead was ousted
        list($rc,$err) = al_triggerevent( "watch:p$nid-news\\member:$lead",
            projurl("p$nid"), "leadousted", array(
            "exlead" => $lead, "projectname" => $projectname));

        // Notify the ex-lead that he missed the deadline
        list($rc,$err) = al_triggerevent( "member:$lead",
            projurl("p$nid"), "misseddeadline", array(
            "name" => $memberinfo["name"],
            "projectname" => $projectname,
            "deadline" => date("D F j, H:i:s T", $deadline)));

        $rc = private_commit();
        if( $rc[0]) return $rc;

        $count ++;
    }

    return array(0,"Successfully removed $count project leads.");
}

/*This function will return an array of duties, each of which is an 
associative array containing the following fields:
- "type" => Either "dispute", "change-proposal", "new-subproject", or whatever else.
- "id" => The ID of the pertinent object, according to the duty type. 
(Eg, if the type is "dispute", then this field will hold the dispute ID.)
- "created" => A Unix timestamp of when this duty was created.
- "deadline" => A Unix timestamp of when the duty must be completed. 
If there is no deadline, then this field will be blank.
*note* the deadline will be 10 days after the duty is created by default
- "projectid" => The ID of the project that the duty relates to.
*/
function ff_getduties( $username)
{
    $duties = array();

    // Get the list of project lead duties
    $qu = sql_exec("select * from duties where project in ".
        "(select id from projects where lead='".
        sql_escape($username)."') order by deadline,seq");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);

        $type = $row["type"];

        if( $type == 'dispute-defendant') $info = "d$row[info]";
        else if( $type == 'code submission') $info = "$row[info]";
        else if( $type == 'change proposal') $info = "$row[info]";
        else $info = "p$row[info]";

        $duties[] = array(
            'type'=> $type,
            'guid' => "duty-$row[seq]",
            'id'=> $info,
            'created' => intval($row["created"]),
            'deadline' => intval($row["deadline"]),
            'projectid' => "p$row[project]");
    }

    //find disputes that must be addressed where the user is the plaintiff
    $disputequ = sql_exec("select id,projectid,length(body) as len,".
        "substring(body from 1 for 11) as created ".
        "from disputes where plaintiff = '".sql_escape($username).
        "' and status = 'plaintiff' order by id");
    if( $disputequ === false) return private_dberr();
    for( $i=0; $i < sql_numrows( $disputequ); $i++) {
        $disputerow = sql_fetch_array($disputequ,$i);
        $duties[] = array(
            'type'=> "dispute-plaintiff",
            'guid' => "dispute-$disputerow[id]-$disputerow[len]",
            'id'=> "d$disputerow[id]",
            'created' => intval($disputerow["created"]),
            'deadline' => 0,
            'projectid' => "p$disputerow[projectid]");
    }

    return array(0,$duties);
}

function al_sendnewsupdate( $username, $subject, $body, $test=true)
{
    if( $test) {
        $recipients = array($username);
    } else {
        $qu = sql_exec( "select username from watches where eventid='news'");
        if( $qu === false) return private_dberr();
        $recipients = array();
        for( $i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array($qu,$i);
            $recipients[] = $row["username"];
        }
    }
    if( sizeof($recipients) == 0) return array(0,"Sent 0 emails");

    $unset = array();
    foreach( $recipients as $recipient) {
        list($rc,$err) = private_notify(
            $recipient, '', $subject, $body, "default", 
            "\"FOSS Factory\" <support@fossfactory.org>");
        if( $rc) {
            $unsent[] = $recipient;
            $result = array($rc,$err);
        }
    }
    if( sizeof($unsent) == sizeof($recipients)) return $result;
    if( sizeof($unsent) > 0) return array(8,$unsent);

    return array(0,"Sent ".sizeof($recipients)." emails");
}

/*Registers the given user to be notified of the particular specified event.  $method is a complete description of the alert type and contact information.  If 'default' is specified, then the member's default alert method is used instead.  Here are some examples of valid alert methods:

email/nobody@hotmail.com
MSN/nobody@hotmail.com
ICQ/123456789
Jabber
textmsg/5195551212

(Note that for RSS alerts, this function is unnecessary.  Rather, people subscribe via their RSS browser.)
*/
// This function does not lock any existing tables.
//
function al_createwatch( $eventid, $username, $method='default') {
    // If the watch already exists, just return the existing ID.
    $qu = sql_exec("select watchid from watches ".
        "where eventid='".sql_escape($eventid).
        "' and username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) > 0) {
        $row = sql_fetch_array($qu,0);
        return array(0,intval($row["watchid"]));
    }

    // There is a slight chance of race condition here:
    // If another process creates the watch right at this moment, then
    // the following query will fail.

    $id = sql_nextval('watches_id_seq');
    if ($id===false) return private_dberr();
    $qu = sql_exec("insert into watches (watchid,eventid,username,method)".
        "values (".intval($id).", '".sql_escape($eventid)."','".
        sql_escape($username)."','".sql_escape($method)."')");  
    if ($qu===false) return private_dberr();

    return array(0,$id);
}

function al_countwatches( $eventid) {
    $qu = sql_exec("select count(*) from watches ".
        "where eventid='".scrub($eventid)."'");
    if( $qu === false) return private_dberr();
    $row = sql_fetch_array($qu,0);
    return array(0,intval($row["count"]));
}

/*
Returns a list of all of the registered watches for the given user.  If eventid is not false, then the list is restricted to watches on that particular event.  Note that a user may be registered for more that one watch on a particular event.  Each watch is an associative array containing the following fields:
- "watchid" => The unique watch ID
- "eventid" => The event ID
- "username" => The member's username
- "method" => The method description string.  (See al_createwatch())*/
function al_getwatches( $username, $eventid=false) {
    if ($eventid) {
        $qu = sql_exec("select * from watches where ".
            "(eventid||'/'||username) = '".
            scrub($eventid)."/".scrub($username)."'");
    } else {
        $qu = sql_exec("select * from watches where ".
            "username = '".sql_escape($username)."'");
    }
    if ($qu===false) return private_dberr();
    $watches = array();
    for ($i=0;$i<sql_numrows($qu);$i++) {
        $row = sql_fetch_array($qu,$i);
        $watches[] = array('watchid'=>$row['watchid'],
            'eventid'=>$row['eventid'],
            'username'=>$row['username'],
            'method'=>$row['method']);
    }
    return array(0,$watches);
}

	

//Destroys the watch with the given ID.
function al_destroywatch( $watchid) { 
    $qu = sql_exec("delete from watches where watchid =".intval($watchid));
    if ($qu===false) return private_dberr();
    return array(0,"successfully deleted watch");
}

// Destroys the watch with the given username and eventid
function al_destroywatch2( $eventid, $username) {
    $qu = sql_exec("delete from watches where eventid='".
        sql_escape($eventid)."' and username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr();
    return array(0,"Success");
}

// This function creates a new event.
// This function should normally be called at the end of the
// transaction in which the corresponding action was performed.  This
// will ensure that the proper notifications will be sent out.
// The notifications are not sent out immediately, but rather are queued
// to be sent, often by a separate process.
//
// This function does not lock any existing tables.
//
function al_triggerevent($eventid,$url,$textid,$macros=false,$priority=1)
{
    if( $macros === false) $macros = array();
    list($rc,$subject) = ff_gettext("$textid-subject",$macros);
    if( $rc) return array($rc,$subject);
    list($rc,$body) = ff_gettext("$textid-body",$macros);
    if( $rc) return array($rc,$body);

    $seq = sql_nextval("recent_events_seq");
    if ($seq===false) return private_dberr();

    $qu =sql_exec("insert into recent_events ".
        "(seq,eventid,subject,url,body,time,status)". 
        "values ($seq,'".sql_escape($eventid)."','".
        sql_escape($subject)."','".
        sql_escape($url)."','".sql_escape($body)."',".time().",'new')");
    if ($qu===false) return private_dberr();

    return array(0,"Success");
}

// This function is for sending out a notification immediately, without
// bothering to trigger an event.  This is useful for things like
// forgotten-password reminders.  It should never be called during a
// database transaction.
function al_notifynow($username,$url,$textid,$macros=false,$method="default")
{
    if( $macros === false) $macros = array();
    list($rc,$subject) = ff_gettext("$textid-subject",$macros);
    if( $rc) return array($rc,$subject);
    list($rc,$body) = ff_gettext("$textid-body",$macros);
    if( $rc) return array($rc,$body);

    return private_notify( $username, $url, $subject, $body, $method);
}

// This function may create transactions that lock tables in the following
// order:
//
//  <recent_events>
function al_queuenotifications()
{
    $qu = sql_exec("select seq from recent_events ".
        "where status='new' order by seq limit 1000");
    if( $qu === false) return private_dberr();

    for( $i=0; $i <sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $seq = intval($row["seq"]);

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        $qu2 = sql_exec("select * from recent_events ".
            "where seq=$seq for update");
        if( $qu2 === false) return private_dberr(1);
        if( sql_numrows($qu2) == 0) {
            // This event has disappeared from the queue.  No need
            // to do anything about it.
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }
        $row2 = sql_fetch_array($qu2,0);
        if( $row2["status"] !== 'new') {
            // This event has already been taken care of by another process
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }

        $eventid = $row2["eventid"];
        $url = $row2["url"];
        $subject = $row2["subject"];
        $body = $row2["body"];

        if( ereg("^(.*)\\\\(.*)$", $eventid, $regs)) {
            $recipient_groups = explode(",",$regs[1]);
            $exclusion_groups = explode(",",$regs[2]);
        } else {
            $recipient_groups = explode(",",$eventid);
            $exclusion_groups = array();
        }
        $recipients = array();
        foreach( $recipient_groups as $group) {
            list($rc,$add_recipients) = private_expand_group( $group);
            if( $rc) return array($rc,$add_recipients);
            $recipients = array_merge( $recipients, $add_recipients);
        }
        foreach( $exclusion_groups as $group) {
            list($rc,$del_recipients) = private_expand_group( $group);
            if( $rc) return array($rc,$del_recipients);
            $recipients = array_diff_key( $recipients, $del_recipients);
        }

        $j=0;
        foreach( $recipients as $username => $method) {
            $rc = private_queuenotification( "$seq/$j",
                $username, $url, $subject, $body, $method);
            if( $rc[0]) return private_dberr($rc[0],$rc[1]);
            $j++;
        }

        $qu2 = sql_exec("update recent_events ".
            "set status='sent' where seq=$seq");
        if( $qu2 === false) return private_dberr(1);

        $rc = private_commit();
        if( $rc[0]) return $rc;
    }

    return array(0,"Queued notifications for ".sql_numrows($qu)." events");
}

function private_expand_group( $group)
{
    $usernames = array();
    if( substr( $group, 0, 5) === 'lead:') {
        list($rc,$projectinfo) = ff_getprojectinfo(substr($group,5));
        if( $rc) return private_dberr($rc,$projectinfo);
        if( "$projectinfo[lead]" !== "")
            $usernames[$projectinfo["lead"]] = 'default';
    } else if( substr( $group, 0, 6) === 'watch:') {
        $watchid = substr($group,6);
        $qu =sql_exec("select * from watches ".
            "where eventid='".sql_escape($watchid)."'");
        if ($qu===false) return private_dberr();
        for($i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array($qu,$i);
            $usernames[$row["username"]] = $row["method"];
        }
    } else if( substr( $group, 0, 7) === 'member:') {
        $usernames[scrub(substr($group,7))] = 'default';
    } else if( substr( $group, 0, 6) === 'admin:') {
        $qu = sql_exec("select username from members where auth='admin'");
        if ($qu===false) return private_dberr();
        for($i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array($qu,$i);
            $usernames[$row["username"]] = 'default';
        }
    } else if( substr( $group, 0, 8) === 'arbiter:') {
        $qu = sql_exec("select username from members ".
            "where auth='admin' or auth='arbiter'");
        if ($qu===false) return private_dberr();
        for($i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array($qu,$i);
            $usernames[$row["username"]] = 'default';
        }
    } else {
        return array(4,"Unknown group type: $group");
    }
    return array(0,$usernames);
}

// Expects to be called from within a transaction.
// Doesn't lock any existing tables or records.
function private_queuenotification($id, $username,
    $url, $subject, $body, $method="default")
{
    $qu = sql_exec("insert into notification_queue ".
        "(id,username,url,subject,body,method) values ".
        "('".sql_escape($id)."','".sql_escape($username).
        "','".sql_escape($url)."','".sql_escape($subject)."','".
        sql_escape($body)."','".sql_escape($method)."')");
    if( $qu === false) return private_dberr(1);
    return array(0,"Success");
}

function al_sendnotifications()
{
    $qu = sql_exec("select id from notification_queue ".
        "order by id limit 1000");
    if( $qu === false) return private_dberr();

    for( $i=0; $i <sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $id = $row["id"];

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        $qu2 = sql_exec("select * from notification_queue ".
            "where id='".sql_escape($id)."' for update");
        if( $qu2 === false) return private_dberr(1);
        if( sql_numrows($qu2) == 0) {
            // This notification has disappeared from the queue.  No need
            // to do anything about it.
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }

        $row2 = sql_fetch_array($qu2,0);
        $username = $row2["username"];
        $url = $row2["url"];
        $subject = $row2["subject"];
        $body = $row2["body"];
        $method = $row2["method"];

        $rc = private_notify( $username, $url, $subject, $body, $method);
        if($rc[0]) return private_dberr($rc[0],$rc[1]);

        $qu2 = sql_exec("delete from notification_queue ".
            "where id='".sql_escape($id)."'");
        if( $qu2 === false) return private_dberr(1);

        $rc = private_commit();
        if( $rc[0]) return $rc;
    }

    return array(0,"Sent ".sql_numrows($qu)." notifications.");
}

function private_notify( $username, $url, $subject, $body, $method,
    $from = "\"FOSS Factory\" <notices@fossfactory.org>")
{
    list($rc,$memberinfo) = ff_getmemberinfo($username); 
    if( $rc) return array($rc,$memberinfo);
    $name = ereg_replace("[^ #$%&'()*+,-./0-9:;=@a-zA-Z\241-\377]",
        "",$memberinfo["name"]);
    $body = str_replace("%MEMBERNAME%",$name,$body);

    // If the debuguser config variable is set, then all notifications
    // should go to that user, and the message body should declare who
    // it was originally for.
    list($rc,$debuguser) = ff_config("debuguser", "");
    if( $rc) return array($rc,$debuguser);
    if( $debuguser) {
        $body = "debuguser email for $username\n$body";
        $username = $debuguser;
        if( $debuguser=='log') $method = 'log';
        else {
            list($rc,$memberinfo) = ff_getmemberinfo($username); 
            if( $rc) return array($rc,$memberinfo);
        }
    }

    if (substr($method,0,5)=='email' || $method=='default') {
        //send email
        $emailurl = "$url" !== '' ? "\n    $GLOBALS[SITE_URL]$url" : "";
        $body = str_replace("\n","\n\n","$body$emailurl");
        $cmd = "echo -n ".escapeshellarg($body)." | fmt";
        $body = `$cmd`;
        $rc = mail( "\"$name\" <$memberinfo[email]>",
            $subject, $body, "From: $from"); 
        if($rc === false) return array(1,"Can't send email");
    } elseif($method === 'log') {
        error_log(date("Y-m-d H:i:s ").
            "$subject\nFrom: $from\nURL: $url\n\n$body\n",3,
            "$GLOBALS[DATADIR]/test-emails");
    } elseif(substr($method,0,3)=='MSN') {
        //send msn message
        return array(1,"MSN notification method not yet defined");
    } elseif(substr($method,0,3)=='ICQ') {
        //send icq message
        return array(1,"ICQ notification method not yet defined");
    } elseif(substr($method,0,6)=='jabber') {
        //send jabber message
        return array(1,"Jabber notification method not yet defined");
    } elseif(substr($method,0,7)=='textmsg') {
        //send text message                          
        return array(1,"textmsg notification method not yet defined");
    }
    return array(0,"Success");
}

/*This function returns an array of event description records with the given event ID.  It will only include events that are fairly recent.  The precise time period included is not defined by this function.  Each record includes the following attributes:
- "eventid" - The event ID
- "time" - the Unix timestamp when the event was triggered
- "subject" - The subject of the event
- "url" - The URL of the event
- "body" - The body of the event*/
function al_getrecentevents($eventid)
{
    $qu = sql_exec("select * from recent_events where eventid='".sql_escape($eventid)."' or eventid like '".sql_escape($eventid)."/%'");
    if ($qu===false) return private_dberr();
    for($i=0;$i<sql_numrows($qu);$i++) {
        $row = sql_fetch_array($qu,$i);
        $events[] = array("eventid"=>$row['eventid'],
              "time"=>$row['time'],
              "subject"=>$row['subject'],
              "url"=>$row['url'],
              "body"=>$row['body']);
    }
    return array(0,$events);
}

function ff_deleteprojects()
{
    $now = time();
    $qu = sql_exec("select id from projects ".
        "where delete_time < $now limit 100");
    if( $qu === false) return private_dberr();

    $count = 0;
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $nid = intval($row["id"]);

        // Start by refunding all the sponsorships
        $qu2 = sql_exec("select * from member_donations ".
            "where project=$nid and amount~'[1-9]'");
        if( $qu2 === false) return private_dberr(1);
        for( $j=0; $j < sql_numrows( $qu2); $j++) {
            $row2 = sql_fetch_array( $qu2, $j);
            list($rc,$err) = private_setsponsorship($nid,$row2['member'],'');
            if( $rc) return array($rc,$err);
        }

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        // We need to lock all of the project's ancestors
        $rc = private_lock_from_root( $nid);
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);

        // Make sure there are no remaining sponsorships
        $qu2 = sql_exec("select * from member_donations ".
            "where project=$nid and amount~'[1-9]' limit 1");
        if( $qu2 === false) return private_dberr(1);
        if( sql_numrows( $qu2) > 0)
            return private_dberr(7,"There is still a sponsorship");

        // Make sure the deletion hasn't been cancelled at the last minute
        $qu2 = sql_exec("select delete_time from projects where id=$nid");
        if( $qu2 === false) return private_dberr(1);
        if( sql_numrows($qu2) != 1) {
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }
        $row2 = sql_fetch_array( $qu2, 0);
        $delete_time = intval($row2["delete_time"]);
        if( $delete_time == 0 || $delete_time > $now) {
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }

        // Delete a bunch of related stuff
        $qu2 = sql_exec("delete from watches where eventid='p$nid-news'");
        if( $qu2 === false) return private_dberr(1);
        $qu2 = sql_exec("delete from duties where project=$nid");
        if( $qu2 === false) return private_dberr(1);
        $qu2 = sql_exec("delete from project_user_data where project=$nid");
        if( $qu2 === false) return private_dberr(1);
        $qu2 = sql_exec("delete from subscriptions where projectid=$nid");
        if( $qu2 === false) return private_dberr(1);
        $qu2 = sql_exec("delete from member_donations ".
            "where project=$nid and amount!~'[1-9]'");
        if( $qu2 === false) return private_dberr(1);

        // Now delete the project
        $qu2 = sql_exec("delete from projects where id=$nid");
        if( $qu2 === false) return private_dberr(1);

        $rc = private_commit();
        if( $rc[0]) return $rc;

        $count++;
    }
    return array(0,"Successfully deleted $count projects.");
}

// Send payment for all projects that have been in the 'accept' stage for
// more than the cooloff period.
function ff_checkpaymentarrival() {
    $qu=sql_exec("select payout_time,name,id from projects ".
        "where status='accept' and payout_time > 0 ".
        "and payout_time <= ".time()." limit 100");
    if ($qu===false) return private_dberr();

    $count = 0;
    for($i=0;$i<sql_numrows($qu);$i++) {
        $row=sql_fetch_array($qu,$i);

        $qu2 = sql_exec("begin");
        if( $qu2 === false) return private_dberr();

        // We need to lock all of the project's ancestors in order
        // to be able to extract the bounty safely.
        $rc = private_lock_from_root( intval($row["id"]));
        if( $rc[0]) return private_dberr($rc[0],$rc[1]);

        // Make sure the submission hasn't been un-accepted while we
        // were dealing with other projects.
        $qu4 = sql_exec("select id,direct_bounty from projects ".
            "where id=$row[id] and status='accept' and ".
            "payout_time=".intval($row["payout_time"])." for update");
        if( $qu4 === false) return private_dberr(1);
        if( sql_numrows($qu4) != 1) {
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }
        $row4 = sql_fetch_array( $qu4, 0);
        $direct_bounty = $row4["direct_bounty"];

        // Get a transaction ID
        $xid = sql_nextval("transaction_seq");
        if( $xid === false) return private_dberr(1);
        $split = 0;
        $now = time();
        $desc = "Bounty payment: p$row[id]";

        $qu4=sql_exec("update projects set status='complete' ".
            "where id=$row[id]");
        if($qu4===false) return private_dberr(1);

        $qu2=sql_exec("select id,username,held_amounts,".
            "max_money(regexp_replace(held_amounts,'.*,',''),".
            "'$direct_bounty') from submissions ".
            "where projectid=$row[id] and status='accept' for update");
        if($qu2===false) return private_dberr(1);
        if( sql_numrows($qu2) != 1) {
            error_log("No unique accepted submission for project p$row[id]");
            $qu2 = sql_exec("rollback");
            if( $qu2 === false) return private_dberr(1);
            continue;
        }
        $row2=sql_fetch_array($qu2,0);
        
        // Make sure to take the entire bounty even if it's increased
        // since the time of submission.
        $held_amounts = explode(',',$row2["held_amounts"]);
        $held_amounts[sizeof($held_amounts)-1] = $row2["max_money"];

        $qu3 = sql_exec("update submissions set status='complete' ".
            "where id=$row2[id]");
        if($qu3===false) return private_dberr(1);

        // In case there are other pending submissions, delete the
        // corresponding duties.  Those submissions can no longer ever
        // be accepted.
        $qu3 = sql_exec("delete from duties where ".
            "project=$row[id] and type='code submission'");
        if( $qu3 === false) return private_dberr(1);

        $rc = private_clear_holds( intval($row["id"]),
            $held_amounts, sizeof($held_amounts));

        list($rc,$amt) = private_extractbounty( $xid, $split, $now, $desc,
            intval($row["id"]), $held_amounts, sizeof($held_amounts)-1);
        if( $rc) return private_dberr($rc,$amt);

        $qu4 = sql_exec("select *,".
            "mult_round_money('$amt',".
                conf("commission")."*0.01) as commission,".
            "mult_round_money('$amt',".
                conf("communitydeduction")."*0.01) as deduction ".
            "from members where ".
            "username='".sql_escape($row2["username"])."' for update");
        if( $qu4 === false) return private_dberr(1);
        if( sql_numrows($qu4) == 0)
            return private_dberr(1,"Member '$row2[username]' disappeared");
        $row4 = sql_fetch_array( $qu4, 0);

        $commission = $row4["commission"];
        $qu5 = sql_exec("insert into transaction_log ".
            "(xid,split,time,account,change,description) values ".
            "($xid,".(++$split).",$now,'fossfactory-income',".
            "'$commission','".sql_escape($desc)."')");
        if( $qu5 === false) return private_dberr(1);

        // If the claimant is not a monthly sponsor
        // then we need to deduct the community tax
        $deduction = "";
        if( !ereg("[1-9]",$row4["subscription_fee"])) {
            $deduction = $row4["deduction"];
            if( ereg("[1-9]",$deduction)) {
                if( intval($row4["prefcharity"]) == 0) {
                    // The community deduction is going into the community pot
                    $qu5 = sql_exec("insert into communitypot ".
                        "(xid,amount) values ($xid,'$deduction')");
                    if( $qu5 === false) return private_dberr(1);

                    $qu5 = sql_exec("insert into transaction_log ".
                        "(xid,split,time,account,change,description) values ".
                        "($xid,".(++$split).",$now,'communitypot',".
                        "'$deduction','".sql_escape($desc)."')");
                    if( $qu5 === false) return private_dberr(1);
                } else {
                    // The community deduction is going into the user's
                    // preferred charity
                    $qu5=sql_exec("update charities ".
                        "set current=add_money(current,'$deduction'),".
                        "total=add_money(total,'$deduction') ".
                        "where id=$row4[prefcharity]");
                    if( $qu5 === false) return private_dberr(1);

                    $qu5 = sql_exec("insert into transaction_log ".
                        "(xid,split,time,account,change,description) values ".
                        "($xid,".(++$split).",$now,".
                        "'charity:$row4[prefcharity]',".
                        "'$deduction','".sql_escape($desc)."')");
                    if( $qu5 === false) return private_dberr(1);
                }
            }
        }

        $change="subtract_money('$amt',add_money('$deduction','$commission'))";

        $qu4 = sql_exec("update members set ".
            "total_earnings=add_money(total_earnings,'$amt'),".
            "total_deductions=add_money(total_deductions,".
            "add_money('$deduction','$commission')),".
            "reserve=add_money(reserve,$change)".
            " where username='".sql_escape($row2["username"])."'");
        if( $qu4 === false) return private_dberr(1);

        $qu4 = sql_exec("insert into transaction_log ".
            "(xid,split,time,account,change,description) values ".
            "($xid,".(++$split).",$now,'reserve:$row2[username]',".
            "$change,'".sql_escape($desc)."')");
        if( $qu4 === false) return private_dberr(1);

        $qu4 = sql_exec("delete from project_user_data ".
            "where project=".intval($row["id"]));
        if( $qu4 === false) return private_dberr(1);

        unset($GLOBALS["PRIVATE_MEMBER_CACHE"][$row4["username"]]);
        unset($GLOBALS["PRIVATE_MEMBER_CACHE"][$row4["email"]]);

        $rc = private_commit();
        if( $rc[0]) return $rc;

        $count ++;
    }
    return array(0,"Successfully paid out $count bounties.");
}

// This function zeros out the first $levels levels of holds on the given
// project and all of its descendants.  It's used when a bounty gets paid out.
// Make sure you're holding a lock on the project when you call this.
function private_clear_holds( $nid, $held_amounts, $levels)
{
    $needs_fixing = 0;
    for( $j=0; $j < $levels; $j++) {
        if( ereg("[1-9]",$held_amounts[$j])) {
            $held_amounts[$j] = '';
            $needs_fixing = 1;
        }
    }
    if( !$needs_fixing) return array(0,"Success");

    // Clear the holds of all descendants.
    $qu = sql_exec("select id,held_amounts from projects ".
        "where parent=$nid for update");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $rc = private_clear_holds( intval($row["id"]),
            explode(",",$row["held_amounts"]), $levels);
        if( $rc[0]) return $rc;
    }

    $rc = private_updatechildbounties( $nid);
    if( $rc[0]) return $rc;

    // Clear the holds of all our own submissions.
    $qu = sql_exec("select id,held_amounts from submissions ".
        "where id=$nid for update");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows( $qu); $i++) {
        $row = sql_fetch_array( $qu, $i);
        $s_held_amounts = explode(",",$row["held_amounts"]);
        for( $j=0; $j < $levels; $j++) $s_held_amounts[$j] = '';
        $qu2 = sql_exec("update submissions set ".
            "held_amounts='".join(',',$s_held_amounts).
            "' where id=".intval($row["id"]));
        if( $qu2 === false) return private_dberr();
    }

    $qu = sql_exec("update projects set held_amounts='".
        join(',',$held_amounts)."' where id=$nid");
    if( $qu === false) return private_dberr();

    return array(0,"Success");
}

// Only call this in a transaction while holding a lock on all ancestors.
function private_extractbounty( $xid, &$split, $now, $desc, $nid,
    $extract_amounts, $depth, $permille=1000)
{
    $targetamt = $extract_amounts[$depth];

    $extracted = '';
    $qu = sql_exec("select parent,allotment,direct_bounty,".
        "subtract_money(direct_bounty,'$targetamt') as new_direct,".
        "subtract_money_list(held_amounts,'".
            join(',',$extract_amounts)."') as max_new_held_amounts ".
        "from projects where id=$nid for update");
    if( $qu === false) return private_dberr();
    if( sql_numrows($qu) == 0) return array(2,"No such project: $nid");
    $row = sql_fetch_array( $qu, 0);
    $parent = intval($row["parent"]);
    $ourportion = round(intval($row["allotment"])*$permille/1000);
    $direct_bounty = $row["direct_bounty"];
    $new_direct = $row["new_direct"];
    $max_new_held_amounts = $row["max_new_held_amounts"];

    // Sanity check
    if( strpos( $new_direct, "-") !== false)
        return array(1,"Attempt to extract more money than we have.");

    // Reduce the held amounts on our own submissions
    $qu = sql_exec("update submissions set held_amounts=".
        "min_money_list('$max_new_held_amounts',".
        "mult_round_money_list(held_amounts,(1000-$permille)*0.001)) ".
        "where projectid=$nid");
    if( $qu === false) return private_dberr();

    // Reduce the direct bounty and our own allotment percentage,
    // and calculate the new held_amounts.
    $qu = sql_exec( "update projects set ".
        "direct_bounty='$new_direct',".
        "bounty=subtract_money(bounty,'$targetamt'),".
        "bounty_portions=regexp_replace(bounty_portions,".
            "'[^,]*$','$new_direct'),".
        "held_amounts=(".
            "select max_money_list(held_amounts) from (".
                "select held_amounts from submissions ".
                    "where projectid=$nid and held_amounts~'[1-9]' union ".
                "select coalesce(sum_money_list(".
                    "regexp_replace(held_amounts,',[^,]*','')),'".
                    str_repeat(",",$depth)."') ".
                    "from projects where parent=$nid) foo),".
        "allotment=allotment-$ourportion where id=$nid");
    if( $qu === false) return private_dberr();

    if( $permille < 1000) {
        // Increase child allotments
        $qu = sql_exec("update projects set ".
            "allotment=allotment*1000/".(1000-$permille).
            " where parent=$nid");
        if( $qu === false) return private_dberr();
    }

    $GLOBALS["PRIVATE_PROJECT_INFO"] = array();

    if( $parent) {
        unset($extract_amounts[$depth]);
        list($rc,$extracted) = private_extractbounty($xid, $split, $now,
            $desc, $parent, $extract_amounts, $depth-1, $ourportion);
        if( $rc) return array($rc,$extracted);
    }

    $rc = private_updatechildbounties( $nid);
    if( $rc[0]) return $rc;

    // Now we need to attribute the extracted amount to the
    // various sponsors as equitably as possible.

    // We already know that the total contribution is the direct bounty.
    // The amount we need to attribute is $targetamt.  So each person's
    // theoretical contribution to this portion is:
    //
    //   amount * $targetamt / $direct_bounty
    //
    // Of course, this won't work out exactly right unless we round very
    // carefully.  So what we'll do is figure out how many pennies would
    // be missing if we were to round the amount down for everyone.  Then
    // we'll just round up the first that many, sorted by the descending
    // fractional portion, and by a random value to break ties.

    // Compute everybody's contributions
    $contribs = array();
    $roundup = array();
    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);
    foreach( $currencies as $name => $details) {
        if( !ereg("[1-9][0-9]*$name",$targetamt)) continue;

        // Compute the number of entries that we'll need to round up.
        $qu = sql_exec("select currency_value('$targetamt','$name')-".
            "sum(floor(currency_value(amount,'$name')*".
                "currency_value('$targetamt','$name')/".
                "currency_value('$direct_bounty','$name'))) as pennies ".
            "from member_donations where project=$nid");
        if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
        $row = sql_fetch_array( $qu, 0);
        $pennies = intval($row["pennies"]);

        // Get a list of the contributions, sorted descending by the
        // amount by which the contribution would be rounded down.
        $qu = sql_exec("select *,".
            "floor(currency_value(amount,'$name')*".
                "currency_value('$targetamt','$name')/".
                "currency_value('$direct_bounty','$name')) as contrib ".
            "from member_donations where project=$nid ".
            "order by (currency_value(amount,'$name')*".
                "currency_value('$targetamt','$name'))%".
                "currency_value('$direct_bounty','$name'),random() ".
            "for update");
        if( $qu === false) return private_dberr();
        // Sanity check
        if( $pennies > sql_numrows($qu))
            return array(1,"There's something wrong with our payment logic");
        for( $i=0; $i < sql_numrows($qu); $i++) {
            $row = sql_fetch_array( $qu, $i);
            if( $i >= $pennies && !ereg("[1-9]",$row["contrib"])) continue;
            if( $row["contrib"] !== '0')
                $contribs[$row["member"]] .= "+$row[contrib]$name";
            if( $i < $pennies) $roundup[$row["member"]] .= "+1$name";
        }
    }

    // Now extract the contributions from the individual sponsorships
    foreach( $contribs as $member => $contrib) {
        $change = "add_money('".substr($contrib,1)."','".
            substr($roundup[$member],1)."')";

        // Compute the new amount
        $qu = sql_exec("select subtract_money(amount,$change) as newamount ".
            "from member_donations where project=$nid and member='$member'");
        if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
        $row = sql_fetch_array($qu,0);
        $newamount = $row["newamount"];

        if( ereg("[1-9]",$newamount)) {
            // Compute the new credits due to this sponsorship
            $calculation = "0";
            list($rc,$currencies) = ff_currencies();
            if( $rc) return array($rc,$currencies);
            foreach( $currencies as $name => $details) {
                $ratio = $details["credit_ratio"];
                $calculation .= "+round(".
                    currency_value($newamount,$name)."*$ratio)";
            }

            $qu = sql_exec("update member_donations ".
                "set amount='$newamount',credits=factor*($calculation) ".
                "where project=$nid and member='$member'");
            if( $qu === false) return private_dberr();
        } else {
            // There's no sponsorship left.  Delete the record.
            $qu = sql_exec("delete from member_donations ".
                "where project=$nid and member='$member'");
            if( $qu === false) return private_dberr();
        }

        $qu = sql_exec("insert into transaction_log ".
            "(xid,split,time,account,change,description) values ".
            "($xid,".(++$split).",$now,'sponsorship:$member:p$nid',".
            "subtract_money('',$change),'".sql_escape($desc)."')");
        if( $qu === false) return private_dberr();
    }

    // Now compute the total amount that was extracted.
    $qu = sql_exec("select add_money('$extracted','$targetamt')");
    if( $qu === false || sql_numrows($qu) != 1) return private_dberr();
    $row = sql_fetch_array($qu,0);
    $extracted = $row["add_money"];

    return array(0,$extracted);
}

// This function should only ever be invoked with the express permission
// of company management.  Because it has no consideration whatsoever for
// concurrency, it should never be used in an automated script outside of
// system downtime.
//
// Valid reasons for its use:
//   - To eliminate profanity
//   - To prevent misleading impersonation
function admin_changeusername( $old, $new)
{
    if( !ereg("^[a-zA-Z0-9]+$", $new))
        return array(4,"Invalid username: $new");

    // The columns to update
    $columns = array("disputes:plaintiff","donations:owner",
        "member_donations:member","member_donations:assignee",
        "members:username","posts:owner","projects:creator",
        "projects:lead","sessions:username","submissions:username",
        "subscriptions:username","watches:username");

    // Sort the columns in order to make sure we lock the tables in
    // alphabetical order
    sort($columns);

    $qu = sql_exec("begin");
    if( $qu === false) return private_dberr();

    // Completely lock all of the relevant tables.
    // This is extremely rude, and will pretty much halt the entire system
    // for the duration of the transaction.
    foreach( $columns as $column) {
        $table = ereg_replace(":.*","",$column);
        $qu = sql_exec("lock table $table");
        if( $qu === false) return private_dberr(1);
    }

    // Make sure the original username exists
    $qu = sql_exec("select name from members where username='".
        sql_escape($old)."'");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) == 0) return private_dberr(2,"No such member: $old");

    // Make sure the new username doesn't exist
    $qu = sql_exec("select name from members where username='".
        sql_escape($new)."'");
    if( $qu === false) return private_dberr(1);
    if( sql_numrows($qu) > 0) return private_dberr(3,"Username exists: $new");

    // Change the username in each table
    foreach( $columns as $column) {
        $table = ereg_replace(":.*","",$column);
        $colname = ereg_replace(".*:","",$column);
        $qu = sql_exec("update $table set $colname='".sql_escape($new).
            "' where $colname='".sql_escape($old)."'");
        if( $qu === false) return private_dberr(1);
    }

    // We're done.
    return private_commit();
}

function admin_expedite_payout( $projectid)
{
    $nid = intval(substr($projectid,1));

    $now = time();

    $qu = sql_exec("update projects set payout_time=$now ".
        "where id=$nid and status='accept' and payout_time > $now");
    if( $qu === false) return private_dberr();

    // Try to pay it out right away.
    return ff_checkpaymentarrival();
}

function ff_assigndispute( $disputeid, $arbiter)
{
    $did = intval(substr($disputeid,1));

    list($rc,$memberinfo) = ff_getmemberinfo( $arbiter);
    if( $rc) return array($rc,$memberinfo);

    // Make sure that the person is actually a valid arbiter
    if( $memberinfo["auth"] !== 'admin' && $memberinfo["auth"] !== 'arbiter')
        return array(5,"'$arbiter' is not an arbiter");

    $qu = sql_exec("update disputes ".
        "set assignedto='arbiter:".$memberinfo["username"].
        "' where id=$did and assignedto is null");
    if( $qu === false) return private_dberr();

    return array(0,"Success");
}

function ff_unassigndispute( $disputeid)
{
    $did = intval(substr($disputeid,1));

    $qu = sql_exec("update disputes set assignedto=null ".
        "where id=$did and status='deliberating'");
    if( $qu === false) return private_dberr();

    return array(0,"Success");
}

function ff_getcharities()
{
    $charities = array();
    $qu = sql_exec("select * from charities order by lower(name)");
    if( $qu === false) return private_dberr();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $charities[intval($row["id"])] = array(
            "id" => intval($row["id"]),
            "name" => $row["name"],
            "addr1" => $row["addr1"],
            "addr2" => $row["addr2"],
            "city" => $row["city"],
            "province" => $row["province"],
            "country" => $row["country"],
            "postalcode" => $row["postalcode"],
            "contactname" => $row["contactname"],
            "phone" => $row["phone"],
            "fax" => $row["fax"],
            "email" => $row["email"],
            "description" => $row["description"],
            "website" => $row["website"],
            "current" => $row["current"],
            "total" => $row["total"]);
    }
    return array(0,$charities);
}

function ff_requestwithdrawal( $username, $email, $amount)
{
    $amount = scrubmoney( $amount);

    if( strpos($amount,"-") !== false || !ereg("[1-9]",$amount))
        return array(4,"Invalid amount for withdrawal: $amount");

    $qu = sql_exec("begin");
    if( $qu  === false) return private_dberr();

    // Make sure there's enough money in the reserve
    $qu = sql_exec("select subtract_money(reserve,'$amount') as remaining ".
        "from members where username='".sql_escape($username)."' for update");
    if( $qu  === false) return private_dberr(1);
    if( sql_numrows($qu) != 1)
        return private_dberr(2,"No such user: $username");
    $row = sql_fetch_array( $qu, 0);
    if( strpos($row["remaining"],"-") !== false)
        return private_dberr(9,"Insufficient funds in reserve");

    // Get a transaction ID
    $xid = sql_nextval("transaction_seq");
    if( $xid === false) return private_dberr(1);
    $split = 0;
    $now = time();
    $desc = "Withdrawal";

    // Subtract the money from the reserve
    $qu = sql_exec("update members set reserve='$row[remaining]' ".
        "where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr(1);

    // Schedule the withdrawal
    $qu = sql_exec("insert into withdrawal_queue (xid,email,amount,username) ".
        "values ($xid,'".sql_escape($email).
        "','$amount','".sql_escape($username)."')");
    if( $qu === false) return private_dberr(1);

    // Log the transaction
    $qu = sql_exec("insert into transaction_log ".
        "(xid,split,time,account,change,description) values ".
        "($xid,".(++$split).",$now,'reserve:$username',".
        "subtract_money('','$amount'),'".sql_escape($desc)."')");
    if( $qu === false) return private_dberr(1);

    $qu = sql_exec("insert into transaction_log ".
        "(xid,split,time,account,change,description) values ".
        "($xid,".(++$split).",$now,'withdrawals:".sql_escape($email)."',".
        "'$amount','".sql_escape($desc)."')");
    if( $qu === false) return private_dberr(1);

    // Inform the administrator of the withdrawal request
    $macros = array(
        "username" => $username,
    );
    $url = "withdrawals.php";
    $rc = al_triggerevent( "admin:", $url, "withdrawalrequest", $macros, 1);
    if( $rc[0]) return $rc;

    return private_commit();
}

function admin_groupwithdrawals()
{
    include("short-words.php");

    list($rc,$currencies) = ff_currencies();
    if( $rc) return array($rc,$currencies);

    foreach( $currencies as $code => $currency) {
        while(1) {
            // Choose a filename
            $filename = date("Ymd")."-$code-".
                $words[mt_rand(0,sizeof($words)-1)];

            $qu = sql_exec("begin");
            if( $qu === false) return private_dberr();

            // Lock the entire table.
            $qu = sql_exec("lock table withdrawal_queue");
            if( $qu === false) return private_dberr(1);

            // Make sure the filename isn't in use
            $qu = sql_exec("select * from withdrawal_queue ".
                "where filename='".sql_escape($filename)."'");
            if( $qu === false) return private_dberr(1);
            if( sql_numrows($qu) == 0) break;

            // Rollback and try again
            $qu = sql_exec("rollback");
            if( $qu === false) return private_dberr();
        }

        $qu = sql_exec("update withdrawal_queue set ".
            "filename='".sql_escape($filename)."' where ".
            "filename is null and amount like '%$code'");
        if( $qu === false) return private_dberr(1);

        $rc = private_commit();
        if( $rc[0]) return $rc;
    }
}

function admin_getwithdrawals( $filename)
{
    $qu = sql_exec("select * from withdrawal_queue ".
        "where filename='".sql_escape($filename)."'");
    if( $qu === false) return private_dberr();

    $withdrawals = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $withdrawals[] = array(
            "xid" => intval($row["xid"]),
            "username" => $row["username"],
            "email" => $row["email"],
            "amount" => $row["amount"],
            "filename" => $row["filename"]);
    }

    return array(0,$withdrawals);
}

function admin_getwithdrawalfilenames( $from_YYYYMMDD=false, $to_YYYYMMDD=false)
{
    if( $to_YYYYMMDD === false) $to_YYYYMMDD = $from_YYYYMMDD;

    $qu = sql_exec("select distinct filename from withdrawal_queue ".
        "where filename > '".sql_escape($from_YYYYMMDD).
        "' and filename < '".sql_escape($to_YYYYMMDD)."-ZZZ'");
    if( $qu === false) return private_dberr(1);

    $filenames = array();
    for( $i=0; $i < sql_numrows($qu); $i++) {
        $row = sql_fetch_array($qu,$i);
        $filenames[] = $row["filename"];
    }

    return array(0,$filenames);
}

function admin_setmemberauth( $username, $auth)
{
    $qu = sql_exec("update members set auth=".
        ($auth?"'".sql_escape($auth)."'":"null").
        " where username='".sql_escape($username)."'");
    if( $qu === false) return private_dberr();
    @sql_exec("update sessions set auth=".
        ($auth?"'".sql_escape($auth)."'":"null").
        " where username='".sql_escape($username)."'");
    return array(0,"Success");
}
?>
