<?php
/**
 * Template Name: Custom Home Page
 */

get_header(); ?>

<main id="maincontent" role="main">
  <?php do_action( 'music_recording_studio_before_slider' ); ?>

  <?php if( get_theme_mod( 'music_recording_studio_slider_hide_show', false) != '' || get_theme_mod( 'music_recording_studio_resp_slider_hide_show', false) != '') { ?>
    <section id="slider">        
      <div id="carouselExampleInterval" class="carousel carousel-fade" data-bs-ride="carousel" data-bs-interval="<?php echo esc_attr(get_theme_mod( 'music_recording_studio_slider_speed',4000)) ?>">
        <?php $music_recording_studio_pages = array();
          for ( $count = 1; $count <= 4; $count++ ) {
            $mod = intval( get_theme_mod( 'music_recording_studio_slider_page' . $count ));
            if ( 'page-none-selected' != $mod ) {
              $music_recording_studio_pages[] = $mod;
            }
          }
          if( !empty($music_recording_studio_pages) ) :
            $args = array(
              'post_type' => 'page',
              'post__in' => $music_recording_studio_pages,
              'orderby' => 'post__in'
            );
            $query = new WP_Query( $args );
            if ( $query->have_posts() ) :
              $i = 1;
        ?>
        <div class="carousel-inner" role="listbox">
          <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <div <?php if($i == 1){echo 'class="carousel-item active"';} else{ echo 'class="carousel-item"';}?>>
                  <div class="slider-bg">
                    <?php the_post_thumbnail(); ?>
                  </div>
                <div class="carousel-caption">
                  <div class="row">
                    <div class="col-lg-7 col-md-6 align-self-center">
                      <?php the_post_thumbnail(); ?>
                    </div>
                    <div class="col-lg-5 col-md-6 align-self-center">
                      <div class="inner_carousel">
                        <h1><a href="<?php echo esc_url( get_permalink() ); ?>" title="<?php echo the_title_attribute(); ?>"><?php the_title(); ?></a></h1>
                        <p class="slider-text"><?php $excerpt = get_the_excerpt(); echo esc_html( music_recording_studio_string_limit_words( $excerpt, esc_attr(get_theme_mod('music_recording_studio_slider_excerpt_number','15')))); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
          <?php $i++; endwhile; 
          wp_reset_postdata();?>
        </div>
        <?php else : ?>
          <div class="no-postfound"></div>
        <?php endif;
        endif;?>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleInterval" id="prev" data-bs-slide="prev">
        <i class="fas fa-arrow-left"></i>
        <span class="screen-reader-text"><?php echo esc_html('Previous','music-recording-studio'); ?></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleInterval" data-bs-slide="next" id="next">
        <i class="fas fa-arrow-right"></i>
        <span class="screen-reader-text"><?php echo esc_html('Next','music-recording-studio'); ?></span>
        </button>
      </div> 
    </section>
  <?php }?>

  <?php do_action( 'music_recording_studio_after_slider' ); ?>

  <?php if(get_theme_mod('music_recording_studio_section_title') != '' || get_theme_mod('music_recording_studio_section_small_title') != '' || get_theme_mod('music_recording_studio_service_category') != '') {?>
    <section id="service-section" class="py-5">
      <div class="container">
        <div class="service-head text-center mb-5">
          <?php if( get_theme_mod('music_recording_studio_section_small_title') != '' ){ ?>
            <strong class="small-title"><?php echo esc_html(get_theme_mod('music_recording_studio_section_small_title'));?></strong>
          <?php }?>
          <?php if( get_theme_mod('music_recording_studio_section_title') != '' ){ ?>
            <h2><?php echo esc_html(get_theme_mod('music_recording_studio_section_title'));?></h2>
          <?php }?>
        </div>
        <div class="owl-carousel">
          <?php
          $music_recording_studio_catData = get_theme_mod('music_recording_studio_service_category');
          if($music_recording_studio_catData){
            $page_query = new WP_Query(array( 'category_name' => esc_html( $music_recording_studio_catData ,'music-recording-studio')));
            while( $page_query->have_posts() ) : $page_query->the_post(); ?>
              <div class="service-box text-center wow zoomIn" data-wow-duration="2s">
                <div class="bx-image"><?php the_post_thumbnail(); ?></div>
                <div class="service-content">
                  <h3><a href="<?php the_permalink(); ?>"><?php the_title();?></a></h3>
                </div>
              </div>
            <?php endwhile;
            wp_reset_postdata();
          } ?>
        </div>
      </div>
    </section>
  <?php }?>

  <?php do_action( 'music_recording_studio_after_service' ); ?>

  <div id="content-vw" class="py-3">
    <div class="container">
      <?php while ( have_posts() ) : the_post(); ?>
        <?php the_content(); ?>
      <?php endwhile; // end of the loop. ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>