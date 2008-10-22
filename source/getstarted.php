<?
$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

// Figure out which code repo to use, if any
$repoproject = $id;
$repoprojinfo = $projinfo;
while( $projinfo["parent"] != 0 &&
        !file_exists("/home/git/$repoproject.git")) {
    $repoproject = "p$repoprojinfo[parent]";
    list($rc,$repoprojinfo) = ff_getprojectinfo( $repoproject);
    if( $rc) softexit();
}

if( file_exists("/home/git/$repoproject.git")) {
    $repo = "git@$hostname:$repoproject";
} else {
    $repo = "<none>";
}
?>
<div style='float:right;white-space:nowrap;border:1px solid black;background-color:#ffffd0;padding:1em'>
Project ID: <b><tt><?=$id?></tt></b><br><br>
Git repository:<br><b><tt><?=htmlentities($repo)?></tt></b>
</div>
<h2>1. Install the ff Script</h2>

<p>
To work on FOSS Factory projects, start by installing the
<b><tt>ff</tt></b> script on your local system.  This script provides
a command-line interface for several FOSS Factory features.  Feel
free to <a href="ff">view the script</a> before installing it.
</p>
<p>
To install it, just place it anywhere in your path and make sure it's
executable.  For example, you could type this as root:
</p>
<p style='margin-left:3em'><b><tt>
curl http://www.fossfactory.org/ff -o /usr/local/bin/ff<br>
chmod a+x /usr/local/bin/ff
</tt></b></p>
<p>
To use the script, start by typing <b><tt>ff help</tt></b>.  You can get
more detailed help on any of the commands by typing
<b><tt>ff help <i>&lt;command&gt;</i></tt></b>.
</p>

<h2>2. Set up Git access</h2>
<p>
To gain access to FOSS Factory's Git repositories, type:
</p>
<p style='margin-left:3em'><b><tt>ff setup</tt></b></p>
<p>
This will transfer your SSH public key to the FOSS Factory server.
</p>

<h2>3. Download the Code</h2>
<?
if( file_exists("/home/git/$repoproject.git")) {
    if( $repoproject == $id) {
?>
<p>
To work on this project, you will probably want to start by downloading
its Git repository as follows:
</p>
<p style='margin-left:3em'>
<b><tt>git clone git@<?=$hostname?>:<?=$repoproject?></tt></b>
</p>
<?
    } else {
?>
<p>
This project currently has no Git repository, but
<a href="<?=projurl($repoproject)?>">this related project</a> has one.
To work on this project, you will probably want to start by downloading
the related Git repository as follows:
</p>
<p style='margin-left:3em'>
<b><tt>git clone git@<?=$hostname?>:<?=$repoproject?></tt></b>
</p>
<p>
If you are the project lead for this project, consider creating its own
Git repository using the <b><tt>ff init</tt></b> command.
</p>
<?
    }
} else {
?>
<p>
This project has no Git repository.  If you are the project lead,
you should probably give it one by using the <b><tt>ff init</tt></b> command.
This command will allow you to clone an upstream Git repository, or to
create one from a source directory on your local filesystem.
</p>
<?
}
?>

<h2>4. Make your changes</h2>
<p>
If you need an introduction to Git, <a href="http://git.or.cz/">the Git homepage</a> is a good place to start.
</p>
<p>
In short, just edit the code and commit your changes (to your local
repository) using <b><tt>git commit</tt></b>.  Note that when it's time to
submit your work, only committed changes will be part of the submission.
</p>

<h2>5. Submit your work</h2>
<p>
To make a submission to this project, type:
</p>
<p style='margin-left:3em'><b><tt>ff submit <?=$id?></tt></b></p>
<p>
This will prompt you for your submission comments, then it will create
the submission.  The project lead will be notified automatically.
</p>
