<?
header("Content-type: text/plain");

list($rc,$projectinfo) = ff_getprojectinfo("$_REQUEST[id]");

if( $rc == 2) {
    print "nosuchproject\n";
    exit;
} else if( $rc) {
    print "syserr\n";
    exit;
}

foreach( $projectinfo as $key => $value) {
    print urlencode($key)."=".urlencode($value)."\n";
}

$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

if( is_dir( "/home/git/$projectinfo[id].git")) {
    print "gitrepo=".urlencode("git@$hostname:$projectinfo[id]")."\n";
}

print "fbounty=".urlencode(format_money($projectinfo["bounty"]))."\n";
?>
