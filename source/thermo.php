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

$p = (float)$_GET['p'];
$total = $_GET['t'];

if( $total == '' )
    exit;

if( $p > 1 ) $p = 1;
if( $p < 0 ) $p = 0;

$rect = array( 42, 14, 164, 5 );
$im2 = imagecreatefrompng( dirname( __FILE__ ).'/images/thermo.png' );
$im = imagecreate( imagesx( $im2 ), 34 );

// Copy thermometer:
imagepalettecopy( $im, $im2 );
$white = imagecolorallocate( $im, 0xFF, 0xFF, 0xFF );
imagefill( $im, 0, 0, $white );
imagecopy( $im, $im2, 0, 0, 0, 0, imagesx( $im2 ), imagesy( $im2 ) );
imagedestroy( $im2 );

// Extend the red bar of the thermostat:
$bar_width = (int)($p*$rect[2]);
if( $bar_width > 0 )
    imagecopyresized( $im, $im, $rect[0], $rect[1], $rect[0]-1, $rect[1], $bar_width, $rect[3], 2, $rect[3] );

// Add current sum below:
$font = 2;
$black = imagecolorallocate( $im, 0x00, 0x00, 0x00 );
$red = imagecolorallocate( $im, 0xCC, 0x00, 0x00 );
$blue = imagecolorallocate( $im, 0x00, 0x00, 0xCC );
$posY = 23;

$color = $black;
if( $p == 0 )
    $color = $blue;
if( $p == 1 )
    $color = $red;

// Add percent to the left:
$txt = number_format( $p*100.0, 2 ).'%';
$posX = $rect[0];
imagestring( $im, $font, $posX, $posY, $txt, $color );

// Add total possible to the right:
$txt = $currency.' '.format_money( $total );
$txt_width = imagefontwidth( $font ) * strlen ( $txt );
$posX = $rect[0] + $rect[2] - $txt_width;
imagestring( $im, $font, $posX, $posY, $txt, $color );

header( 'Content-Type: image/gif' );
imagegif( $im );

?>
