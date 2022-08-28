<?php
//about theme info
add_action( 'admin_menu', 'music_recording_studio_gettingstarted' );
function music_recording_studio_gettingstarted() {
	add_theme_page( esc_html__('About Music Recording Studio', 'music-recording-studio'), esc_html__('About Music Recording Studio', 'music-recording-studio'), 'edit_theme_options', 'music_recording_studio_guide', 'music_recording_studio_mostrar_guide');
}

// Add a Custom CSS file to WP Admin Area
function music_recording_studio_admin_theme_style() {
	wp_enqueue_style('music-recording-studio-custom-admin-style', esc_url(get_template_directory_uri()) . '/inc/getstart/getstart.css');
	wp_enqueue_script('music-recording-studio-tabs', esc_url(get_template_directory_uri()) . '/inc/getstart/js/tab.js');
}
add_action('admin_enqueue_scripts', 'music_recording_studio_admin_theme_style');

//guidline for about theme
function music_recording_studio_mostrar_guide() { 
	//custom function about theme customizer
	$music_recording_studio_return = add_query_arg( array()) ;
	$music_recording_studio_theme = wp_get_theme( 'music-recording-studio' );
?>

<div class="wrapper-info">
    <div class="col-left">
    	<h2><?php esc_html_e( 'Welcome to Music Recording Studio', 'music-recording-studio' ); ?> <span class="version"><?php esc_html_e( 'Version', 'music-recording-studio' ); ?>: <?php echo esc_html($music_recording_studio_theme['Version']);?></span></h2>
    	<p><?php esc_html_e('All our WordPress themes are modern, minimalist, 100% responsive, seo-friendly,feature-rich, and multipurpose that best suit designers, bloggers and other professionals who are working in the creative fields.','music-recording-studio'); ?></p>
    </div>
    <div class="tab-sec">
    	<div class="tab">
			<button class="tablinks" onclick="music_recording_studio_open_tab(event, 'lite_theme')"><?php esc_html_e( 'Setup With Customizer', 'music-recording-studio' ); ?></button>
			<button class="tablinks" onclick="music_recording_studio_open_tab(event, 'gutenberg_editor')"><?php esc_html_e( 'Setup With Gutunberg Block', 'music-recording-studio' ); ?></button>
		</div>

		<?php
			$music_recording_studio_plugin_custom_css = '';
			if(class_exists('Ibtana_Visual_Editor_Menu_Class')){
				$music_recording_studio_plugin_custom_css ='display: block';
			}
		?>
		<div id="lite_theme" class="tabcontent open">
			<?php if(!class_exists('Ibtana_Visual_Editor_Menu_Class')){ 
				$plugin_ins = Music_Recording_Studio_Plugin_Activation_Settings::get_instance();
				$music_recording_studio_actions = $plugin_ins->recommended_actions;
				?>
				<div class="music-recording-studio-recommended-plugins">
				    <div class="music-recording-studio-action-list">
				        <?php if ($music_recording_studio_actions): foreach ($music_recording_studio_actions as $key => $music_recording_studio_actionValue): ?>
				                <div class="music-recording-studio-action" id="<?php echo esc_attr($music_recording_studio_actionValue['id']);?>">
			                        <div class="action-inner">
			                            <h3 class="action-title"><?php echo esc_html($music_recording_studio_actionValue['title']); ?></h3>
			                            <div class="action-desc"><?php echo esc_html($music_recording_studio_actionValue['desc']); ?></div>
			                            <?php echo wp_kses_post($music_recording_studio_actionValue['link']); ?>
			                            <a class="ibtana-skip-btn" get-start-tab-id="lite-theme-tab" href="javascript:void(0);"><?php esc_html_e('Skip','music-recording-studio'); ?></a>
			                        </div>
				                </div>
				            <?php endforeach;
				        endif; ?>
				    </div>
				</div>
			<?php } ?>
			<div class="lite-theme-tab" style="<?php echo esc_attr($music_recording_studio_plugin_custom_css); ?>">
				<h3><?php esc_html_e( 'Lite Theme Information', 'music-recording-studio' ); ?></h3>
				<hr class="h3hr">
				<p><?php esc_html_e('With Music Recording Studio you can showcase your artistic skills and exhibit your work in a compelling manner to your fans. Without a question, this is the most functional, competent, mobile-friendly, and visually attractive WordPress theme. This WP Theme works well with websites that promote albums, artists, audio companies, DJ artists, label music,  popular music band, music store, festival, and many more websites that deal with the music business. This multipurpose theme is designed on a solid basis, enabling one to build a fully functional and feature-rich website. The theme is completely customizable, SEO-friendly, responsive, interactive, and has a quicker website load time. The theme is well-designed and adaptable, and it performs admirably. It features a CTA button and tidy code ensuring that you dont face any bug-related issues. Social networking icons have been incorporated to ensure that your fans do not miss out on your new music launch or crucial updates. Create an attractive aura for your audience with plenty of customization settings. There is also a testimonial feature in this WordPress theme. The high-quality banner animation image is engaging and will lure your fans the moment they visit your homepage.','music-recording-studio'); ?></p>
			  	<div class="col-left-inner">
			  		<h4><?php esc_html_e( 'Theme Documentation', 'music-recording-studio' ); ?></h4>
					<p><?php esc_html_e( 'If you need any assistance regarding setting up and configuring the Theme, our documentation is there.', 'music-recording-studio' ); ?></p>
					<div class="info-link">
						<a href="<?php echo esc_url( MUSIC_RECORDING_STUDIO_FREE_THEME_DOC ); ?>" target="_blank"> <?php esc_html_e( 'Documentation', 'music-recording-studio' ); ?></a>
					</div>
					<hr>
					<h4><?php esc_html_e('Theme Customizer', 'music-recording-studio'); ?></h4>
					<p> <?php esc_html_e('To begin customizing your website, start by clicking "Customize".', 'music-recording-studio'); ?></p>
					<div class="info-link">
						<a target="_blank" href="<?php echo esc_url( admin_url('customize.php') ); ?>"><?php esc_html_e('Customizing', 'music-recording-studio'); ?></a>
					</div>
					<hr>
					<h4><?php esc_html_e('Having Trouble, Need Support?', 'music-recording-studio'); ?></h4>
					<p> <?php esc_html_e('Our dedicated team is well prepared to help you out in case of queries and doubts regarding our theme.', 'music-recording-studio'); ?></p>
					<div class="info-link">
						<a href="<?php echo esc_url( MUSIC_RECORDING_STUDIO_SUPPORT ); ?>" target="_blank"><?php esc_html_e('Support Forum', 'music-recording-studio'); ?></a>
					</div>
					<hr>
					<h4><?php esc_html_e('Reviews & Testimonials', 'music-recording-studio'); ?></h4>
					<p> <?php esc_html_e('All the features and aspects of this WordPress Theme are phenomenal. I\'d recommend this theme to all.', 'music-recording-studio'); ?></p>
					<div class="info-link">
						<a href="<?php echo esc_url( MUSIC_RECORDING_STUDIO_REVIEW ); ?>" target="_blank"><?php esc_html_e('Reviews', 'music-recording-studio'); ?></a>
					</div>

					<div class="link-customizer">
						<h3><?php esc_html_e( 'Link to customizer', 'music-recording-studio' ); ?></h3>
						<hr class="h3hr">
						<div class="first-row">
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','music-recording-studio'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','music-recording-studio'); ?></a>
								</div>
							</div>

							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-slides"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_slidersettings') ); ?>" target="_blank"><?php esc_html_e('Slider Settings','music-recording-studio'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-category"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_service_section') ); ?>" target="_blank"><?php esc_html_e('Service Section','music-recording-studio'); ?></a>
								</div>
							</div>
						
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','music-recording-studio'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','music-recording-studio'); ?></a>
								</div>
							</div>
							
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','music-recording-studio'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','music-recording-studio'); ?></a>
								</div>
							</div>
						</div>
					</div>
			  	</div>
				<div class="col-right-inner">
					<h3 class="page-template"><?php esc_html_e('How to set up Home Page Template','music-recording-studio'); ?></h3>
				  	<hr class="h3hr">
					<p><?php esc_html_e('Follow these instructions to setup Home page.','music-recording-studio'); ?></p>
                  	<p><span class="strong"><?php esc_html_e('1. Create a new page :','music-recording-studio'); ?></span><?php esc_html_e(' Go to ','music-recording-studio'); ?>
					  	<b><?php esc_html_e(' Dashboard >> Pages >> Add New Page','music-recording-studio'); ?></b></p>
                  	<p><?php esc_html_e('Name it as "Home" then select the template "Custom Home Page".','music-recording-studio'); ?></p>
                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/home-page-template.png" alt="" />
                  	<p><span class="strong"><?php esc_html_e('2. Set the front page:','music-recording-studio'); ?></span><?php esc_html_e(' Go to ','music-recording-studio'); ?>
					  	<b><?php esc_html_e(' Settings >> Reading ','music-recording-studio'); ?></b></p>
				  	<p><?php esc_html_e('Select the option of Static Page, now select the page you created to be the homepage, while another page to be your default page.','music-recording-studio'); ?></p>
                  	<img src="<?php echo esc_url(get_template_directory_uri()); ?>/inc/getstart/images/set-front-page.png" alt="" />
                  	<p><?php esc_html_e(' Once you are done with setup, then follow the','music-recording-studio'); ?> <a class="doc-links" href="<?php echo esc_url( MUSIC_RECORDING_STUDIO_FREE_THEME_DOC ); ?>" target="_blank"><?php esc_html_e('Documentation','music-recording-studio'); ?></a></p>
			  	</div>
			</div>
		</div>
		
		<div id="gutenberg_editor" class="tabcontent">
			<?php if(!class_exists('Ibtana_Visual_Editor_Menu_Class')){ 
			$plugin_ins = Music_Recording_Studio_Plugin_Activation_Settings::get_instance();
			$music_recording_studio_actions = $plugin_ins->recommended_actions;
			?>
				<div class="music-recording-studio-recommended-plugins">
				    <div class="music-recording-studio-action-list">
				        <?php if ($music_recording_studio_actions): foreach ($music_recording_studio_actions as $key => $music_recording_studio_actionValue): ?>
				                <div class="music-recording-studio-action" id="<?php echo esc_attr($music_recording_studio_actionValue['id']);?>">
			                        <div class="action-inner plugin-activation-redirect">
			                            <h3 class="action-title"><?php echo esc_html($music_recording_studio_actionValue['title']); ?></h3>
			                            <div class="action-desc"><?php echo esc_html($music_recording_studio_actionValue['desc']); ?></div>
			                            <?php echo wp_kses_post($music_recording_studio_actionValue['link']); ?>
			                        </div>
				                </div>
				            <?php endforeach;
				        endif; ?>
				    </div>
				</div>
			<?php }else{ ?>
				<h3><?php esc_html_e( 'Gutunberg Blocks', 'music-recording-studio' ); ?></h3>
				<hr class="h3hr">
				<div class="music-recording-studio-pattern-page">
			    	<a href="<?php echo esc_url( admin_url( 'admin.php?page=ibtana-visual-editor-templates' ) ); ?>" class="vw-pattern-page-btn ibtana-dashboard-page-btn button-primary button"><?php esc_html_e('Ibtana Settings','music-recording-studio'); ?></a>
			    </div>

			    <div class="link-customizer-with-guternberg-ibtana">
	              	<div class="link-customizer-with-block-pattern">
						<h3><?php esc_html_e( 'Link to customizer', 'music-recording-studio' ); ?></h3>
						<hr class="h3hr">
						<div class="first-row">
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-buddicons-buddypress-logo"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[control]=custom_logo') ); ?>" target="_blank"><?php esc_html_e('Upload your logo','music-recording-studio'); ?></a>
								</div>
								<div class="row-box2">
									<span class="dashicons dashicons-format-gallery"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_post_settings') ); ?>" target="_blank"><?php esc_html_e('Post settings','music-recording-studio'); ?></a>
								</div>
							</div>
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-menu"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=nav_menus') ); ?>" target="_blank"><?php esc_html_e('Menus','music-recording-studio'); ?></a>
								</div>
								
								<div class="row-box2">
									<span class="dashicons dashicons-text-page"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_footer') ); ?>" target="_blank"><?php esc_html_e('Footer Text','music-recording-studio'); ?></a>
								</div>
							</div>
							
							<div class="row-box">
								<div class="row-box1">
									<span class="dashicons dashicons-admin-generic"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[section]=music_recording_studio_left_right') ); ?>" target="_blank"><?php esc_html_e('General Settings','music-recording-studio'); ?></a>
								</div>
								 <div class="row-box2">
									<span class="dashicons dashicons-screenoptions"></span><a href="<?php echo esc_url( admin_url('customize.php?autofocus[panel]=widgets') ); ?>" target="_blank"><?php esc_html_e('Footer Widget','music-recording-studio'); ?></a>
								</div> 
							</div>
						</div>
					</div>	
				</div>
			<?php } ?>
		</div>

	</div>
</div>

<?php } ?>