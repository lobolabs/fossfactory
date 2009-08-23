<?
$hostname = $_SERVER["HTTP_HOST"];
if($hostname === "www.fossfactory.org") $hostname = "git.fossfactory.org";

// Figure out which code repo to use, if any
$repoproject = $id;
$repoprojinfo = $projinfo;
while( $repoprojinfo["parent"] != 0 &&
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
Git repository:<br><b><tt><?=htmlentities($repo)?></tt></b><br><br>
<? if( file_exists("/home/git/$repoproject.git")) { ?>
<a href="/gitweb?p=<?=$repoproject?>.git">Browse Git Repository</a>
<? } ?>
</div>
<h2>1. Download the code</h2>
<?
if( file_exists("/home/git/$repoproject.git")) {
    if( $repoproject == $id) {
?>
<p>
To work on this project, you will probably want to start by downloading
its Git repository as follows: (or just <a href="/gitweb?p=<?=$repoproject?>.git">browse it online</a>.)
</p>
<p style='margin-left:3em'>
<b><tt>git clone git@<?=$hostname?>:<?=$repoproject?></tt></b>
</p>
<p>
If it asks you for a password, enter "guest".
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
If it asks you for a password, enter "<b>guest</b>".
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

<h2>2. Make your changes</h2>
<p>
If you need an introduction to Git, <a href="http://git.or.cz/">the Git homepage</a> is a good place to start.
</p>
<p>
In short, just edit the code and commit your changes (to your local
repository) using <b><tt>git commit</tt></b>.  Note that when it's time to
submit your work, only committed changes will be part of the submission.
</p>

<h2>3. Install the ff Script</h2>

<p>
To submit your changes, you'll need to install the
<b><tt>ff</tt></b> script on your local system.  This script provides
a command-line interface for several FOSS Factory features.  Feel
free to <a href="ff">view the script</a> before installing it.
</p>
<p>
To install it, just place it anywhere in your path and make sure it's
executable.  For example, if you are running Ubuntu, you could type this:
</p>
<p style='margin-left:3em'><b><tt>
sudo curl http://www.fossfactory.org/ff -o /usr/local/bin/ff<br>
sudo chmod a+x /usr/local/bin/ff
</tt></b></p>
<p>
To use the script, start by typing <b><tt>ff help</tt></b>.  You can get
more detailed help on any of the commands by typing
<b><tt>ff help <i>&lt;command&gt;</i></tt></b>.
</p>

<h2>4. Set up Git write-access</h2>
<p>
To gain special access to FOSS Factory's Git repositories, type:
</p>
<p style='margin-left:3em'><b><tt>ff setup</tt></b></p>
<p>
This will transfer your SSH public key to the FOSS Factory server.
Note that you will only have push access to repositories for which you
are the project lead, and to your own code submission repositories.
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
