<? /*
Copyright 2007-2008 John-Paul Gignac
Copyright 2008      FOSS Factory Inc.

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
$srcdir = realpath(realpath(".")."/..");

if( getenv("PATH_INFO")) {
    header("Content-type: application/octet-stream");

    if( getenv("PATH_INFO") === '/fossfactory-src-LIVE.tar.gz') {
        passthru("cd ".escapeshellarg($srcdir).
            "; tar cz LICENSE contributors README source/ schema/ ".
            "--transform 's,^,fossfactory-src/,' ".
            "--exclude .svn --owner root --group root");
    } else if( ereg("^/[-._a-zA-Z0-9]+$",getenv("PATH_INFO")) &&
        is_file("$srcdir/releases".getenv("PATH_INFO"))) {
        readfile( "$srcdir/releases".getenv("PATH_INFO"));
    } else {
        print "You're cool.";
    }

    exit;
}

apply_template("FOSS Factory Source",
    array(array("name"=>"FOSS Factory Source","href"=>"get-source.php")));
?>
<h1>FOSS Factory Source</h1>
<p>
The source code for this website is distributed under
the <a href="http://www.gnu.org/licenses/agpl.html">GNU
Affero General Public License</a>.
</p>

<?
// Get a list of the files in the releases directory
$releases = array();
if( is_dir("$srcdir/releases")) {
    $dir = opendir( "$srcdir/releases");
    if( $dir !== false) {
        while( ($file = readdir($dir)) !== false) {
            if( substr($file,0,1) === '.') continue;
            $releases[] = $file;
        }
    }
    closedir($dir);
}

if( sizeof($releases)) {
?>
<table border=1 cellspacing=0 cellpadding=4>
<tr><th>Version</th><th>Size</th></tr>
<?
    foreach( $releases as $release) {
?>
<tr><td><a href="get-source.php/<?=htmlentities($release)?>"><?=htmlentities($release)?></a></td><td><?=round(filesize("$srcdir/releases/$release")/100000)/10?> Mb</td></tr>
<?
    }
?>
</table>
<p>
Or you can download the source code
<a href="get-source.php/fossfactory-src-LIVE.tar.gz">
as it exists on this server right now</a>.
This may or may not be identical to one of the above files.
</p>
<?
} else {
?>
<p>
<a href="get-source.php/fossfactory-src-LIVE.tar.gz">Download the source code</a>
</p>
<p>
The above link downloads a copy of the source code as it exists on this
server right now.  It may or may not correspond with any official release
version of the software.
</p>
<?
}
?>
<p>
If you'd like to help improve FOSS Factory, start by checking out our list of <a
href="http://www.fossfactory.org/project.php?p=p30&tab=subprojects">requested
improvements</a> and feel free to add your own ideas to the list.
</p>
