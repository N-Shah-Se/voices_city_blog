<?php
/**
 * My Library Shortcode
 *
 * @package 	Ocean_Extra
 * @category 	Core
 * @author 		OceanWP
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'OceanWP_Library_Shortcode' ) ) {

	class OceanWP_Library_Shortcode {

		/**
		 * Start things up
		 */
		public function __construct() {
			add_shortcode( 'oceanwp_library', array( $this, 'library_shortcode' ) );
		}

		/**
		 * Registers the function as a shortcode
		 */
		public function library_shortcode( $atts, $content = null ) {

			// Attributes
			$atts = shortcode_atts( array(
				'id' => '',
			), $atts, 'oceanwp_library' );

			ob_start();

			if ( $atts[ 'id' ] ) {

				// Check if the template is created with Elementor
				$elementor  = get_post_meta( $atts[ 'id' ], '_elementor_edit_mode', true );

			    // If Elementor
			    if ( class_exists( 'Elementor\Plugin' ) && $elementor ) {

			        echo Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $atts[ 'id' ] );

			    }

			    // If Beaver Builder
			    else if ( class_exists( 'FLBuilder' ) && ! empty( $atts[ 'id' ] ) ) {

			        echo do_shortcode( '[fl_builder_insert_layout id="' . $atts[ 'id' ] . '"]' );

			    }

			    // Else
			    else {

			    	// Get template content
			    	$content = $atts[ 'id' ];

					// Display template content.
					if ( function_exists( 'ocean_do_template_content' ) ) {
						echo ocean_do_template_content( $content );
					}

			    }
			}

			return ob_get_clean();

		}

	}

}
new OceanWP_Library_Shortcode();