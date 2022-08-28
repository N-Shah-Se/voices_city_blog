<?php

/**
 * Work with terms
 */
class Wpil_Term
{
    /**
     * Register services
     */
    public function register()
    {
        foreach (Wpil_Settings::getTermTypes() as $term) {
            add_action($term . '_add_form_fields', [$this, 'showTermSuggestions']);
            add_action($term . '_edit_form', [$this, 'showTermSuggestions']);
            // check the term link counts once were sure there's no more link processing to do
            add_action('saved_' . $term, [$this, 'updateTermStats'], 10, 3);
        }
    }

    /**
     * Show suggestions on term page
     */
    public static function showTermSuggestions()
    {
        if(empty($_GET['tag_ID']) ||empty($_GET['taxonomy'] || !in_array($_GET['taxonomy'], Wpil_Settings::getTermTypes()))){
            return;
        }

        $term_id = (int)$_GET['tag_ID'];
        $post_id = 0;
        $user = wp_get_current_user();
        ?>
        <div id="wpil_link-articles" class="postbox">
            <h2 class="hndle no-drag"><span><?php _e('Link Whisper Suggested Links', 'wpil'); ?></span></h2>
            <div class="inside">
                <?php include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/link_list_v2.php';?>
            </div>
        </div>
        <?php
    }

    /**
     * Updates the term's linking stats after the link adding is completed elsewhere
     **/
    public static function updateTermStats($term_id, $tt_id = 0, $updated = false){
        $term = new Wpil_Model_Post($term_id, 'term');
        if(WPIL_STATUS_LINK_TABLE_EXISTS && Wpil_Report::stored_link_content_changed($term)){
            // get the fresh term content for the benefit of the descendent methods
            $term->getFreshContent();
            // update the links stored in the link table
            Wpil_Report::update_post_in_link_table($term);
            // update the meta data for the term
            Wpil_Report::statUpdate($term, true);
            // and update the link counts for the posts that this one links to
            Wpil_Report::updateReportInternallyLinkedPosts($term);
        }
    }

    /**
     * Get all Advanced Custom Fields names
     *
     * @return array
     */
    public static function getAdvancedCustomFieldsList($term_id)
    {
        global $wpdb;

        $fields = [];

        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return $fields;
        }

        // get any ACF fields the user has ignored
        $ignored_fields = Wpil_Settings::getIgnoredACFFields();
        $fields_query = $wpdb->get_results("SELECT SUBSTR(meta_key, 2) as `name` FROM {$wpdb->termmeta} WHERE term_id = $term_id AND meta_value LIKE 'field_%' AND SUBSTR(meta_key, 2) != ''");
        foreach ($fields_query as $field) {
            $name = trim($field->name);
            if(in_array($name, $ignored_fields, true)){
                continue;
            }

            if ($name) {
                $fields[] = $field->name;
            }
        }

        return $fields;
    }

    /**
     * Get category or tag by slug
     *
     * @param $slug
     * @return WP_Term
     */
    public static function getTermBySlug($slug)
    {
        if(empty($slug) || is_int($slug) || is_array($slug)){
            return false;
        }

        $taxonomies = get_taxonomies();

        if(empty($taxonomies)){
            return false;
        }

        $taxonomies = array_values($taxonomies);

        $args = array(
            'get'                    => 'all',
            'slug'                   => $slug,
            'number'                 => 1,
            'taxonomy'               => $taxonomies,
            'update_term_meta_cache' => false,
            'orderby'                => 'none',
            'suppress_filter'        => true,
        );

        $term = get_terms( $args );

        if(empty($term) || is_a($term, 'Wp_Error') || !is_array($term)){
            return false;
        }

        return reset($term);
    }
}
