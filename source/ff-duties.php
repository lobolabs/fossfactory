<?
header("Content-type: text/plain");

$username = $_REQUEST["u"];
$password = $_REQUEST["p"];

// Verify the user's credentials
list($rc,$err) = ff_checkpassword($username, $password);
if( $rc == 5 || $rc == 2) {
    print "Incorrect username or password\n";
    exit;
} else if( $rc) {
    print "Internal system error: $rc $err\n";
    exit;
}

include_once("getduties.php");

list($rc,$duties) = getduties($username);
if( $rc) {
    print "Internal system error: $rc $duties\n";
    exit;
}

print "You have ".(sizeof($duties)==1?"1 duty.":sizeof($duties)." duties")."\n";
foreach( $duties as $duty) {
    print "--\n";
    print "Project: $duty[projectid]";
    list($rc,$projectinfo) = ff_getprojectinfo($duty["projectid"]);
    if( !$rc) print " \"$projectinfo[name]\"";
    print "\n";
    print "Subject: $duty[subject]\n";
    print "Deadline: ".($duty["deadline"]?date("D F j, H:i:s T",$duty["deadline"]):"unassigned")."\n";
    print "URL: $GLOBALS[SITE_URL]$duty[link]\n";
}
?>
