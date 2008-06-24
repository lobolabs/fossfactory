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
function diffText( $before, $after, $label='orig') {
    // Prepare the input for diffing
    $before = private_prepDiff($before);
    $after = private_prepDiff($after);

    $tmp2 = tempnam( "/tmp", "diff");
    $tmp = "$tmp2-$label";

    $out = fopen( $tmp, "wb");
    if( !$out) return array(1,"Can't open tmp file $tmp");
    $rc = fwrite( $out, $before);
    if( fclose($out) === false || $rc === false) {
        return array(1,"Error writing to tmp file $tmp");
    }

    $out = fopen( $tmp2, "wb");
    if( !$out) return array(1,"Can't open tmp file $tmp2");
    $rc = fwrite( $out, $after);
    if( fclose($out) === false || $rc === false) {
        return array(1,"Error writing to tmp file $tmp2");
    }

    // We'll use 7 words of context.
    $cmd = "cd ".escapeshellarg(dirname($tmp)).
        "; diff -U 7 ".escapeshellarg(basename($tmp)).
            " ".escapeshellarg(basename($tmp2));
    $diff = `$cmd`;

    unlink( $tmp);
    unlink( $tmp2);

    return array(0,$diff);
}

function private_prepDiff( $txt) {
    // Cleanup - this part is not reversible
    $txt = str_replace("\r", "", $txt);
    $txt = rtrim($txt)."\n";
    $txt = str_replace("\t", "    ", $txt);
    $txt = ereg_replace("  *\n", "\n", $txt);

    // Place each word on its own line in a reversible way
    $txt = str_replace("\n", "\n\n", $txt);
    $txt = ereg_replace("([^ \n])(  *)", "\\1\n\\2", $txt);

    return $txt;
}

function private_unprepDiff( $txt) {
    // Undo the reversible part of private_prepDiff()
    $txt = ereg_replace("([^\n])\n([^\n])", "\\1\\2", $txt);
    $txt = str_replace("\n\n", "\n", $txt);
    return $txt;
}

function patchText( $before, $diff, $reverse=0) {
    $tmp = tempnam( "/tmp", "patch");
    $tmp2 = "$tmp.diff";

    $out = fopen( $tmp, "wb");
    if( !$out) return array(1,"Can't open tmp file $tmp");
    $rc = fwrite( $out, private_prepDiff($before));
    if( fclose($out) === false || $rc === false) {
        return array(1,"Error writing to tmp file $tmp");
    }

    $out = popen( "/usr/bin/patch ".($reverse?"-R ":"").
        "-f ".escapeshellarg($tmp), "wb");
    if( !$out) return array(1,"Can't execute patch command");
    $rc = fwrite( $out, str_replace("\r","",$diff));
    $err = pclose( $out);
    $result = file_get_contents( $tmp);
    @unlink( $tmp);
    @unlink( "$tmp.orig");
    @unlink( "$tmp.rej");

    if( $rc === false || $result === false || $err >= 2)
        return array(1,"System error applying the patch");
    if( $err == 1)
        return array(7,"$tmp:The patch doesn't apply cleanly");

    return array(0,private_unprepDiff($result));
}

function formatDiff( $diff) {
    $diff = htmlentities($diff);
    $diff = ereg_replace("^---[^\n]*\n\\+\\+\\+[^\n]*\n", "", $diff);
    $diff = ereg_replace("\n@@[^\n]*\n", " ...\n <br><br>...\n", $diff);
    $diff = ereg_replace("^@@ -1,[^\n]*\n", "\n", $diff);
    $diff = ereg_replace("^@@[^\n]*\n", " ...\n", $diff);

    // Replace single-line anchors with single-line changes
    while( ereg("\n[-+][^\n]*\n [^\n]*\n[-+]", $diff)) {
        $diff = ereg_replace("(\n[-+][^\n]*\n) ([^\n]*\n)([-+])",
            "\\1-\\2+\\2\\3", $diff);
    }
    // Replace certain double-line anchors with double-line changes
    while( ereg("\n\\+[^\n]*\n\\+[^\n]*\n [^\n]*\n [^\n]*\n-", $diff)) {
        $diff = ereg_replace("(\n\\+[^\n]*\n\\+[^\n]*\n)".
            "([^\n]*\n) ([^\n]*\n)-", "\\1+\\2+\\3-\\2-\\3-", $diff);
    }

    // Rearrange into proper sections
    $a = explode("\n", $diff);
    $n = sizeof($a);
    $diff = '';
    for( $i=0; $i < $n; $i++) {
        $minus = $plus = '';
        for( $j=$i; $j < $n; $j++) {
            $chr = substr($a[$j],0,1);
            if( $chr == '-') $minus .= $a[$j]."\n";
            else if( $chr == '+') $plus .= $a[$j]."\n";
            else break;
        }
        $i = $j;
        $diff .= $minus.$plus.$a[$j]."\n";
    }

    $diff = str_replace("\n \n", "\n <br>\n", $diff);
    $diff = str_replace("\n-\n", "\n-<br>\n", $diff);
    $diff = str_replace("\n+\n", "\n+<br>\n", $diff);
    $diff = ereg_replace("(\n[^+][^\n]*\n|^\n)\\+", "\\1=", $diff);
    $diff = ereg_replace("(\n[^-][^\n]*\n|^\n)-", "\\1_", $diff);
    $diff = ereg_replace("\n[-+]", " ", $diff);
    $diff = ereg_replace("\n_([^\n]*)\n", "\n<del>\\1</del>\n", $diff);
    $diff = ereg_replace("\n=([^\n]*)\n", "\n<ins>\\1</ins>\n", $diff);
    return "<tt>$diff</tt>";
}
?>
