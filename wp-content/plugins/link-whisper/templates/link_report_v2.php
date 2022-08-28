<div class="wrap wpil-report-page wpil_styles">
    <?php if( empty( get_option(WPIL_PREMIUM_NOTICE_DISMISSED, '') ) ){ ?>
    <div id="lw_banner">
        <img class="close" src="<?php echo esc_url(WP_INTERNAL_LINKING_PLUGIN_URL . 'images/icon_delete.png'); ?>">
        <div class="title"><?php _e('Upgrade to Link Whisper Premium', 'wpil'); ?></div>
        <div class="features">
            <div><?php _e('+ Add internal links with a single click!', 'wpil'); ?></div>
            <div><?php _e('+ Get inbound internal link suggestions from the reports screen.', 'wpil'); ?></div>
            <div><?php _e('+ Add inbound internal links directly from reports with a single click.', 'wpil'); ?></div>
            <div><?php _e('+ Customize the anchor text by clicking the words you want.', 'wpil'); ?></div>
            <div><?php _e('+ No more copying and pasting links, just click and done!', 'wpil'); ?></div>
            <div><?php _e('+ Save hours of time and gain more control over your internal links.', 'wpil'); ?></div>
        </div>
        <a href="<?php echo esc_url(WPIL_STORE_URL); ?>" target="blank"><?php _e('Get Link Whisper Premium', 'wpil'); ?></a>
    </div>
    <?php } ?>

    <?=Wpil_Base::showVersion()?>
    <h1 class="wp-heading-inline"><?php _e('Internal links report', 'wpil'); ?></h1>
    <?php $user = wp_get_current_user(); ?>
    <hr class="wp-header-end">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder">
            <div id="post-body-content" style="position: relative;">
                <?php $user = wp_get_current_user(); ?>
                <form action='' method="post" style="float: right;" id="wpil_report_reset_data_form">
                    <input type="hidden" name="reset" value="1">
                    <input type="hidden" name="reset_data_nonce" value="<?php echo wp_create_nonce($user->ID . 'wpil_reset_report_data'); ?>">
                    <button type="submit" class="button-primary">Run Link Scan</button>
                </form>
                <div class="tbl-link-reports">
                    <form>
                        <input type="hidden" name="page" value="link_whisper" />
                        <input type="hidden" name="type" value="links" />
                        <?php $tbl->search_box('Search', 'search_posts'); ?>
                    </form>
                    <?php $tbl->display(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var admin_url = '<?=admin_url()?>';
</script>
