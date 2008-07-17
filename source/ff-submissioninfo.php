<?
header("Content-type: text/plain");

list($rc,$submissioninfo) = ff_getsubmissioninfo(substr("$_REQUEST[id]",1));

if( $rc == 2) {
    print "nosuchsubmission\n";
    exit;
} else if( $rc) {
    print "syserr\n";
    exit;
}

foreach( $submissioninfo as $key => $value) {
    print urlencode($key)."=".urlencode($value)."\n";
}
?>
