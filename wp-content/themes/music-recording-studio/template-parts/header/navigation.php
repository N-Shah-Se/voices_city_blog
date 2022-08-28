<?php
/**
 * The template part for header
 *
 * @package Music Recording Studio 
 * @subpackage music-recording-studio
 * @since music-recording-studio 1.0
 */
?>

<div id="header" class="text-end">
  <?php if(has_nav_menu('primary')){ ?>
    <div class="toggle-nav mobile-menu text-end">
      <button role="tab" onclick="music_recording_studio_menu_open_nav()" class="responsivetoggle"><i class="<?php echo esc_attr(get_theme_mod('music_recording_studio_res_open_menu_icon','fas fa-bars')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Open Button','music-recording-studio'); ?></span></button>
    </div>
  <?php } ?>
  <div id="mySidenav" class="nav sidenav">
    <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Top Menu', 'music-recording-studio' ); ?>">
      <?php if(has_nav_menu('primary')){
        wp_nav_menu( array( 
          'theme_location' => 'primary',
          'container_class' => 'main-menu clearfix' ,
          'menu_class' => 'clearfix',
          'items_wrap' => '<ul id="%1$s" class="%2$s mobile_nav">%3$s</ul>',
          'fallback_cb' => 'wp_page_menu',
        ) );
      } ?>
      <a href="javascript:void(0)" class="closebtn mobile-menu" onclick="music_recording_studio_menu_close_nav()"><i class="<?php echo esc_attr(get_theme_mod('music_recording_studio_res_menu_close_icon','fas fa-times')); ?>"></i><span class="screen-reader-text"><?php esc_html_e('Close Button','music-recording-studio'); ?></span></a>
    </nav>
  </div>
</div>