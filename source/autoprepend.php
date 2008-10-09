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
if( isset($_GET["trk"])) {
    // The trk variable is only used for analytics, to help determine
    // where people are clicking.  We don't want it to show up in the URL.
    header("Location: $_SERVER[SCRIPT_NAME]");
    exit;
}

// Quick sanity check
if( get_magic_quotes_gpc()) {
    print "<b>Please disable magic_quotes_gpc in ".
        get_cfg_var("cfg_file_path")." and reload Apache.</b>\n";
    exit;
}

if( get_cfg_var('register_globals')) {
    print "<b>Please disable register_globals in ".
        get_cfg_var("cfg_file_path")." and reload Apache.</b>\n";
    exit;
}

$GLOBALS['IS_PRODUCTION'] = ($_SERVER["HTTP_HOST"] === 'www.fossfactory.org'
    && substr($_SERVER["SCRIPT_NAME"],1,1) !== '~');

$GLOBALS['SITE_URL'] = "http://$_SERVER[HTTP_HOST]".dirname($_SERVER["SCRIPT_NAME"]);
$GLOBALS['SECURE_URL'] = ($_SERVER["HTTP_HOST"]==='www.fossfactory.org'?"https://$_SERVER[HTTP_HOST]".dirname($_SERVER["SCRIPT_NAME"]):"http://$_SERVER[HTTP_HOST]/////".dirname($_SERVER["SCRIPT_NAME"]));
if( substr($GLOBALS['SITE_URL'],-1) !== '/') {
    $GLOBALS['SITE_URL'] .= "/";
    $GLOBALS['SECURE_URL'] .= "/";
}

include_once("config.php");
include_once("db.php");
include_once("buttons.php");

function is_secure() {
    list($rc,$require_https) = ff_config("require_https");
    if( $rc && $rc != 2) return FALSE;
    if( !$rc && $require_https) return $_SERVER["HTTPS"]?TRUE:FALSE;
    // On sandboxes, a bunch of slashes is our way of pretending it's https.
    return strpos($_SERVER["REQUEST_URI"],"/////") !== false;
}

$ACCEPTABLE_FORMATS = array(
    "asp" => "text/plain",
    "avi" => "video/x-msvideo",
    "c" => "text/plain",
    "cc" => "text/plain",
    "cgi" => "text/plain",
    "c++" => "text/plain",
    "doc" => "application/msword",
    "eps" => "application/postscript",
    "fli" => "video/fli",
    "gif" => "image/gif",
    "gnumeric" => "application/gnumeric",
    "htm" => "text/html",
    "html" => "text/html",
    "java" => "text/plain",
    "jpeg" => "image/jpeg",
    "jpg" => "image/jpeg",
    "jsp" => "text/plain",
    "mid" => "audio/midi",
    "midi" => "audio/midi",
    "mp3" => "audio/mpeg",
    "mpeg" => "video/mpeg",
    "mpg" => "video/mpeg",
    "odb" => "application/vnd.oasis.opendocument.database",
    "odc" => "application/vnd.oasis.opendocument.chart",
    "odf" => "application/vnd.oasis.opendocument.formula",
    "odg" => "application/vnd.oasis.opendocument.graphics",
    "odi" => "application/vnd.oasis.opendocument.image",
    "odm" => "application/vnd.oasis.opendocument.text-master",
    "odp" => "application/vnd.oasis.opendocument.presentation",
    "ods" => "application/vnd.oasis.opendocument.spreadsheet",
    "odt" => "application/vnd.oasis.opendocument.text",
    "pdf" => "application/pdf",
    "php" => "text/plain",
    "pl" => "text/plain",
    "png" => "image/png",
    "ps" => "application/postscript",
    "py" => "text/plain",
    "sh" => "text/plain",
    "txt" => "text/plain",
    "wav" => "audio/x-wav",
    "xls" => "application/vnd.ms-excel",
);

