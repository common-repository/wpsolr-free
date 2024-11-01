<?php

namespace wpsolr\core\classes\ui\shortcode;

use wpsolr\core\classes\ui\WPSOLR_Query;
use wpsolr\core\classes\utilities\WPSOLR_Escape;

/**
 * Class WPSOLR_Shortcode
 */
class WPSOLR_Shortcode {

	const SHORTCODE_NAME = 'to be defined in children';

	/**
	 * Load all shorcode classes in this very directory.
	 */
	public static function Autoload() {


		// Loop on all widgets
		foreach (
			[
				WPSOLR_Shortcode_Facet::class,
				//WPSOLR_Shortcode_Sort::class,
			] as $shortcode_class_name
		) {

			add_shortcode( $shortcode_class_name::SHORTCODE_NAME, array(
				$shortcode_class_name,
				'get_html'
			) );
		}

		add_action( 'manage_posts_extra_tablenav', [ static::class, 'manage_posts_extra_tablenav' ], 10, 1 );
	}


	/**
	 * Add facets shortcode to admin search pages
	 *
	 * @param string $which
	 *
	 * @return void
	 */
	static function manage_posts_extra_tablenav( $which ): void {
		global $pagenow, $wp_query;

		if ( ( 'top' === $which ) && is_admin() && ( $pagenow == 'edit.php' ) &&
		     ( WPSOLR_Query::class === get_class( $wp_query ) )
		) {
			?>
            <style>
                .wpsolr_group_facets {
                    width: 100%;
                    float: left;
                    margin: 10px 0 10px 0;
                }

                <!--
                .Xwpsolr_group_facets ul {
                    width: 20%;
                }

                -->
            </style>
			<?php
			WPSOLR_Escape::echo_escaped( do_shortcode( '[wpsolr_facet]' ) );
		}
	}

}