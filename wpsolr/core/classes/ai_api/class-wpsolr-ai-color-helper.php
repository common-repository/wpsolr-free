<?php

namespace wpsolr\core\classes\ai_api;


/**
 * RGB-to-HSL Converter
 * https://gist.github.com/brandonheyer/5254516
 * https://www.w3schools.com/colors/colors_rgb.asp
 */
class WPSOLR_AI_Color_Helper {

	static function rgb_to_hsl( $r, $g, $b ) {

		$r /= 255;
		$g /= 255;
		$b /= 255;

		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );

		$h = $s = 0; // achromatic
		$l = ( $max + $min ) / 2;
		$d = $max - $min;

		if ( ! empty( $d ) ) {

			$s = $d / ( 1 - abs( 2 * $l - 1 ) );

			switch ( $max ) {
				case $r:
					$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
					if ( $b > $g ) {
						$h += 360;
					}
					break;

				case $g:
					$h = 60 * ( ( $b - $r ) / $d + 2 );
					break;

				case $b:
					$h = 60 * ( ( $r - $g ) / $d + 4 );
					break;
			}
		}

		return [ round( $h, 2 ), round( $s, 2 ), round( $l, 2 ) ];
	}
}