$sid = '';
$username = '';
$auth = '';
if( isset( $_COOKIE["ff_session"])) {
    $sid = "$_COOKIE[ff_session]";
    $secure_sid = "$_COOKIE[ff_secure_session]";
    $rc = ff_getsessioninfo( $sid);
    if( $rc[0] === 2) $sid = '';
    else if( $rc[0]) {
        print "Internal system error: $rc[0] $rc[1]";
        exit;
    } else {
        if( $_SERVER["HTTPS"]) {
            // Make sure the secure sid has been provided
            if( $secure_sid !== $rc[1]["secure_sid"]) {
                header( "Location: logout.php");
                exit;
            }
        }

        $username = "".$rc[1]["username"];
        $auth = "".$rc[1]["auth"];
    }
}
$pref_currency = 'USD';
if( isset( $_COOKIE["ff_pref_currency"])) {
    list($rc,$currencies) = ff_currencies();
    if( isset($currencies["$_COOKIE[ff_pref_currency]"]))
        $pref_currency = "$_COOKIE[ff_pref_currency]";
}
$GLOBALS["auto_apply_footer"] = 0;
function apply_template($title='',$breadcrumbs=false,$onload='',$styles=false,$showfeaturedprojects=false)
{
if( !$title) $title = "FOSS Factory";
if( !is_array( $breadcrumbs)) $breadcrumbs = array();
if( sizeof($breadcrumbs))
    array_unshift( $breadcrumbs, array("name"=>"Home","href"=>"./"));
if( $styles === false) $styles = array(
    "style","header-style","footer-style","project-style");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
            "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<? foreach( $styles as $style) { ?>
<link rel="stylesheet" type="text/css" href="<?=htmlentities($style)?>.css" />
<? } ?>
<base href="http://<?="$_SERVER[HTTP_HOST]$_SERVER[SCRIPT_NAME]".($_SERVER["QUERY_STRING"]?"?".$_SERVER["QUERY_STRING"]:"")?>" />
<title><?=htmlentities($title)?></title>
<script>
    function currency_ul_toggle() {
        var o=document.getElementById('currency');
        if (o.className=="closed") 
            o.className="open";
        else 
            o.className="closed";
        return false;
    }
    function currency_ul_close() {
        var o=document.getElementById('currency');
        o.className="closed";
    }
    function set_currency(code) {
        document.cookie = "ff_pref_currency="+code+";expires="+
            (new Date(new Date().getTime()+1000*60*60*24*365).toGMTString())+
            ";path=/";
        window.location.reload(true);
        return false;
    }
    function login_submit() {
        var o=document.getElementById('login_form');
        alert (o.elements[0]+", "+o.elements[1]+", "+o.elements[2]);
        o.submit();
    }
</script>
<script language="javascript" src="search.js"></script>
<script language="javascript" src="account.js"></script>
</head>
<body<?=$onload?' onload="'.htmlentities($onload).'"':''?><?=$GLOBALS["username"] !== ''?" class=loggedin":""?>>
<div id="header">
    <a href="./" id="fflogo"><img src="logo.png" width=314 height=36></a>
    <div id="decoration1"></div>

<? if( $GLOBALS["username"] !== '') { ?>
<? list($rc,$memberinfo) = ff_getmemberinfo($GLOBALS["username"]); ?>
<!--if username logged in-->
    <div id="welcome">
        Welcome <em><?=htmlentities($GLOBALS['username'])?></em>
    </div>
    <div id="mystuff">
        <div id="myfactory">
            <a href="account.php">My Factory</a>
<? if( strpos(getenv("HTTP_USER_AGENT"),"MSIE") === false &&
        strpos(getenv("HTTP_USER_AGENT"),"Internet Explorer") === false) { ?>
    
            <ul>
                <li><a href="account.php#tabs">My Duties</a></li>
                <li><a href="account.php?tab=projects#tabs">My Projects</a></li>
                <li><a href="account.php?tab=prefs#tabs">Settings</a></li>
                <li><a href="account.php?tab=subscription#tabs">Monthly Sponsorship</a></li>
                <li><a href="account.php?tab=reserve#tabs">My Reserve</a></li>
                <li><a href="account.php?tab=drafts#tabs">Drafts</a></li>
            </ul>
<? } ?>
        </div>
        <a href="account.php?tab=projects" id="sponsorships">My Sponsorships: <em><?=convert_money($memberinfo["current_sponsorships"])?></em></a>
        <a href="account.php?tab=reserve" id="myreserve">My Reserve: <em><?=convert_money($memberinfo["reserve"])?></em></a>
    </div>
<? } else { ?>
    <div id="sign_up">
        Not a member?<a id="signup_button" href="signup.php">Sign Up</a>
    </div>
    <script>
    function submitenter(o,e) {
        var keycode;
        if( window.event) keycode = window.event.keyCode;
        else if(e) keycode = e.which;
        if( keycode == 13) {
            o.form.submit();
            return false;
        }
        return true;
    }
    </script>
    <form method=post action="<?=$GLOBALS["SECURE_URL"]?>login.php" id="login_form"> <label for="loginuserid">Username</label><input type="text" id="loginuserid" name=userid><label for="password">Password</label><input  name="password" type="password" onKeyPress='return submitenter(this,event)'><label for="remember">Remember?</label><input type="checkbox" id="remember" name="remember">
        <a href="login" onClick="document.getElementById('login_form').submit();return false">Login</a>
<? if( $_SERVER["QUERY_STRING"] || basename($_SERVER["SCRIPT_NAME"]) === 'newproject.php') { ?>
        <input type=hidden name=url value="<?=htmlentities(basename($_SERVER["SCRIPT_NAME"]).($_SERVER["QUERY_STRING"]?"?".$_SERVER["QUERY_STRING"]:""))?>">
<? } ?>
    </form>
<? } ?>
    <div style='clear:both'></div>
    <div id="currency">
