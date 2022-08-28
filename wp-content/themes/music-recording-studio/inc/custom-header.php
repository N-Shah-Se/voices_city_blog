<?php
/**
 * @package Music Recording Studio
 * Setup the WordPress core custom header feature.
 *
 * @uses music_recording_studio_header_style()
*/
function music_recording_studio_custom_header_setup() {
	add_theme_support( 'custom-header', apply_filters( 'music_recording_studio_custom_header_args', array(
		'header-text' 			 =>	false,
		'width'                  => 1200,
		'height'                 => 80,
		'flex-width'    		 => true,
		'flex-height'    		 => true,
		'wp-head-callback'       => 'music_recording_studio_header_style',
	) ) );
}
add_action( 'after_setup_theme', 'music_recording_studio_custom_header_setup' );

if ( ! function_exists( 'music_recording_studio_header_style' ) ) :
/**
 * Styles the header image and text displayed on the blog
 *
 * @see music_recording_studio_custom_header_setup().
 */
add_action( 'wp_enqueue_scripts', 'music_recording_studio_header_style' );

function music_recording_studio_header_style() {
	if ( get_header_image() ) :
	$custom_css = "
        .main-header{
			background-image:url('".esc_url(get_header_image())."');
			background-position: center top;
		    background-size: 100% 100%;
		}";
	   	wp_add_inline_style( 'music-recording-studio-basic-style', $custom_css );
	endif;
}
endif;