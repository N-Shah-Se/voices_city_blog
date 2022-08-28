<?php
/**
 * Music Recording Studio Theme Customizer
 *
 * @package Music Recording Studio
 */

/**
 * Add postMessage support for site title and description for the Theme Customizer.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */

function music_recording_studio_custom_controls() {
	load_template( trailingslashit( get_template_directory() ) . '/inc/custom-controls.php' );
}
add_action( 'customize_register', 'music_recording_studio_custom_controls' );

function music_recording_studio_customize_register( $wp_customize ) {

	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage'; 
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial( 'blogname', array( 
		'selector' => '.logo .site-title a', 
	 	'render_callback' => 'music_recording_studio_Customize_partial_blogname',
	)); 

	$wp_customize->selective_refresh->add_partial( 'blogdescription', array( 
		'selector' => 'p.site-description', 
		'render_callback' => 'music_recording_studio_Customize_partial_blogdescription',
	));

	// add home page setting pannel
	$wp_customize->add_panel( 'music_recording_studio_panel_id', array(
		'capability' => 'edit_theme_options',
		'theme_supports' => '',
		'title' => esc_html__( 'VW Settings', 'music-recording-studio' ),
		'priority' => 10,
	));

	// Layout
	$wp_customize->add_section( 'music_recording_studio_left_right', array(
    	'title' => esc_html__( 'General Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_panel_id'
	) );

	$wp_customize->add_setting('music_recording_studio_width_option',array(
        'default' => 'Full Width',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control(new Music_Recording_Studio_Image_Radio_Control($wp_customize, 'music_recording_studio_width_option', array(
        'type' => 'select',
        'label' => esc_html__('Width Layouts','music-recording-studio'),
        'description' => esc_html__('Here you can change the width layout of Website.','music-recording-studio'),
        'section' => 'music_recording_studio_left_right',
        'choices' => array(
            'Full Width' => esc_url(get_template_directory_uri()).'/assets/images/full-width.png',
            'Wide Width' => esc_url(get_template_directory_uri()).'/assets/images/wide-width.png',
            'Boxed' => esc_url(get_template_directory_uri()).'/assets/images/boxed-width.png',
    ))));

	$wp_customize->add_setting('music_recording_studio_theme_options',array(
        'default' => 'Right Sidebar',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control('music_recording_studio_theme_options',array(
        'type' => 'select',
        'label' => esc_html__('Post Sidebar Layout','music-recording-studio'),
        'description' => esc_html__('Here you can change the sidebar layout for posts. ','music-recording-studio'),
        'section' => 'music_recording_studio_left_right',
        'choices' => array(
            'Left Sidebar' => esc_html__('Left Sidebar','music-recording-studio'),
            'Right Sidebar' => esc_html__('Right Sidebar','music-recording-studio'),
            'One Column' => esc_html__('One Column','music-recording-studio'),
            'Grid Layout' => esc_html__('Grid Layout','music-recording-studio')
        ),
	) );

	$wp_customize->add_setting('music_recording_studio_page_layout',array(
        'default' => 'One_Column',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control('music_recording_studio_page_layout',array(
        'type' => 'select',
        'label' => esc_html__('Page Sidebar Layout','music-recording-studio'),
        'description' => esc_html__('Here you can change the sidebar layout for pages. ','music-recording-studio'),
        'section' => 'music_recording_studio_left_right',
        'choices' => array(
            'Left_Sidebar' => esc_html__('Left Sidebar','music-recording-studio'),
            'Right_Sidebar' => esc_html__('Right Sidebar','music-recording-studio'),
            'One_Column' => esc_html__('One Column','music-recording-studio')
        ),
	) );

	// Selective Refresh
	$wp_customize->selective_refresh->add_partial( 'music_recording_studio_woocommerce_shop_page_sidebar', array( 'selector' => '.post-type-archive-product #sidebar', 
		'render_callback' => 'music_recording_studio_customize_partial_music_recording_studio_woocommerce_shop_page_sidebar', ) );

    // Woocommerce Shop Page Sidebar
	$wp_customize->add_setting( 'music_recording_studio_woocommerce_shop_page_sidebar',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_woocommerce_shop_page_sidebar',array(
		'label' => esc_html__( 'Shop Page Sidebar','music-recording-studio' ),
		'section' => 'music_recording_studio_left_right'
    )));

    // Selective Refresh
	$wp_customize->selective_refresh->add_partial( 'music_recording_studio_woocommerce_single_product_page_sidebar', array( 'selector' => '.single-product #sidebar', 
		'render_callback' => 'music_recording_studio_customize_partial_music_recording_studio_woocommerce_single_product_page_sidebar', ) );

    //Woocommerce Single Product page Sidebar
	$wp_customize->add_setting( 'music_recording_studio_woocommerce_single_product_page_sidebar',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_woocommerce_single_product_page_sidebar',array(
		'label' => esc_html__( 'Single Product Sidebar','music-recording-studio' ),
		'section' => 'music_recording_studio_left_right'
    )));

    // Pre-Loader
	$wp_customize->add_setting( 'music_recording_studio_loader_enable',array(
        'default' => 0,
        'transport' => 'refresh',
        'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_loader_enable',array(
        'label' => esc_html__( 'Pre-Loader','music-recording-studio' ),
        'section' => 'music_recording_studio_left_right'
    )));

	$wp_customize->add_setting('music_recording_studio_preloader_bg_color', array(
		'default'           => '#DE3960',
		'sanitize_callback' => 'sanitize_hex_color',
	));
	$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'music_recording_studio_preloader_bg_color', array(
		'label'    => __('Pre-Loader Background Color', 'music-recording-studio'),
		'section'  => 'music_recording_studio_left_right',
	)));

	$wp_customize->add_setting('music_recording_studio_preloader_border_color', array(
		'default'           => '#ffffff',
		'sanitize_callback' => 'sanitize_hex_color',
	));
	$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'music_recording_studio_preloader_border_color', array(
		'label'    => __('Pre-Loader Border Color', 'music-recording-studio'),
		'section'  => 'music_recording_studio_left_right',
	)));

	//Slider
	$wp_customize->add_section( 'music_recording_studio_slidersettings' , array(
    	'title'      => __( 'Slider Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_panel_id'
	) );

	$wp_customize->add_setting( 'music_recording_studio_slider_hide_show',array(
      'default' => 0,
      'transport' => 'refresh',
      'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ));  
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_slider_hide_show',array(
      'label' => esc_html__( 'Show / Hide Slider','music-recording-studio' ),
      'section' => 'music_recording_studio_slidersettings'
    )));

     //Selective Refresh
    $wp_customize->selective_refresh->add_partial('music_recording_studio_slider_hide_show',array(
		'selector'        => '.slider-btn a',
		'render_callback' => 'music_recording_studio_customize_partial_music_recording_studio_slider_hide_show',
	));

	for ( $count = 1; $count <= 4; $count++ ) {
		$wp_customize->add_setting( 'music_recording_studio_slider_page' . $count, array(
			'default'           => '',
			'sanitize_callback' => 'music_recording_studio_sanitize_dropdown_pages'
		) );
		$wp_customize->add_control( 'music_recording_studio_slider_page' . $count, array(
			'label'    => __( 'Select Slider Page', 'music-recording-studio' ),
			'description' => __('Slider image size (450 x 250)','music-recording-studio'),
			'section'  => 'music_recording_studio_slidersettings',
			'type'     => 'dropdown-pages'
		) );
	}

    //Slider excerpt
	$wp_customize->add_setting( 'music_recording_studio_slider_excerpt_number', array(
		'default'           => 15,
		'transport' 	    => 'refresh',
		'sanitize_callback' => 'music_recording_studio_sanitize_number_range'
	) );
	$wp_customize->add_control( 'music_recording_studio_slider_excerpt_number', array(
		'label'       => esc_html__( 'Slider Excerpt length','music-recording-studio' ),
		'section'     => 'music_recording_studio_slidersettings',
		'type'        => 'range',
		'settings'    => 'music_recording_studio_slider_excerpt_number',
		'input_attrs' => array(
			'step' => 5,
			'min'  => 0,
			'max'  => 50,
		),
	) );

	$wp_customize->add_setting( 'music_recording_studio_slider_speed', array(
		'default'  => 4000,
		'sanitize_callback'	=> 'sanitize_text_field'
	) );
	$wp_customize->add_control( 'music_recording_studio_slider_speed', array(
		'label' => esc_html__('Slider Transition Speed','music-recording-studio'),
		'section' => 'music_recording_studio_slidersettings',
		'type'  => 'text',
	) );

	// Services Section
	$wp_customize->add_section('music_recording_studio_service_section',array(
		'title'	=> __('Service Section','music-recording-studio'),
		'description' => __('Add section title and Select the Category to display for services.','music-recording-studio'),
		'panel' => 'music_recording_studio_panel_id',
	));

	$wp_customize->add_setting( 'music_recording_studio_section_small_title', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field'
	) );
	$wp_customize->add_control( 'music_recording_studio_section_small_title', array(
		'label'    => __( 'Add Section Small Title', 'music-recording-studio' ),
		'input_attrs' => array(
            'placeholder' => __( 'What we do', 'music-recording-studio' ),
        ),
		'section'  => 'music_recording_studio_service_section',
		'type'     => 'text'
	) );

	$wp_customize->add_setting( 'music_recording_studio_section_title', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field'
	) );
	$wp_customize->add_control( 'music_recording_studio_section_title', array(
		'label'    => __( 'Add Section Title', 'music-recording-studio' ),
		'input_attrs' => array(
            'placeholder' => __( 'Studio Services', 'music-recording-studio' ),
        ),
		'section'  => 'music_recording_studio_service_section',
		'type'     => 'text'
	) );

	$categories = get_categories();
		$cat_posts = array();
			$i = 0;
			$cat_posts[]='Select';
		foreach($categories as $category){
			if($i==0){
			$default = $category->slug;
			$i++;
		}
		$cat_posts[$category->slug] = $category->name;
	}

	$wp_customize->add_setting('music_recording_studio_service_category',array(
		'default'	=> 'select',
		'sanitize_callback' => 'music_recording_studio_sanitize_choices',
	));
	$wp_customize->add_control('music_recording_studio_service_category',array(
		'type'    => 'select',
		'choices' => $cat_posts,
		'label' => __('Select Service Category','music-recording-studio'),
		'section' => 'music_recording_studio_service_section',
	));

	//Blog Post
	$wp_customize->add_panel( 'music_recording_studio_blog_post_parent_panel', array(
		'title' => esc_html__( 'Blog Post Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_panel_id',
		'priority' => 20,
	));

	// Add example section and controls to the middle (second) panel
	$wp_customize->add_section( 'music_recording_studio_post_settings', array(
		'title' => esc_html__( 'Post Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_blog_post_parent_panel',
	));

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('music_recording_studio_toggle_postdate', array( 
		'selector' => '.post-main-box h2 a', 
		'render_callback' => 'music_recording_studio_Customize_partial_music_recording_studio_toggle_postdate', 
	));

	$wp_customize->add_setting( 'music_recording_studio_toggle_postdate',array(
        'default' => 1,
        'transport' => 'refresh',
        'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_toggle_postdate',array(
        'label' => esc_html__( 'Post Date','music-recording-studio' ),
        'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_toggle_author',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_toggle_author',array(
		'label' => esc_html__( 'Author','music-recording-studio' ),
		'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_toggle_comments',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_toggle_comments',array(
		'label' => esc_html__( 'Comments','music-recording-studio' ),
		'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_toggle_time',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_toggle_time',array(
		'label' => esc_html__( 'Time','music-recording-studio' ),
		'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_featured_image_hide_show',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
	));
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_featured_image_hide_show', array(
		'label' => esc_html__( 'Featured Image','music-recording-studio' ),
		'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_toggle_tags',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
	));
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_toggle_tags', array(
		'label' => esc_html__( 'Tags','music-recording-studio' ),
		'section' => 'music_recording_studio_post_settings'
    )));

    $wp_customize->add_setting( 'music_recording_studio_excerpt_number', array(
		'default'              => 30,
		'type'                 => 'theme_mod',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'music_recording_studio_sanitize_number_range',
		'sanitize_js_callback' => 'absint',
	) );
	$wp_customize->add_control( 'music_recording_studio_excerpt_number', array(
		'label'       => esc_html__( 'Excerpt length','music-recording-studio' ),
		'section'     => 'music_recording_studio_post_settings',
		'type'        => 'range',
		'settings'    => 'music_recording_studio_excerpt_number',
		'input_attrs' => array(
			'step'             => 5,
			'min'              => 0,
			'max'              => 50,
		),
	) );

	$wp_customize->add_setting('music_recording_studio_meta_field_separator',array(
		'default'=> '|',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('music_recording_studio_meta_field_separator',array(
		'label'	=> __('Add Meta Separator','music-recording-studio'),
		'description' => __('Add the seperator for meta box. Example: "|", "/", etc.','music-recording-studio'),
		'section'=> 'music_recording_studio_post_settings',
		'type'=> 'text'
	));

    $wp_customize->add_setting('music_recording_studio_excerpt_settings',array(
        'default' => 'Excerpt',
        'transport' => 'refresh',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control('music_recording_studio_excerpt_settings',array(
        'type' => 'select',
        'label' => esc_html__('Post Content','music-recording-studio'),
        'section' => 'music_recording_studio_post_settings',
        'choices' => array(
        	'Content' => esc_html__('Content','music-recording-studio'),
            'Excerpt' => esc_html__('Excerpt','music-recording-studio'),
            'No Content' => esc_html__('No Content','music-recording-studio')
        ),
	) );

    // Button Settings
	$wp_customize->add_section( 'music_recording_studio_button_settings', array(
		'title' => esc_html__( 'Button Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_blog_post_parent_panel',
	));

	$wp_customize->add_setting( 'music_recording_studio_button_border_radius', array(
		'default'              => 5,
		'type'                 => 'theme_mod',
		'transport' 		   => 'refresh',
		'sanitize_callback'    => 'music_recording_studio_sanitize_number_range',
		'sanitize_js_callback' => 'absint',
	) );
	$wp_customize->add_control( 'music_recording_studio_button_border_radius', array(
		'label'       => esc_html__( 'Button Border Radius','music-recording-studio' ),
		'section'     => 'music_recording_studio_button_settings',
		'type'        => 'range',
		'input_attrs' => array(
			'step'             => 1,
			'min'              => 1,
			'max'              => 50,
		),
	) );

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('music_recording_studio_button_text', array( 
		'selector' => '.post-main-box .more-btn a', 
		'render_callback' => 'music_recording_studio_Customize_partial_music_recording_studio_button_text', 
	));

    $wp_customize->add_setting('music_recording_studio_button_text',array(
		'default'=> esc_html__('Read More','music-recording-studio'),
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('music_recording_studio_button_text',array(
		'label'	=> esc_html__('Add Button Text','music-recording-studio'),
		'input_attrs' => array(
            'placeholder' => esc_html__( 'Read More', 'music-recording-studio' ),
        ),
		'section'=> 'music_recording_studio_button_settings',
		'type'=> 'text'
	));

	// Related Post Settings
	$wp_customize->add_section( 'music_recording_studio_related_posts_settings', array(
		'title' => esc_html__( 'Related Posts Settings', 'music-recording-studio' ),
		'panel' => 'music_recording_studio_blog_post_parent_panel',
	));

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('music_recording_studio_related_post_title', array( 
		'selector' => '.related-post h3', 
		'render_callback' => 'music_recording_studio_Customize_partial_music_recording_studio_related_post_title', 
	));

    $wp_customize->add_setting( 'music_recording_studio_related_post',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ) );
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_related_post',array(
		'label' => esc_html__( 'Related Post','music-recording-studio' ),
		'section' => 'music_recording_studio_related_posts_settings'
    )));

    $wp_customize->add_setting('music_recording_studio_related_post_title',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));
	$wp_customize->add_control('music_recording_studio_related_post_title',array(
		'label'	=> esc_html__('Add Related Post Title','music-recording-studio'),
		'input_attrs' => array(
            'placeholder' => esc_html__( 'Related Post', 'music-recording-studio' ),
        ),
		'section'=> 'music_recording_studio_related_posts_settings',
		'type'=> 'text'
	));

   	$wp_customize->add_setting('music_recording_studio_related_posts_count',array(
		'default'=> 3,
		'sanitize_callback'	=> 'music_recording_studio_sanitize_number_absint'
	));
	$wp_customize->add_control('music_recording_studio_related_posts_count',array(
		'label'	=> esc_html__('Add Related Post Count','music-recording-studio'),
		'input_attrs' => array(
            'placeholder' => esc_html__( '3', 'music-recording-studio' ),
        ),
		'section'=> 'music_recording_studio_related_posts_settings',
		'type'=> 'number'
	));

	//Responsive Media Settings
	$wp_customize->add_section('music_recording_studio_responsive_media',array(
		'title'	=> esc_html__('Responsive Media','music-recording-studio'),
		'panel' => 'music_recording_studio_panel_id',
	));

    $wp_customize->add_setting( 'music_recording_studio_resp_slider_hide_show',array(
      	'default' => 0,
     	'transport' => 'refresh',
      	'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ));  
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_resp_slider_hide_show',array(
      	'label' => esc_html__( 'Show / Hide Slider','music-recording-studio' ),
      	'section' => 'music_recording_studio_responsive_media'
    )));

    $wp_customize->add_setting( 'music_recording_studio_sidebar_hide_show',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ));  
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_sidebar_hide_show',array(
      	'label' => esc_html__( 'Show / Hide Sidebar','music-recording-studio' ),
      	'section' => 'music_recording_studio_responsive_media'
    )));

    $wp_customize->add_setting( 'music_recording_studio_resp_scroll_top_hide_show',array(
		'default' => 1,
		'transport' => 'refresh',
		'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ));  
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_resp_scroll_top_hide_show',array(
      	'label' => esc_html__( 'Show / Hide Scroll To Top','music-recording-studio' ),
      	'section' => 'music_recording_studio_responsive_media'
    )));

	//Footer Text
	$wp_customize->add_section('music_recording_studio_footer',array(
		'title'	=> esc_html__('Footer Settings','music-recording-studio'),
		'panel' => 'music_recording_studio_panel_id',
	));	

	//Selective Refresh
	$wp_customize->selective_refresh->add_partial('music_recording_studio_footer_text', array( 
		'selector' => '.copyright p', 
		'render_callback' => 'music_recording_studio_Customize_partial_music_recording_studio_footer_text', 
	));
	
	$wp_customize->add_setting('music_recording_studio_footer_text',array(
		'default'=> '',
		'sanitize_callback'	=> 'sanitize_text_field'
	));	
	$wp_customize->add_control('music_recording_studio_footer_text',array(
		'label'	=> esc_html__('Copyright Text','music-recording-studio'),
		'input_attrs' => array(
            'placeholder' => esc_html__( 'Copyright 2021, .....', 'music-recording-studio' ),
        ),
		'section'=> 'music_recording_studio_footer',
		'type'=> 'text'
	));

	$wp_customize->add_setting('music_recording_studio_copyright_alingment',array(
        'default' => 'center',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control(new Music_Recording_Studio_Image_Radio_Control($wp_customize, 'music_recording_studio_copyright_alingment', array(
        'type' => 'select',
        'label' => esc_html__('Copyright Alignment','music-recording-studio'),
        'section' => 'music_recording_studio_footer',
        'settings' => 'music_recording_studio_copyright_alingment',
        'choices' => array(
            'left' => esc_url(get_template_directory_uri()).'/assets/images/copyright1.png',
            'center' => esc_url(get_template_directory_uri()).'/assets/images/copyright2.png',
            'right' => esc_url(get_template_directory_uri()).'/assets/images/copyright3.png'
    ))));

    $wp_customize->add_setting( 'music_recording_studio_hide_show_scroll',array(
    	'default' => 1,
      	'transport' => 'refresh',
      	'sanitize_callback' => 'music_recording_studio_switch_sanitization'
    ));  
    $wp_customize->add_control( new Music_Recording_Studio_Toggle_Switch_Custom_Control( $wp_customize, 'music_recording_studio_hide_show_scroll',array(
      	'label' => esc_html__( 'Show / Hide Scroll to Top','music-recording-studio' ),
      	'section' => 'music_recording_studio_footer'
    )));

    //Selective Refresh
	$wp_customize->selective_refresh->add_partial('music_recording_studio_scroll_to_top_icon', array( 
		'selector' => '.scrollup i', 
		'render_callback' => 'music_recording_studio_Customize_partial_music_recording_studio_scroll_to_top_icon', 
	));

    $wp_customize->add_setting('music_recording_studio_scroll_top_alignment',array(
        'default' => 'Right',
        'sanitize_callback' => 'music_recording_studio_sanitize_choices'
	));
	$wp_customize->add_control(new Music_Recording_Studio_Image_Radio_Control($wp_customize, 'music_recording_studio_scroll_top_alignment', array(
        'type' => 'select',
        'label' => esc_html__('Scroll To Top','music-recording-studio'),
        'section' => 'music_recording_studio_footer',
        'settings' => 'music_recording_studio_scroll_top_alignment',
        'choices' => array(
            'Left' => esc_url(get_template_directory_uri()).'/assets/images/layout1.png',
            'Center' => esc_url(get_template_directory_uri()).'/assets/images/layout2.png',
            'Right' => esc_url(get_template_directory_uri()).'/assets/images/layout3.png'
    ))));
}

add_action( 'customize_register', 'music_recording_studio_customize_register' );

load_template( trailingslashit( get_template_directory() ) . '/inc/logo/logo-resizer.php' );

/**
 * Singleton class for handling the theme's customizer integration.
 *
 * @since  1.0.0
 * @access public
 */
final class Music_Recording_Studio_Customize {

	/**
	 * Returns the instance.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return object
	 */
	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Sets up initial actions.
	 *
	 * @since  1.0.0
	 * @access private
	 * @return void
	 */
	private function setup_actions() {

		// Register panels, sections, settings, controls, and partials.
		add_action( 'customize_register', array( $this, 'sections' ) );

		// Register scripts and styles for the controls.
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_control_scripts' ), 0 );
	}

	/**
	 * Sets up the customizer sections.
	 *
	 * @since  1.0.0
	 * @access public
	 * @param  object  $manager
	 * @return void
	*/
	public function sections( $manager ) {

		// Load custom sections.
		load_template( trailingslashit( get_template_directory() ) . '/inc/section-pro.php' );

		// Register custom section types.
		$manager->register_section_type( 'Music_Recording_Studio_Customize_Section_Pro' );

		// Register sections.
		$manager->add_section( new Music_Recording_Studio_Customize_Section_Pro( $manager,'music_recording_studio_go_pro', array(
			'priority'   => 1,
			'title'    => esc_html__( 'RECORDING STUDIO PRO', 'music-recording-studio' ),
			'pro_text' => esc_html__( 'UPGRADE PRO', 'music-recording-studio' ),
			'pro_url'  => esc_url('https://www.vwthemes.com/themes/recording-studio-wordpress-theme/'),
		) )	);
	}

	/**
	 * Loads theme customizer CSS.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue_control_scripts() {

		wp_enqueue_script( 'music-recording-studio-customize-controls', trailingslashit( get_template_directory_uri() ) . '/assets/js/customize-controls.js', array( 'customize-controls' ) );

		wp_enqueue_style( 'music-recording-studio-customize-controls', trailingslashit( get_template_directory_uri() ) . '/assets/css/customize-controls.css' );
	}
}

// Doing this customizer thang!
Music_Recording_Studio_Customize::get_instance();