<? list($rc,$currencies) = ff_currencies(); ?>
        <a href="" onClick="return currency_ul_toggle()" onMouseOver="return currency_ul_close()"><?=htmlentities($currencies[$GLOBALS["pref_currency"]]["name"])?></a>
        <ul class="closed">
<? foreach( $currencies as $code => $currency) {
    if( $code === $GLOBALS["pref_currency"]) continue;
?>
            <li><a href="" onClick="return set_currency('<?=$code?>')"><?=htmlentities($currency["name"])?></a></li>
<? } ?>
        </ul>
    </div>
<? if( $GLOBALS["username"] !== '') { ?>
<!--if username logged in (we check again)-->
    <a href="logout.php" id="logout">Logout</a>
<? } ?>
    <ul id='breadcrumbs'>
        <a id="new_project" href="newproject.php">New Project</a>

        <!-- Google CSE Search Box Begins  -->
        <form action="http://www.google.com/cse" id="searchbox_010024490006809046004:b7d25d-b9rs">
        <a href="browse.php">Browse Projects</a> |
        <input type="hidden" name="cx" value="010024490006809046004:b7d25d-b9rs" />
        <input type="text" name="q" size="20" />
        <input type="submit" name="sa" value="Search" />
        </form>
        <script type="text/javascript" src="http://www.google.com/coop/cse/brand?form=searchbox_010024490006809046004%3Ab7d25d-b9rs&lang=en"></script>
        <!-- Google CSE Search Box Ends -->
<?php
if( sizeof($breadcrumbs)) {
    for( $i=0; $i < sizeof($breadcrumbs); $i++) {
        print "        <li".($i==0?" class='first-of-type'":"").">";
        if ($i==0) print "&nbsp;&nbsp;";
        print "<a href=\"".htmlentities($breadcrumbs[$i]["href"])."\">";
        print htmlentities($breadcrumbs[$i]["name"])."</a>";
        if ($i>=0 && $i!=(sizeof($breadcrumbs)-1)) print "&nbsp;>&nbsp;</li>\n";
        else print "</li>\n";
    }
}
?>
    </ul>
</div>
<div id="content_body">
<?php
    if( $showfeaturedprojects) {
?>
<div class="featured"><div><div><div>
<h2>Featured Projects</h2>
<?
        $i = 0;
        list($rc,$features) = ff_getfeaturedprojects();
        if( $rc) {
            print "A database error occurred: $rc $features";
        } else if( sizeof($features) == 0) {
            print "Sorry, there are no featured projects.";
        } else {
            foreach( $features as $feature) {
                $i++;
?>
<div class=<?=$i&1?"oddrow":"evenrow"?>><div class=money><?=convert_money($feature["bounty"])?></div><a href="project.php?p=<?=$feature["id"]?>"><?=htmlentities($feature["name"])?></a></div>
<?php
            }
        }
?>
</div></div></div><div class="bottom <?=$i&1?"oddbottom":"evenbottom"?>"><div><div><div><div></div></div></div></div></div></div>
<? //stallman's comment 
     if( basename($_SERVER['SCRIPT_NAME'])=='charities.php'  ) { ?>

<div class=sidenote1>
"This is a good initiative for supporting free software projects.
I hope that you fund many useful developments.<br><br>

Thank you also for contributing a small percentage to the FSF as well
as other worthy causes."<p>Richard Stallman<em><br>Free Software Foundation<br>President and founder</em></p>
</div>
<? } ?>


<div class="mainbody withsidebar">
<?
    } else if( basename($_SERVER['SCRIPT_NAME'])=='intro.php'  )
    {?>

    <?}
      else {
?>
<div class=mainbody>
<?
    }

    $GLOBALS["auto_apply_footer"] = 1;
}

function softexit() {
    include("autoappend.php");
    exit;
}

function scrubmoney($money) {
    return ereg_replace("[^-+0-9A-Z]","",$money);
}

function scrub($val) {
    return ereg_replace("[^-._a-zA-Z0-9]","",$val);
}

