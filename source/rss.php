<?php

/*
Copyright 2008 Mart Roosmaa

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
*/

require_once( dirname( __FILE__ ) . '/../contrib/feedcreator.class.php' );

$cache_dir = $GLOBALS['DATADIR'].'/feed';
if( !file_exists( $cache_dir ) )
    @mkdir( $cache_dir );

header( 'Content-Type: application/xml; charset=UTF-8' );

switch( $_GET['src'] ) {
    case 'projects':
        $file = $cache_dir.'/projectList.rss2.xml';
        break;
    default:
        $file = null;
        break;
}

// Use the cached file is it is frech enough (< 15 mins):
if( $file !== null && file_exists( $file ) && time() - filemtime( $file ) < 900 ) {
    readfile( $file );
    exit;
}

$rss = new UniversalFeedCreator();
$rss->syndicationURL = $GLOBALS['SITE_URL'].basename( $_SERVER['PHP_SELF'] );

$image = new FeedImage();
$image->title = 'FOSS Factory';
$image->url = $GLOBALS['SITE_URL'].'logo.png';
$image->link = $GLOBALS['SITE_URL'];
$image->description = 'Feed provided by FOSS Factory. Click to visit.';
$rss->image = $image;

if( $_GET['src'] == 'projects' ) {
    include_once("formattext.php");

    $rss->title = 'FOSS Factory projects';
    $rss->description = 'List of newest projects on FOSS Factory.';
    $rss->link = $GLOBALS['SITE_URL'].'browse.php';

    list( $rc, $projects ) = ff_getnewprojects( 30 );
    if( $rc == 0 )
        foreach( $projects as $p ) {
            $item = new FeedItem();
            $item->title = $p['name'];
            $item->link = $GLOBALS['SITE_URL'].projurl($p['id']);
            $item->guid = $item->link;
            $item->date = (int)$p['created'];
            $item->author = $p['creator'];

            $item->description = '
                <p>
                Creator: '.xmlescape($p['creator']).'<br>
                Requirements:<br><br>
                    '.formattext($p['reqmts']).'
                </p>
                ';
            $rss->addItem( $item );
        }
} else if( $_GET['src'] == 'duties' ) {
    include_once("getduties.php");
    include_once("formattext.php");
    
    $user = scrub( $_GET['u'] );
    
    $rss->title = '[FF] '.$user.'\'s duties';
    $rss->description = $user.'\' duties on FOSS Factory.';
    $rss->link = $GLOBALS['SITE_URL'].'account.php#tabs';
    
    list( $rc, $duties ) = getduties( $user );
    if( $rc == 0 )
        foreach( $duties as $d ) {
            $item = new FeedItem();
            $item->title = $d['subject'];
            $item->link = $d['link'];
            $item->guid = $d['guid'];
            $item->date = $d['deadline'];
            $item->description = formatText( $d['body'] );
            $rss->addItem( $item );
        }
} else if( $_GET['src'] == 'projectevents' ) {
    include_once("formattext.php");
    
    $pid = scrub( $_GET['p'] );
    $pname = 'Unknown';
    
    list( $rc, $prjinf ) = ff_getprojectinfo( $pid );
    if( $rc == 0 ) {
        $pname = $prjinf['name'];
        $watches = array(array("eventid"=>"$pid-news"));
        list( $rc, $events ) = al_getrecentevents( 'watch:'.$pid.'-news' );
        if( $rc == 0 )
            foreach( $events as $e ) {
                $item = new FeedItem();
                $item->title = $e['subject'];
                $item->link = $GLOBALS['SITE_URL'].$e['url'];
                $item->date = (int)$e['time'];
                $item->description = formatText( $e['body'] );
                $rss->addItem( $item );
            }
    }
    
    $rss->title = '[FF] '.$pname;
    $rss->description = 'Recent events affecting FOSS Factory project \''.$pname.'\'';
    $rss->link = $GLOBALS['SITE_URL'].projurl($pid);
} else if( $_GET['src'] == 'userevents' ) {
    include_once("formattext.php");
    
    $user = scrub( $_GET['u'] );
    
    list( $rc, $watching ) = al_getwatches( $user );
    if( $rc == 0 )
        foreach( $watching as $w ) {
            list( $rc, $events ) = al_getrecentevents( 'watch:'.$w['eventid'] );
            if( $rc != 0 )
                continue;
            
            foreach( $events as $e ) {
                $item = new FeedItem();
                $item->title = $e['subject'];
                $item->link = $GLOBALS['SITE_URL'].$e['url'];
                $item->date = (int)$e['time'];
                $item->description = formatText( $e['body'] );
                $rss->addItem( $item );
            }
        }
    
    $rss->title = '[FF] '.$user.'\'s News';
    $rss->description = $user.'\'s FOSS Factory news';
    $rss->link = $GLOBALS['SITE_URL'];
} else {
    header( 'Location: index.php' );
    exit;
}

if( $file !== null ) {
    $rss->saveFeed( 'RSS2.0', $file, false );
    @readfile( $file );
} else {
    echo $rss->createFeed( 'RSS2.0' );
}

?>
