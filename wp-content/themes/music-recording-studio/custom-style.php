<?php

	$music_recording_studio_custom_css= "";

	/*----------------------First highlight color-------------------*/

	$music_recording_studio_first_color = get_theme_mod('music_recording_studio_first_color');

	if($music_recording_studio_first_color != false){
		$music_recording_studio_custom_css .='.social-icons .widget p.mb-0 a:hover, #slider .read-btn a, .view-all-btn a,.more-btn a,#comments input[type="submit"],#comments a.comment-reply-link,input[type="submit"],.woocommerce #respond input#submit, .woocommerce a.button, .woocommerce button.button, .woocommerce input.button,.woocommerce #respond input#submit.alt, .woocommerce a.button.alt, .woocommerce button.button.alt, .woocommerce input.button.alt,nav.woocommerce-MyAccount-navigation ul li,.pro-button a, .woocommerce a.added_to_cart.wc-forward, #service-section strong, #footer .wp-block-search .wp-block-search__button, #sidebar .wp-block-search .wp-block-search__button, #sidebar h3, .woocommerce span.onsale{';
			$music_recording_studio_custom_css .='background-color: '.esc_attr($music_recording_studio_first_color).';';
		$music_recording_studio_custom_css .='}';
	}

	if($music_recording_studio_first_color != false){
		$music_recording_studio_custom_css .='a, p.site-title a, .logo h1 a, p.site-description, .service-box:hover .read-btn a, .copyright a:hover, .post-main-box:hover h2 a, #footer .textwidget a,#footer li a:hover,.post-main-box:hover h3 a,#sidebar ul li a:hover,.post-navigation a:hover .post-title, .post-navigation a:focus .post-title,.post-navigation a:hover,.post-navigation a:focus{';
			$music_recording_studio_custom_css .='color: '.esc_attr($music_recording_studio_first_color).';';
		$music_recording_studio_custom_css .='}';
	}

	/*----------------------Second highlight color-------------------*/

	$music_recording_studio_second_color = get_theme_mod('music_recording_studio_second_color');

	if($music_recording_studio_second_color != false){
		$music_recording_studio_custom_css .='.logo-bg, .more-btn a:hover,input[type="submit"]:hover,#comments input[type="submit"]:hover,#comments a.comment-reply-link:hover,.pagination .current,.pagination a:hover,#footer .tagcloud a:hover,#sidebar .tagcloud a:hover,.woocommerce #respond input#submit:hover, .woocommerce a.button:hover, .woocommerce button.button:hover, .woocommerce input.button:hover,.woocommerce #respond input#submit.alt:hover, .woocommerce a.button.alt:hover, .woocommerce button.button.alt:hover, .woocommerce input.button.alt:hover,.widget_product_search button:hover,nav.woocommerce-MyAccount-navigation ul li:hover, .social-icons .widget p.mb-0 a, .appointment-btn, .main-navigation ul.sub-menu>li>a:before, #slider .read-btn a:hover, .quoute-text, .view-all-btn a:hover, .more-btn a:hover, #comments input[type="submit"]:hover,#comments a.comment-reply-link:hover,.woocommerce #respond input#submit:hover, .woocommerce a.button:hover, .woocommerce button.button:hover, .woocommerce input.button:hover,.woocommerce #respond input#submit.alt:hover, .woocommerce a.button.alt:hover, .woocommerce button.button.alt:hover, .woocommerce input.button.alt:hover,.pro-button a:hover, #preloader, #footer-2, .scrollup i, .pagination span, .pagination a, .widget_product_search button, .toggle-nav button{';
			$music_recording_studio_custom_css .='background-color: '.esc_attr($music_recording_studio_second_color).';';
		$music_recording_studio_custom_css .='}';
	}

	if($music_recording_studio_second_color != false){
		$music_recording_studio_custom_css .='.social-icons .widget p.mb-0 a:hover, #slider .carousel-control-next i, #slider .carousel-control-prev i, #slider .inner_carousel h1 a, .post-main-box h2 a,.post-main-box:hover .post-info span a, .single-post .post-info:hover a, .middle-bar h6, .main-navigation a:hover{';
			$music_recording_studio_custom_css .='color: '.esc_attr($music_recording_studio_second_color).';';
		$music_recording_studio_custom_css .='}';
	}

	if($music_recording_studio_second_color != false){
		$music_recording_studio_custom_css .='#slider .carousel-control-next i, #slider .carousel-control-prev i{';
			$music_recording_studio_custom_css .='border-color: '.esc_attr($music_recording_studio_second_color).';';
		$music_recording_studio_custom_css .='}';
	}

	if($music_recording_studio_second_color != false){
		$music_recording_studio_custom_css .='.home-page-header{';
			$music_recording_studio_custom_css .='border-bottom-color: '.esc_attr($music_recording_studio_second_color).';';
		$music_recording_studio_custom_css .='}';
	}

	/*---------------------------Width Layout -------------------*/

	$music_recording_studio_theme_lay = get_theme_mod( 'music_recording_studio_width_option','Full Width');
    if($music_recording_studio_theme_lay == 'Boxed'){
		$music_recording_studio_custom_css .='body{';
			$music_recording_studio_custom_css .='max-width: 1140px; width: 100%; padding-right: 15px; padding-left: 15px; margin-right: auto; margin-left: auto;';
		$music_recording_studio_custom_css .='}';
	}else if($music_recording_studio_theme_lay == 'Wide Width'){
		$music_recording_studio_custom_css .='body{';
			$music_recording_studio_custom_css .='width: 100%;padding-right: 15px;padding-left: 15px;margin-right: auto;margin-left: auto;';
		$music_recording_studio_custom_css .='}';
	}else if($music_recording_studio_theme_lay == 'Full Width'){
		$music_recording_studio_custom_css .='body{';
			$music_recording_studio_custom_css .='max-width: 100%;';
		$music_recording_studio_custom_css .='}';
	}

	/*----------------Responsive Media -----------------------*/

	$music_recording_studio_resp_slider = get_theme_mod( 'music_recording_studio_resp_slider_hide_show',false);
	if($music_recording_studio_resp_slider == true && get_theme_mod( 'music_recording_studio_slider_hide_show', false) == false){
    	$music_recording_studio_custom_css .='#slider{';
			$music_recording_studio_custom_css .='display:none;';
		$music_recording_studio_custom_css .='} ';
	}
    if($music_recording_studio_resp_slider == true){
    	$music_recording_studio_custom_css .='@media screen and (max-width:575px) {';
		$music_recording_studio_custom_css .='#slider{';
			$music_recording_studio_custom_css .='display:block;';
		$music_recording_studio_custom_css .='} }';
	}else if($music_recording_studio_resp_slider == false){
		$music_recording_studio_custom_css .='@media screen and (max-width:575px) {';
		$music_recording_studio_custom_css .='#slider{';
			$music_recording_studio_custom_css .='display:none;';
		$music_recording_studio_custom_css .='} }';
		$music_recording_studio_custom_css .='@media screen and (max-width:575px){';
		$music_recording_studio_custom_css .='.page-template-custom-home-page.admin-bar .homepageheader{';
			$music_recording_studio_custom_css .='margin-top: 45px;';
		$music_recording_studio_custom_css .='} }';
	}

	$music_recording_studio_resp_sidebar = get_theme_mod( 'music_recording_studio_sidebar_hide_show',true);
    if($music_recording_studio_resp_sidebar == true){
    	$music_recording_studio_custom_css .='@media screen and (max-width:575px) {';
		$music_recording_studio_custom_css .='#sidebar{';
			$music_recording_studio_custom_css .='display:block;';
		$music_recording_studio_custom_css .='} }';
	}else if($music_recording_studio_resp_sidebar == false){
		$music_recording_studio_custom_css .='@media screen and (max-width:575px) {';
		$music_recording_studio_custom_css .='#sidebar{';
			$music_recording_studio_custom_css .='display:none;';
		$music_recording_studio_custom_css .='} }';
	}

	$music_recording_studio_resp_scroll_top = get_theme_mod( 'music_recording_studio_resp_scroll_top_hide_show',true);
	if($music_recording_studio_resp_scroll_top == true && get_theme_mod( 'music_recording_studio_hide_show_scroll',true) == false){
    	$music_recording_studio_custom_css .='.scrollup i{';
			$music_recording_studio_custom_css .='visibility:hidden !important;';
		$music_recording_studio_custom_css .='} ';
	}
    if($music_recording_studio_resp_scroll_top == true){
    	$music_recording_studio_custom_css .='@media screen and (max-width:575px) {';
		$music_recording_studio_custom_css .='.scrollup i{';
			$music_recording_studio_custom_css .='visibility:visible !important;';
		$music_recording_studio_custom_css .='} }';
	}else if($music_recording_studio_resp_scroll_top == false){
		$music_recording_studio_custom_css .='@media screen and (max-width:575px){';
		$music_recording_studio_custom_css .='.scrollup i{';
			$music_recording_studio_custom_css .='visibility:hidden !important;';
		$music_recording_studio_custom_css .='} }';
	}
	
	/*---------------- Button Settings ------------------*/

	$music_recording_studio_button_border_radius = get_theme_mod('music_recording_studio_button_border_radius');
	if($music_recording_studio_button_border_radius != false){
		$music_recording_studio_custom_css .='.post-main-box .more-btn a{';
			$music_recording_studio_custom_css .='border-radius: '.esc_attr($music_recording_studio_button_border_radius).'px;';
		$music_recording_studio_custom_css .='}';
	}

	/*-------------- Copyright Alignment ----------------*/

	$music_recording_studio_copyright_alingment = get_theme_mod('music_recording_studio_copyright_alingment');
	if($music_recording_studio_copyright_alingment != false){
		$music_recording_studio_custom_css .='.copyright p{';
			$music_recording_studio_custom_css .='text-align: '.esc_attr($music_recording_studio_copyright_alingment).';';
		$music_recording_studio_custom_css .='}';
	}

	/*------------------ Logo  -------------------*/

	// Site title Font Size
	$music_recording_studio_site_title_font_size = get_theme_mod('music_recording_studio_site_title_font_size');
	if($music_recording_studio_site_title_font_size != false){
		$music_recording_studio_custom_css .='.logo h1, .logo p.site-title{';
			$music_recording_studio_custom_css .='font-size: '.esc_attr($music_recording_studio_site_title_font_size).';';
		$music_recording_studio_custom_css .='}';
	}

	// Site tagline Font Size
	$music_recording_studio_site_tagline_font_size = get_theme_mod('music_recording_studio_site_tagline_font_size');
	if($music_recording_studio_site_tagline_font_size != false){
		$music_recording_studio_custom_css .='.logo p.site-description{';
			$music_recording_studio_custom_css .='font-size: '.esc_attr($music_recording_studio_site_tagline_font_size).';';
		$music_recording_studio_custom_css .='}';
	}

	/*------------------ Preloader Background Color  -------------------*/

	$music_recording_studio_preloader_bg_color = get_theme_mod('music_recording_studio_preloader_bg_color');
	if($music_recording_studio_preloader_bg_color != false){
		$music_recording_studio_custom_css .='#preloader{';
			$music_recording_studio_custom_css .='background-color: '.esc_attr($music_recording_studio_preloader_bg_color).';';
		$music_recording_studio_custom_css .='}';
	}

	$music_recording_studio_preloader_border_color = get_theme_mod('music_recording_studio_preloader_border_color');
	if($music_recording_studio_preloader_border_color != false){
		$music_recording_studio_custom_css .='.loader-line{';
			$music_recording_studio_custom_css .='border-color: '.esc_attr($music_recording_studio_preloader_border_color).'!important;';
		$music_recording_studio_custom_css .='}';
	}

	// Slider CSS
	if(get_theme_mod('music_recording_studio_slider_hide_show') == false){
		$music_recording_studio_custom_css .=' .page-template-custom-home-page .main-header{';
				$music_recording_studio_custom_css .=' position: static; border-bottom: 1px solid #DE3960;';
		$music_recording_studio_custom_css .='}';
		$music_recording_studio_custom_css .=' .page-template-custom-home-page p.site-title a, .page-template-custom-home-page .logo h1 a, .page-template-custom-home-page .logo p.site-description{';
				$music_recording_studio_custom_css .=' color: #000;';
		$music_recording_studio_custom_css .='}';
	}