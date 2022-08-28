<?php
/**
 * The template part for Middle Header
 *
 * @package Music Recording Studio
 * @subpackage music-recording-studio
 * @since music-recording-studio 1.0
 */
?>

<div class="main-header">
  <div class="container">
    <div class="row">
      <div class="col-lg-3 col-md-4 col-9">
        <div class="logo">
          <?php if ( has_custom_logo() ) : ?>
            <div class="site-logo"><?php the_custom_logo(); ?></div>
          <?php endif; ?>
          <?php $blog_info = get_bloginfo( 'name' ); ?>
            <?php if ( ! empty( $blog_info ) ) : ?>
              <?php if ( is_front_page() && is_home() ) : ?>
                <?php if( get_theme_mod('music_recording_studio_logo_title_hide_show',true) != ''){ ?>
                  <h1 class="site-title mb-0"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
                <?php } ?>
              <?php else : ?>
                <?php if( get_theme_mod('music_recording_studio_logo_title_hide_show',true) != ''){ ?>
                  <p class="site-title mb-0"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
                <?php } ?>
              <?php endif; ?>
            <?php endif; ?>
            <?php
              $description = get_bloginfo( 'description', 'display' );
              if ( $description || is_customize_preview() ) :
            ?>
            <?php if( get_theme_mod('music_recording_studio_tagline_hide_show',true) != ''){ ?>
              <p class="site-description mb-0">
                <?php echo esc_html($description); ?>
              </p>
            <?php } ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="<?php if(class_exists('woocommerce')){ ?>col-lg-8 col-md-6<?php } else {?> col-lg-9 col-md-8 <?php }?> col-3 align-self-center">
        <?php get_template_part('template-parts/header/navigation'); ?>
      </div>
      <?php if(class_exists('woocommerce')){ ?>
        <div class="col-lg-1 col-md-2 ps-lg-0 cart-user align-self-center text-md-end text-center mt-md-0 mt-3">
          <span class="cart-icon">
            <a href="<?php if(function_exists('wc_get_cart_url')){ echo esc_url(wc_get_cart_url()); } ?>"><i class="fas fa-shopping-cart"></i><span class="screen-reader-text"><?php esc_html_e( 'Cart','music-recording-studio' );?></span></a>
          </span>
          <span class="myaccount-icon">
            <a href="<?php echo esc_url( get_permalink( get_option('woocommerce_myaccount_page_id') ) ); ?>"><i class="fas fa-user-circle"></i><span class="screen-reader-text"><?php esc_html_e( 'MyAccount','music-recording-studio' );?></span></a>
          </span>
        </div>
      <?php }?> 
    </div>
  </div>
</div>