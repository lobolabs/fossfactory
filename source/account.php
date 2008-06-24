<? /*
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
*/ ?>
<?php

$tab = scrub($_REQUEST["tab"]);
if( !$tab) $tab = "duties";

if ($username==='') {
    include("login.php");
    softexit();
}

list($rc,$memberinfo) = ff_getmemberinfo( $username);
if( $rc == 2) {
    print "No such member: $username";
    softexit();
}

if(isset($_REQUEST["deldraft"])) {
    ff_deleteprojectdraft( $_REQUEST["deldraft"], $username);
    header("Location: account.php?tab=drafts");
    exit;
}

if(isset($_REQUEST["setprefs"])) {
    list($rc,$news) = al_getwatches( $username, "news");
    if( $rc) {
        print "$rc $news";
        softexit();
    }

    list($rc,$promos) = al_getwatches( $username, "promos");
    if( $rc) {
        print "$rc $promos";
        softexit();
    }

    $rc = ff_setmemberinfo($username,
        false, false, false, $_REQUEST["prefcharity"]);
    if( $rc[0]) {
        print "$rc[0] $rc[1]";
        exit;
        header( "Location: account.php?tab=prefs&err=1");
        exit;
    }

    if( (sizeof($news) > 0) != ("$_REQUEST[news]" === "1")) {
        if( $_REQUEST["news"]) al_createwatch( "news", $username);
        else al_destroywatch( $news[0]["watchid"]);
    }

    if( (sizeof($promos) > 0) != ("$_REQUEST[promos]" === "1")) {
        if( $_REQUEST["promos"]) al_createwatch( "promos", $username);
        else al_destroywatch( $promos[0]["watchid"]);
    }

    header( "Location: account.php?tab=prefs");
    exit;
}

if(isset($_REQUEST['assigncredits'])) {
    // Get a list of the sponsorships keyed by project ID.
    $donations = array();
    foreach( $_REQUEST as $key => $value) {
        if( substr( $key, 0, 6) !== 'assign') continue;
        $donations[substr($key,6)] = floor($value*100);
    }

    // Get all of our current donations
    list($rc,$curdonations) = ff_memberdonations( $username);
    if( $rc) {
        header( "Location: account.php?tab=projects&err=1");
        exit;
    }

    $err = 0;

    // Now perform the assignments
    $badassignee = '';
    foreach( $_REQUEST as $key => $value) {
        if( substr( $key, 0, 2) !== 'a_') continue;
        $pid = substr($key,2);
        $assignee = trim($value);
        $curassignee = isset($curdonations[$pid]) ?
            $curdonations[$pid]["assignee"] : $username;
        if( $assignee === $curassignee) continue;
        $rc = ff_assigndonation( $pid, $username, $assignee);
        if( $rc[0] == 2) $badassignee = $assignee;
        if( $rc[0]) $err = 2;
    }

    if ($err!=2) $err="success"; 
    header("Location: account.php?tab=projects".
        ($err?"&err=$err&b=".urlencode($badassignee):""));
    exit;
}

if(isset($_REQUEST['subscribe'])) {
    list($rc,$currencies) = ff_currencies();
    if( !is_secure() || $rc) {
        header("Location: $GLOBALS[SITE_URL]".
            "account.php?tab=subscription&err=syserr");
        exit;
    }

    if( !isset( $currencies[$_REQUEST["c"]])) {
        header("Location: $GLOBALS[SITE_URL]account.php?tab=subscription");
        exit;
    }

    $currency = $currencies[$_REQUEST["c"]];
    $amount = round($_REQUEST["subscribe"]*$currency["multiplier"]);

    $sponsorships = array();
    foreach( $_REQUEST as $name => $value) {
        if( substr($name,0,7) === 'amount_') {
            $val = round($value*$currency["multiplier"]).$currency["code"];
            if( ereg("[1-9]",$val)) $sponsorships[substr($name,7)] = $val;
        }
    }

    list($rc,$err) = ff_setsubscription( $username, "$amount$currency[code]",
        "monthly", $sponsorships);
    header("Location: $GLOBALS[SITE_URL]account.php?tab=subscription".($rc?"&err=syserr":""));
    exit;
}

if(isset($_REQUEST['withdraw'])) {
    list($rc,$currencies) = ff_currencies();
    if( !is_secure() || $rc || !isset( $currencies[$_REQUEST["currency"]])) {
        header("Location: $GLOBALS[SITE_URL]".
            "account.php?tab=reserve&err=syserr");
        exit;
    }

    $currency = $currencies[$_REQUEST["currency"]];

    $amount = round($_REQUEST["withdraw"]*
        $currency["multiplier"]).$currency["code"];

    $email = $_REQUEST["email"];
    if( ereg("[^-._+a-zA-Z0-9@]",$email)) {
        header("Location: $GLOBALS[SITE_URL]".
            "account.php?tab=reserve&err=bademail");
        exit;
    }

    list($rc,$err) = ff_requestwithdrawal($username,$email,$amount);
    if( $rc == 9) {
        header("Location: $GLOBALS[SITE_URL]".
            "account.php?tab=reserve&err=toomuch");
        exit;
    } else if( $rc) {
        header("Location: $GLOBALS[SITE_URL]".
            "account.php?tab=reserve&err=syserr");
        exit;
    }

    header("Location: $GLOBALS[SITE_URL]".
        "account.php?tab=reserve&err=success&amount=$amount");
    exit;
}

apply_template("My Factory",array(
    array("name"=>"My Factory", "href"=>"account.php"),
));

include_once("tabs.php");

$tabs = array(
    "duties" => "My Duties",
    "projects" => "My Projects",
    "prefs" => "Settings",
    "subscription" => "Monthly Sponsorship",
    "reserve" => "My Reserve",
    "drafts" => "Drafts",
);
tab_header( $tabs, "account.php", $tab, "duties");

include_once("myprojectsfunc.php");

if( $tab == 'projects') {
    include_once("myprojects.php");
} else if( $tab == 'duties') {
    include_once("myduties.php");
} else if( $tab == 'prefs') {
    include_once("prefs.php");
} else if( $tab == 'subscription') {
    include_once("subscription.php");
} else if( $tab == 'reserve') {
    include_once("reserve.php");
} else if( $tab == 'drafts') {
    include_once("drafts.php");
}

tab_footer();
?>