function xmlescape( $s)
{
    $s = str_replace( "&", "&amp;", $s);
    $s = str_replace( "'", "&apos;", $s);
    $s = str_replace( ">", "&gt;", $s);
    $s = str_replace( "<", "&lt;", $s);
    $s = str_replace( '"', "&quot;", $s);
    return $s;
}

function add_commas($str,$places) {
    if( strlen($str) <= $places) return $str;
    return add_commas(substr($str,0,-$places),$places).
        ",".substr($str,-$places);
}

function format_money($val, $currency_when_0=false) {
    if( $currency_when_0 === false)
        $currency_when_0 = $GLOBALS["pref_currency"];
    $val = substr(ereg_replace("\+0[A-Z]+","","+$val"),1);
    if( $val == '') $val = "0$currency_when_0";
    $parts = explode("+",$val);
    $out = array();
    list($rc,$currencies) = ff_currencies();
    if( $rc) return "Unavailable";
    foreach( $parts as $part) {
        ereg("([0-9.-]+)([A-Z]+)",$part,$regs);
        $currency = $regs[2];
        $n = $regs[1];
        $prefix = "";
        if(substr($n,0,1)==='-') {
            $n = substr($n,1);
            $prefix = "-";
        }
        $info = $currencies[$currency];
        while(strlen($n) <= $info["decimal_places"]) $n = "0$n";
        $intpart = substr($n,0,-$info["decimal_places"]);
        $fracpart = substr($n,-$info["decimal_places"]);
        if( $currency==='INR')
            $intpart = add_commas(substr($intpart,0,-1),2).substr($intpart,-1);
        else
            $intpart = add_commas($intpart,3);
        $out[] = "$info[prefix]$prefix$intpart.$fracpart";
    }
    return join(" + ",$out);
}

function converted_value($val,$currency=false) {
    if( $currency === false) $currency = $GLOBALS["pref_currency"];
    if( $val==='' || ereg("^[0-9.-]+$currency$", $val))
        return currency_value($val,$currency);

    list($rc,$currencies) = ff_currencies();
    if( $rc) return "Unavailable";

    $total = 0;
    $expr = "0";
    foreach( $currencies as $code => $c) {
        if( ereg("([0-9]+)$code", $val, $regs)) {
            $expr .= "+($regs[1]*$c[exchange_rate])";
        }
    }
    $expr = "scale=10;0.5+($expr)/".$currencies[$currency]["exchange_rate"];
    $result = ereg_replace("\\..*","",`echo '$expr' | bc`);
    if( $result === '') $result = '0';

    return $result;
}

function convert_money($val,$currency=false) {
    if( $currency === false) $currency = $GLOBALS["pref_currency"];
    return format_money(converted_value($val,$currency).$currency,$currency);
}

function money($val,$commas=1) {
    $val = intval($val);
    $result = ".".substr("00".($val%100),-2);
    $val = floor($val/100);
    if( $commas) {
        while( $val > 1000) {
            $result = ",".substr("000".($val%1000),-3).$result;
            $val = floor($val/1000);
        }
    }
    return "$".$val.$result;
}

function format_for_entryfield($val,$currency=false) {
    if( $currency === false) $currency = $GLOBALS["pref_currency"];
    list($rc,$currencies) = ff_currencies();
    if( $rc) return "format-error";
    $d = $currencies[$currency]["decimal_places"];
    if( strlen($val) <= $d) $val = str_repeat("0",$d-strlen($val)+1).$val;
    return substr($val,0,-2).".".substr($val,-2);
}

function currency_value($money,$currency=false) {
    if( $currency === false) $currency = $GLOBALS["pref_currency"];
    if( ereg($currency,$money) === false) return 0;
    return ereg_replace(".*[^0-9-]","",
        ereg_replace("$currency.*","",":$money"));
}

function jsencode($s) {
    $s = str_replace( "\\", "\\\\", $s);
    $s = str_replace( "'", "\\'", $s);
    $s = str_replace( "\"", "\\\"", $s);
    $s = str_replace( "/", "\\/", $s);
    $s = str_replace( "\n", "\\n'+\n'", $s);
    $s = str_replace( "\r", "\\r", $s);
    $s = str_replace( "\000", "\\000", $s);
    $s = preg_replace( "/([\001-\011\013-\037\177-\377])/e",
        "'\\\\\\\\'.substr('000'.decoct(ord('\\1')),-3)", $s);
    return $s;
}

if( $_REQUEST["requser"] &&
    $_REQUEST["requser"] !== $username) {
    include_once("login.php");
    softexit();
}

$GLOBALS["priorities"] = array('enhancement'=>1,'low'=>2,'medium'=>3,'high'=>4,'critical'=>5,'blocker'=>6,'subproject'=>0);
?>
