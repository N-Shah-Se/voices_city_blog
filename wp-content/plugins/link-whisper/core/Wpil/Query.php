<?php

/**
 * Work with DB queries
 */
class Wpil_Query
{
    /**
     * Get post statuses query row
     *
     * @param string $table
     * @return string
     */
    public static function postStatuses($table = '')
    {
        $query = "";
        $statuses = Wpil_Settings::getPostStatuses();
        $statuses = array('publish'); // todo remove if we ever make this a thing
        if (!empty($statuses)) {
            $query = " AND " . (!empty($title) ? $table."." : "") . "post_status IN ('" . implode("', '", $statuses) . "') ";
        }

        return $query;
    }

    /**
     * Get post types query row
     *
     * @param string $table
     * @return string
     */
    public static function postTypes($table = '')
    {
        $query = "";
        $post_types = Wpil_Settings::getPostTypes();
        if (!empty($post_types)) {
            $query = " AND " . ((!empty($table)) ? $table . ".post_type" : "`post_type`") . " IN ('" . implode("', '", $post_types) . "') ";
        }

        return $query;
    }

    /**
     * Get term taxonomy query row
     *
     * @param string $table
     * @return string
     */
    public static function taxonomyTypes($table = '')
    {
        $query = "";
        $taxonomies = Wpil_Settings::getTermTypes();
        if (!empty($taxonomies)) {
            $query = " AND taxonomy IN ('" . implode("', '", $taxonomies) . "')";
        }

        return $query;
    }

    /**
     * Get posts IDs for report query
     *
     * @param false $orphaned
     * @return string
     */
    public static function reportPostIds($orphaned = false, $hide_noindex = false)
    {
        global $wpdb;

        $ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpil_sync_report3' AND meta_value = '1'");
        if ($orphaned) {
            $ids = array_intersect($ids, $ids = $wpdb->get_col("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'wpil_links_inbound_internal_count' AND meta_value = '0'"));

            // remove any links that are on the ignore orphan list
            $ignored = Wpil_Settings::getItemTypeIds(Wpil_Settings::getIgnoreOrphanedPosts(), 'post');
            $ids = array_diff($ids, $ignored);

            // also remove any posts that are hidden by redirects
            $redirected = Wpil_Settings::getRedirectedPosts();
            $ids = array_diff($ids, $redirected);
        }
        
        if($orphaned || $hide_noindex){
            //** Remove any noIndex post ids **//
            // if RankMath is active, remove any ids that are set to "noIndex"
            if(defined('RANK_MATH_VERSION')){
                $id_string = " `post_id` IN ('" . implode("', '", $ids) . "')";
                $rank_math_meta = $wpdb->get_results("SELECT `post_id`, `meta_value` FROM {$wpdb->postmeta} WHERE {$id_string} AND `meta_key` = 'rank_math_robots'");

                $ids = array_flip($ids);
                foreach($rank_math_meta as $data){
                    if(false !== strpos($data->meta_value, 'noindex')){ // we can check the unserialized data because Rank Math uses a simple flag like structure to the saved data.
                        // NOTE: if the friends want to include the global no index rules, there's a "is_post_indexable" function that should do it. I went this route because it should be faster on large sites.
                        unset($ids[$data->post_id]);
                    }
                }
                $ids = array_flip($ids);
            }
    
            // if Yoast is active, remove any posts that are set to "noIndex"
            if(defined('WPSEO_VERSION')){
                $id_string = " `post_id` IN ('" . implode("', '", $ids) . "')";
                $no_index_ids = $wpdb->get_col("SELECT DISTINCT `post_id` FROM {$wpdb->postmeta} WHERE $id_string AND meta_key = '_yoast_wpseo_meta-robots-noindex' AND meta_value = '1'");
                $ids = array_diff($ids, $no_index_ids);
            }
        }

        if(!empty($ids)){
            $post_status = self::postStatuses();
            $post_types = self::postTypes();
            $ids2 = implode(',', $ids);
            $ids = array_intersect($ids, $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE ID IN ({$ids2}) {$post_types} {$post_status}"));
        }

        return !empty($ids) ? " AND p.ID IN (" . implode(',', $ids) . ")" : "";
    }

    /**
     * Get terms IDs for report query
     *
     * @param false $orphaned
     * @return string
     */
    public static function reportTermIds($orphaned = false, $hide_noindex = false)
    {
        global $wpdb;

        $ids = $wpdb->get_col("SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = 'wpil_sync_report3' AND meta_value = '1'");
        if ($orphaned) {
            $ids = array_intersect($ids, $ids = $wpdb->get_col("SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = 'wpil_links_inbound_internal_count' AND meta_value = '0'"));
        
            // remove any links that are on the ignore orphan list
            $ignored = Wpil_Settings::getItemTypeIds(Wpil_Settings::getIgnoreOrphanedPosts(), 'term');
            $ids = array_diff($ids, $ignored);
        }

        if($orphaned || $hide_noindex){
            //** Remove any noIndex post ids **//
            // if RankMath is active, remove any ids that are set to "noIndex"
            if(defined('RANK_MATH_VERSION')){
                foreach($ids as $key => $id){
                    $term = get_term($id);
                    if(is_a($term, 'WP_Error') || empty(\RankMath\Helper::is_term_indexable($term))){
                        unset($ids[$key]);
                    }
                }
            }

            // if Yoast is active rmeove any ids that are set to "noIndex"
            if(defined('WPSEO_VERSION')){
                $yoast_taxonomy_data = get_site_option('wpseo_taxonomy_meta');
                if(!empty($yoast_taxonomy_data)){
                    foreach($ids as $key => $id){
                        // if the category has been set to noIndex
                        if( isset($yoast_taxonomy_data[$id]) &&
                            isset($yoast_taxonomy_data[$id]['wpseo_noindex']) && 
                            'noindex' === $yoast_taxonomy_data[$id]['wpseo_noindex'])
                        {
                            // remove the id from the list
                            unset($ids[$key]);
                        }
                    }
                }
            }
        }

        if(!empty($ids)){
            $taxonomies = self::taxonomyTypes();
            $ids2 = implode(',', $ids);
            $ids = array_intersect($ids, $ids = $wpdb->get_col("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ({$ids2}) {$taxonomies}"));
        }

        return implode(',', $ids);
    }
}
