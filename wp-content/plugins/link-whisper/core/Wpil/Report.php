<?php

/**
 * Report controller
 */
class Wpil_Report
{
    static $all_post_ids = array();
    static $all_post_count;
    static $memory_break_point;

    public static $meta_keys = [
        'wpil_links_outbound_internal_count',
        'wpil_links_inbound_internal_count',
        'wpil_links_outbound_external_count'
    ];
    /**
     * Register services
     */
    public function register()
    {
        add_action('wp_ajax_reset_report_data', [$this, 'ajax_reset_report_data']);
        add_action('wp_ajax_process_report_data', [$this, 'ajax_process_report_data']);
        add_action('wp_ajax_wpil_report_reload', [$this, 'ajax_reload']);
        add_action('wp_ajax_wpil_back_to_report', [$this, 'ajax_back_to_report']);
        add_filter('screen_settings', [ $this, 'showScreenOptions' ], 10, 2);
        add_filter('set_screen_option_report_options', [$this, 'saveOptions'], 12, 3);
    }

    /**
     * Reports init function
     */
    public static function init()
    {
        global $wpdb, $user;

        //exit if user role lower than editor
        $user = wp_get_current_user();

        $type = !empty($_GET['type']) ? $_GET['type'] : '';
        //post links count update page
        if ($type == 'post_links_count_update') {
            self::postLinksCountUpdate();
            return;
        }

        //show table with reports if all reports are ready
        $tbl = new Wpil_Table_Report();
        $page = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : 'link_whisper';
        include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/link_report_v2.php';
    }

    /**
     * Resets all the stored link data in both the meta and the LW link table, on ajax call.
     **/
    public static function ajax_reset_report_data(){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";
        Wpil_Base::verify_nonce('wpil_reset_report_data');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // validate the data and set the default values
        $status = array(
            'nonce'                     => $_POST['nonce'],
            'loop_count'                => isset($_POST['loop_count'])  ? (int)$_POST['loop_count'] : 0,
            'clear_data'                => (isset($_POST['clear_data']) && 'true' === $_POST['clear_data'])  ? true : false,
            'data_setup_complete'       => false,
            'time'                      => microtime(true),
        );

        // if we're clearing data
        if(true === $status['clear_data']){
            // clear the exsting post meta
            self::clearMeta();

            // clear the link table
            self::setupWpilLinkTable();

            // check to see that the link table was successfully created
            $table = $wpdb->get_results("SELECT `post_id` FROM {$links_table} LIMIT 1");
            if(!empty($wpdb->last_error)){
                // if there was an error, let the user know about it
                wp_send_json(array(
                    'error' => array(
                        'title' => __('Database Error', 'wpil'),
                        'text'  => sprintf(__('There was an error in creating the links database table. The error message was: %s', 'wpil'), $wpdb->last_error),
                    )
                ));
            }

            // set the clear data flag to false now that we're done clearing the data
            $status['clear_data'] = false;
            // signal that the data setup is complete
            $status['data_setup_complete'] = true;
            // get the meta processing screen to show the user on the next leg of processing
            $status['loading_screen'] = self::get_loading_screen('meta-loading-screen');
            // and send back the notice
            wp_send_json($status);
        }

        // if we made it this far without a break, there must have been data missing
        wp_send_json(array(
                'error' => array(
                    'title' => __('Data Error', 'wpil'),
                    'text'  => __('There was some data missing from the reset attempt, please refresh the page and try again.', 'wpil'),
                )
        ));
    }

    /**
     * Inserts the data needed to generate the report in the meta and the link table, on ajax call.
     **/
    public static function ajax_process_report_data(){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";
        Wpil_Base::verify_nonce('wpil_reset_report_data');

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        // validate the data and set the default return values
        $status = array(
            'nonce'                         => $_POST['nonce'],
            'loop_count'                    => isset($_POST['loop_count'])             ? (int)$_POST['loop_count'] : 0,
            'link_posts_to_process_count'   => isset($_POST['link_posts_to_process_count']) ? (int)$_POST['link_posts_to_process_count'] : 0,
            'link_posts_processed'          => isset($_POST['link_posts_processed'])   ? (int)$_POST['link_posts_processed'] : 0,
            'meta_filled'                   => (isset($_POST['meta_filled']) && 'true' === $_POST['meta_filled']) ? true : false,
            'links_filled'                  => (isset($_POST['links_filled']) && 'true' === $_POST['links_filled']) ? true : false,
            'link_processing_complete'      => false,
            'time'                          => microtime(true),
        );

        // if the total post count hasn't been obtained yet
        if(0 === $status['link_posts_to_process_count']){
            $status['link_posts_to_process_count'] = self::get_total_post_count();
        }

        // if the meta flags haven't been set
        if(false === $status['meta_filled']){
            if (self::fillMeta()) {
                $status['meta_filled'] = true;
                $status['loading_screen'] = self::get_loading_screen('link-loading-screen');
            }
            wp_send_json($status);
        }

        // if the links in the table haven't been filled
        if(false === $status['links_filled']){
            // check to see if there's already some posts processed
            if(0 === $status['link_posts_processed']){
                $status['link_posts_processed'] = $wpdb->get_var("SELECT COUNT(DISTINCT {$links_table}.post_id) FROM {$links_table}");
                // clear any existing stored ids
                delete_transient('wpil_stored_unprocessed_link_ids');
            }
            // begin filling the link table with link references
            $link_processing = self::fillWpilLinkTable();
            // add the number of processed posts to the total count
            $status['link_posts_processed'] += $link_processing['inserted_posts'];
            // say if we're done processing links or not
            $status['links_filled'] = $link_processing['completed'];
            // and signal if the pre processing is complete
            $status['link_processing_complete'] = $link_processing['completed'];

            // if the links have all been processed
            if($link_processing['completed']){
                // get the post processing loading screen
                $status['loading_screen'] = self::get_loading_screen('post-loading-screen');
            }
            // send back the current status data
            wp_send_json($status);
        }

        // refresh the posts inbound/outbound link stats
        $refresh = self::refreshAllStat(true);

        // note how many posts have been refreshed
        $status['link_posts_processed'] = $refresh['loaded'];
        // and if we're done yet
        $status['processing_complete']  = $refresh['finished'];

        wp_send_json($status);
    }

    /**
     * Refresh posts statistics
     *
     * @return array
     */
    public static function refreshAllStat($report_building = false)
    {
        global $wpdb;
        $post_table  = $wpdb->posts;
        $meta_table  = $wpdb->postmeta;
        $post_types = Wpil_Settings::getPostTypes();
        $process_terms = !empty(Wpil_Settings::getTermTypes());

        //get all posts count
        $all = self::get_total_post_count();
        $post_type_replace_string = !empty($post_types) ? " AND {$wpdb->posts}.post_type IN ('" . (implode("','", $post_types)) . "') " : "";

        $updated = 0;
        if($post_types){
            // get the total number of posts that have been updated
            $updated += $wpdb->get_var("SELECT COUNT({$post_table}.ID) FROM {$post_table} LEFT JOIN {$meta_table} ON ({$post_table}.ID = {$meta_table}.post_id ) WHERE 1=1 AND ( {$meta_table}.meta_key = 'wpil_sync_report3' AND {$meta_table}.meta_value = 1 ) {$post_type_replace_string} AND {$post_table}.post_status = 'publish'");
        }
        // if categories are a selected type
        if($process_terms){
            // add the total number of categories that have been updated
            $updated += $wpdb->get_var("SELECT COUNT(`term_id`) FROM {$wpdb->termmeta} WHERE meta_key = 'wpil_sync_report3' AND meta_value = '1'");
        }
        // and subtract them from the total post count to get the number that have yet to be updated
        $not_updated_count = ($all - $updated);
        
        // get the post processing limit and add it to the query variables
        $limit = (Wpil_Settings::getProcessingBatchSize()/10);

        $start = microtime(true);
        $time_limit = ($report_building) ? 20: 5;
        $memory_break_point = self::get_mem_break_point();
        $processed_link_count = 0;
        while(true){
            // get the posts that haven't been updated, subject to the proccessing limit
            $posts_not_updated = $wpdb->get_results("SELECT {$post_table}.ID FROM {$post_table} LEFT JOIN {$meta_table} ON ({$post_table}.ID = {$meta_table}.post_id AND {$meta_table}.meta_key = 'wpil_sync_report3' ) WHERE 1=1 AND ( {$meta_table}.meta_value != 1 ) {$post_type_replace_string} AND {$post_table}.post_status = 'publish' GROUP BY {$post_table}.ID ORDER BY {$post_table}.post_date DESC LIMIT $limit");
            
            if($process_terms){
                $terms_not_updated = $wpdb->get_results("SELECT `term_id` FROM {$wpdb->termmeta} WHERE meta_key = 'wpil_sync_report3' AND meta_value = '0'");
            }else{
                $terms_not_updated = 0;
            }

            // break if there's no posts/cats to update, or the loop is out of time.
            if( (empty($posts_not_updated) && empty($terms_not_updated)) || microtime(true) - $start > $time_limit){
                break;
            }

            //update posts statistics
            if (!empty($posts_not_updated)) {
                foreach($posts_not_updated as $post){
                    if (microtime(true) - $start > $time_limit) {
                        break;
                    }

                    // if there is a memory limit and we've passed the safe limit
                    if('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point){
                        // update the last updated date
                        update_option('wpil_2_report_last_updated', date('c'));
                        // exit this loop and the WHILE loop that wraps it
                        break 2;
                    }

                    $post_obj = new Wpil_Model_Post($post->ID);
                    self::statUpdate($post_obj, $report_building);
                    $processed_link_count++;
                }
            }

            //update term statistics
            if (!empty($terms_not_updated)) {
                foreach($terms_not_updated as $cat){
                    if (microtime(true) - $start > $time_limit) {
                        break;
                    }

                    // if there is a memory limit and we've passed the safe limit
                    if('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point){
                        // update the last updated date
                        update_option('wpil_2_report_last_updated', date('c'));
                        // exit this loop and the WHILE loop that wraps it
                        break 2;
                    }

                    $post_obj = new Wpil_Model_Post($cat->term_id, 'term');
                    self::statUpdate($post_obj, $report_building);
                    $processed_link_count++;
                }
            }

            update_option('wpil_2_report_last_updated', date('c'));
        }

        $not_updated_count -= $processed_link_count;

        //create array with results
        $r = ['time'=> microtime(true),
            'success' => true,
            'all' => $all,
            'remained' => ($not_updated_count - $processed_link_count),
            'loaded' => ($all - $not_updated_count),
            'finished' => ($not_updated_count <= 0) ? true : false,
            'processed' => $processed_link_count,
            'w' => $all ? round((($all - $not_updated_count) / $all) * 100) : 100,
        ];
        $r['status'] = "$r[w]%, $r[loaded] / $r[all]";

        return $r;
    }

    /**
     * Clears the stored metadata that is created in posts and terms.
     **/
    public static function clearMeta(){
        global $wpdb;

        // create a list of the meta keys we store link data in
        $meta_keys = array( 'wpil_links_outbound_internal_count',
        'wpil_links_inbound_internal_count',
        'wpil_links_outbound_external_count',
        'wpil_links_outbound_internal_count_data',
        'wpil_links_inbound_internal_count_data',
        'wpil_links_outbound_external_count_data',
        'wpil_sync_report3',
        'wpil_sync_report2_time');

        // clear any stored meta data
        foreach($meta_keys as $key) {
            $wpdb->delete($wpdb->prefix.'postmeta', ['meta_key' => $key]);
            $wpdb->delete($wpdb->prefix.'termmeta', ['meta_key' => $key]);
        }
    }

    /**
     * Create meta records for new posts
     */
    public static function fillMeta()
    {
        global $wpdb;
        $post_table  = $wpdb->prefix . "posts";
        $meta_table  = $wpdb->prefix . "postmeta";
        
        $start = microtime(true);

        $args = array();
        $post_type_replace_string = '';
        $post_types = Wpil_Settings::getPostTypes();
        $process_terms = !empty(Wpil_Settings::getTermTypes());
        $type_count = (count($post_types) - 1);
        foreach($post_types as $key => $post_type){
            if(empty($post_type_replace_string)){
                $post_type_replace_string = ' AND ' . $post_table . '.post_type IN (';
            }
            
            $args[] = $post_type;
            if($key < $type_count){
                $post_type_replace_string .= '%s, ';
            }else{
                $post_type_replace_string .= '%s)';
            }
        }

        $limit = Wpil_Settings::getProcessingBatchSize();
        $args[] = $limit;
        while(true){
            // select a batch of posts that haven't had their link meta updated yet
            $posts = self::get_untagged_posts();

            if(microtime(true) - $start > 20 || empty($posts)){
                break;
            }

            $count = 0;
            $insert_query = "INSERT INTO {$meta_table} (post_id, meta_key, meta_value) VALUES ";
            $links_data = array ();
            $place_holders = array ();
            foreach ($posts as $post_id) {
                array_push(
                    $links_data, 
                    $post_id,
                    'wpil_sync_report3',
                    '0'
                );
                $place_holders [] = "('%d', '%s', '%s')";

                // if we've hit the limit, stop adding posts to process
                if($count > $limit){
                    break;
                }
                $count++;
            }

            if (count($place_holders) > 0) {
                $insert_query .= implode(', ', $place_holders);
                $insert_query = $wpdb->prepare($insert_query, $links_data);
                $insert_count = $wpdb->query($insert_query);
            }

            if(microtime(true) - $start > 20){
                break;
            }
        }

        // if categories are a selected type
        if($process_terms){
            //create or update meta value for categories
            $taxonomies = Wpil_Settings::getTermTypes();
            $terms = $wpdb->get_results("SELECT term_id FROM {$wpdb->term_taxonomy} WHERE taxonomy IN ('" . implode("', '", $taxonomies) . "')");
            foreach($terms as $term){
                update_term_meta($term->term_id, 'wpil_sync_report3', 0);
            }
        }
        
        $meta_filled = empty($posts);
        return $meta_filled;
    }

    /**
     * Update post links stats
     *
     * @param integer $post_id
     * @param bool $processing_for_report (Are we pulling data from the link table, or the meta? TRUE for the link table, FALSE for the meta)
     */
    public static function statUpdate($post, $processing_for_report = false)
    {
        global $wpdb;
        $meta_table = $wpdb->prefix."postmeta";

        //get links
        if($processing_for_report){
            $internal_inbound   = self::getReportInternalInboundLinks($post);
            $outbound_links     = self::getReportOutboundLinks($post);
        }else{
            $internal_inbound   = self::getInternalInboundLinks($post);
            $outbound_links     = self::getOutboundLinks($post);
        }

        if ($post->type == 'term') {
            //update term meta
            update_term_meta($post->id, 'wpil_links_inbound_internal_count', count($internal_inbound));
            update_term_meta($post->id, 'wpil_links_inbound_internal_count_data', $internal_inbound);
            update_term_meta($post->id, 'wpil_links_outbound_internal_count', count($outbound_links['internal']));
            update_term_meta($post->id, 'wpil_links_outbound_internal_count_data', $outbound_links['internal']);
            update_term_meta($post->id, 'wpil_links_outbound_external_count', count($outbound_links['external']));
            update_term_meta($post->id, 'wpil_links_outbound_external_count_data', $outbound_links['external']);
            update_term_meta($post->id, 'wpil_sync_report3', 1);
            update_term_meta($post->id, 'wpil_sync_report2_time', date('c'));
        } else {
            // create our array of meta data
            $assembled_data = array(
                                'wpil_links_inbound_internal_count'         => count($internal_inbound),
                                'wpil_links_inbound_internal_count_data'    => $internal_inbound,
                                'wpil_links_outbound_internal_count'        => count($outbound_links['internal']),
                                'wpil_links_outbound_internal_count_data'   => $outbound_links['internal'],
                                'wpil_links_outbound_external_count'        => count($outbound_links['external']),
                                'wpil_links_outbound_external_count_data'   => $outbound_links['external'],
                                'wpil_sync_report3'                         => 1,
                                'wpil_sync_report2_time'                    => date('c'));

            // check to see if any meta data already exists
            $search_query = $wpdb->prepare("SELECT * FROM {$meta_table} WHERE post_id = {$post->id} AND (`meta_key` = %s OR `meta_key` = %s OR `meta_key` = %s OR `meta_key` = %s OR `meta_key` = %s OR `meta_key` = %s OR (`meta_key` = %s AND `meta_value` = '1') OR `meta_key` = %s)", array_keys($assembled_data));
            $results = $wpdb->get_results($search_query);

            // if meta data does exist
            if(!empty($results)){
                // go over the meta we want to save
                foreach($assembled_data as $key => $value){
                    // see if there's old meta data for the current post
                    $updated = false;
                    foreach($results as $stored_data){
                        // if there is old meta data for the current post...
                        if($key === $stored_data->meta_key || $key === 'wpil_sync_report3'){
                            // check to make sure the data has changed since it was last saved
                            if($stored_data->meta_value === (string)maybe_serialize($value)){
                                // if it hasn't, mark the data as already updated and skip to the next item
                                $updated = true;
                                continue;
                            }
                            // update the meta
                            $wpdb->update(
                                $meta_table,
                                array('meta_value' => maybe_serialize($value)),
                                array('post_id' => $post->id, 'meta_key' => $key)
                            );
                            $updated = true;
                            break;
                        }
                    }
                    // if there isn't old meta data...
                    if(!$updated){
                        // insert the current data
                        $wpdb->insert(
                            $meta_table,
                            array('post_id' => $post->id, 'meta_key' => $key, 'meta_value' => maybe_serialize($value))
                        );
                    }
                }
            }else{
            // if no meta data exists, insert our values
                $meta_table = $wpdb->prefix.'postmeta';
                $insert_query = "INSERT INTO {$meta_table} (post_id, meta_key, meta_value) VALUES ";
                $links_data = array();
                $place_holders = array ();
                foreach($assembled_data as $key => $value){
                    if('wpil_sync_report3' === $key){ // skip the sync flag
                        continue;
                    }

                    array_push (
                        $links_data,
                        $post->id,
                        $key,
                        maybe_serialize($value)
                    );

                    $place_holders [] = "('%d', '%s', '%s')";
                }

                if (count($place_holders) > 0) {
                    $insert_query .= implode (', ', $place_holders);
                    $insert_query = $wpdb->prepare ($insert_query, $links_data);
                    $wpdb->query($insert_query);
                    $wpdb->update(
                        $meta_table,
                        array('meta_key' => 'wpil_sync_report3', 'meta_value' => 1),
                        array('post_id' => $post->id, 'meta_key' => 'wpil_sync_report3')
                    );
                }
            }
        }
    }

    public static function getReportInternalInboundLinks($post){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";
        $link_data = array();

        //get other internal links
        $url = $post->getLinks()->view;
        $cleaned_url = self::getCleanUrl($url);
        $cleaned_url = str_replace(['http://', 'https://'], '://', $cleaned_url);
        $protocol_variant_urls = array( ('https'.$cleaned_url), ('http'.$cleaned_url) );

        // account for ugly permalinks if this is a post
        $ugly_permalinks = "";
        if($post->type === 'post'){
            $cleaned_home_url = trailingslashit(str_replace(['http://', 'https://'], '://', get_home_url()));
            if(get_post_type($post->id) === 'page'){
                $ugly_urls = array(
                    ('https'.$cleaned_home_url.'?page_id='.$post->id),
                    ('http'.$cleaned_home_url.'?page_id='.$post->id)
                );
            }else{
                $ugly_urls = array(
                    ('https'.$cleaned_home_url.'?p='.$post->id),
                    ('http'.$cleaned_home_url.'?p='.$post->id)
                );
            }

            $ugly_permalinks = $wpdb->prepare("OR `clean_url` = '%s' OR `clean_url` = '%s'", $ugly_urls);
        }

        $redirect_urls = Wpil_Settings::getRedirectionUrls();
        $redirected = '';
        if(!empty($redirect_urls)){
            $old_url = array_search($url, $redirect_urls);

            if(!empty($old_url)){
                $cleaned_old_url = self::getCleanUrl($old_url);
                $cleaned_old_url = str_replace(['http://', 'https://'], '://', $cleaned_old_url);
                $protocol_variant_old_urls = array( ('https'.$cleaned_old_url), ('http'.$cleaned_old_url) );
                $redirected = $wpdb->prepare("OR `clean_url` = '%s' OR `clean_url` = '%s'", $protocol_variant_old_urls);
            }
        }

        // get all the links from the link table that point at this post and are on the current site.
        $results = $wpdb->get_results($wpdb->prepare("SELECT `post_id`, `post_type`, `host`, `anchor` FROM {$links_table} WHERE `clean_url` = '%s' OR `clean_url` = '%s' {$ugly_permalinks} {$redirected}", $protocol_variant_urls));

        $post_objs = array();
        foreach($results as $data){
            if(empty($data->post_id)){
                continue;
            }

            $cache_id = $data->post_type . $data->post_id;
            if(!isset($post_objs[$cache_id])){
                $post_objs[$cache_id] = new Wpil_Model_Post($data->post_id, $data->post_type);
                $post_objs[$cache_id]->content = null;
            }

            $link_data[] = new Wpil_Model_Link([
                'url' => $url,
                'host' => $data->host,
                'internal' => true,
                'post' => $post_objs[$cache_id],
                'anchor' => !empty($data->anchor) ? $data->anchor : '',
            ]);
        }

        return $link_data;

    }

    /**
     * Cleans up a URL so it's ready for saving to the database.
     * URL cleaning consists of removing query vars, removing the "www." if present and making sure there's a trailling slash.
     * Cleaned URLs are used for index and lookup purposes, so this doesn't affect what the user sees
     **/
    public static function getCleanUrl($url){

        // check if the link isn't a pretty permalink
        if( !empty($url) && 
            (false !== strpos($url, '?') || false !== strpos($url, '&')) && 
            preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values))
        {
            // if it is, clean it up just a little and return it
            return strtok(str_replace('www.', '', $url), '#');
        }

        return trailingslashit(strtok(str_replace('www.', '', $url), '?#'));
    }

    /**
     * Gets the current post's inbound links from the cache if they're available.
     * If there's no cache, it attempts to pull up the inbound links for the post.
     * If there aren't any, returns an empty array
     * 
     * @param object $post
     * @return array $link_data
     **/
    public static function getCachedReportInternalInboundLinks($post){
        $link_data = get_transient('wpil_stored_post_internal_inbound_links_' . $post->id);
        if(empty($link_data) && $link_data !== 'no_links'){
            $link_data = self::getReportInternalInboundLinks($post);

            if(!empty($link_data)){
                set_transient('wpil_stored_post_internal_inbound_links_' . $post->id, $link_data, MINUTE_IN_SECONDS * 10);
            }else{
                set_transient('wpil_stored_post_internal_inbound_links_' . $post->id, 'no_links', MINUTE_IN_SECONDS * 10);
            }

        }elseif('no_links' === $link_data){
            $link_data = array();
        }

        return $link_data;
    }

    public static function getReportOutboundLinks($post){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";

        //create initial array
        $data = array(
            'internal' => array(),
            'external' => array()
        );

        // query all of the link data that the current post has from the link table
        $links = $wpdb->get_results($wpdb->prepare("SELECT `clean_url`, `internal`, `raw_url`, `anchor`, `host`, `location` FROM {$links_table} WHERE `post_id` = '%d' AND `post_type` = %s", array($post->id, $post->type)));

        // create a post obj reference to cut down on the number of post queries
        $post_objs = array(); // keyed to clean_url

        // create a nav link reference to cut down on repetetive checks for header and footer links
        $nav_link_objs = array();

        // if the count all links option is active
        if(get_option('wpil_show_all_links', false)){
            // obtain the nav link cache
            $nav_link_objs = get_transient('wpil_nav_link_cache');

            // if it's not empty, merge it with the post objects
            if(!empty($nav_link_objs)){
                $post_objs = array_merge($post_objs, $nav_link_objs);
            }
        }

        //add links to array from post content
        foreach($links as $link){
            // skip if there's no link
            if(empty($link->clean_url)){
                continue;
            }

            // set up the post variable
            $p = null;

            // if the link is an internal one
            if($link->internal){
                // check to see if we've come across the link before
                if(!isset($post_objs[$link->clean_url])){
                    // if we haven't, get the post/term that the link points at
                    $p = Wpil_Post::getPostByLink($link->clean_url);

                    // store the post object in an array in case we need it later
                    $post_objs[$link->clean_url] = $p;

                    // if the link was a nav link
                    if($link->location === 'header' || $link->location === 'footer'){
                        // add it to the nav link array
                        $nav_link_objs[$link->clean_url] = $p;
                    }

                }else{
                    // if the link has been processed previously, set the post obj for the one we stored
                    $p = $post_objs[$link->clean_url];
                }
            }

            $link_obj = new Wpil_Model_Link([
                    'url' => $link->raw_url,
                    'anchor' => $link->anchor,
                    'host' => $link->host,
                    'internal' => ($link->internal) ? true : false,
                    'post' => $p
            ]);
            
            if ($link->internal) {
                $data['internal'][] = $link_obj;
            } else {
                $data['external'][] = $link_obj;
            }
        }

        // update the nav link cache if there are nav links
        if(!empty($nav_link_objs)){
            set_transient('wpil_nav_link_cache', $nav_link_objs, (4 * HOUR_IN_SECONDS) );
        }

        return $data;
    }

    /**
     * Collect inbound internal links
     * Todo: improve so it pulls links from page builders too
     *
     * @param object $post
     * @return array
     */
    public static function getInternalInboundLinks($post)
    {
        global $wpdb;
        $data = [];

        //get other internal links
        $url = $post->getLinks()->view;
        $host = parse_url($url, PHP_URL_HOST);

        if(empty($url)){
            return $data;
        }

        $posts = [];

        // make the url protocol agnostic
        $url2 = str_replace(['https://', 'http://'], '://', $url);

        // account for ugly permalinks if this is a post
        $ugly_permalink = "";
        if($post->type === 'post'){
                // TODO: Check if there's any reports of issues from just checking for the relative version of the urls.
                // If we pass 2.1.0 without issues, then we can probably just run with it
//            $cleaned_home_url = trailingslashit(str_replace(['http://', 'https://'], '://', get_home_url()));
            if(get_post_type($post->id) === 'page'){
//                $ugly_url = $cleaned_home_url.'?page_id='.$post->id;
                $ugly_relative = "/?page_id=".$post->id;
            }else{
//                $ugly_url = $cleaned_home_url.'?p='.$post->id;
                $ugly_relative = "/?p=".$post->id;
            }

            $ugly_permalink = $wpdb->prepare("OR post_content LIKE '%%%s%%'", $ugly_relative);
        }

        $redirect_urls = Wpil_Settings::getRedirectionUrls();
        $redirected = '';
        if(!empty($redirect_urls)){
            $old_url = array_search($url, $redirect_urls);

            if(!empty($old_url)){
                $cleaned_old_url = self::getCleanUrl($old_url);
                $cleaned_old_url = str_replace(['http://', 'https://'], '://', $cleaned_old_url);
                $redirected = $wpdb->prepare("OR post_content LIKE '%%%s%%'", $cleaned_old_url);
            }
        }

        $post_types = "AND `post_type` IN ('" . implode("','", Wpil_Settings::getPostTypes()) . "')";

        $statuses_query = Wpil_Query::postStatuses();
        $result = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE (post_content LIKE '%%%s%%' {$redirected} {$ugly_permalink}) {$post_types} {$statuses_query}", $url2));

        if ($result) {
            foreach ($result as $post) {
                $posts[] = new Wpil_Model_Post($post->ID);
            }
        }

        //get content from categories
        $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->term_taxonomy} WHERE description LIKE '%%%s%%'", $url2));
        if ($result) {
            foreach ($result as $term) {
                $posts[] = new Wpil_Model_Post($term->term_id, 'term');
            }
        }

        $posts = array_merge($posts, self::getCustomFieldsInboundLinks($url2));

        //make result array from both post types
        foreach($posts as $p){
            preg_match_all('|<a [^>]+'.$url2.'[\'\"][^>]*>([^<]*)<|i', $p->getContent(false), $anchors);

            if(!empty($ugly_permalink)){
                preg_match_all('|<a [^>]+'.$ugly_relative.'[\'\"][^>]*>([^<]*)<|i', $p->getContent(false), $anchors2);
                $anchors = array_merge($anchors, $anchors2);
            }

            $p->content = null;

            foreach ($anchors[1] as $key => $anchor) {
                if (empty($anchor) && strpos($anchors[0][$key], 'title=') !== false) {
                    preg_match('/<a\s+(?:[^>]*?\s+)?title=(["\'])(.*?)\1/i', $anchors[0][$key], $title);
                    if (!empty($title[2])) {
                        $anchor = $title[2];
                    }
                }

                $data[] = new Wpil_Model_Link([
                    'url' => $url,
                    'host' => str_replace('www.', '', $host),
                    'internal' => Wpil_Link::isInternal($url),
                    'post' => $p,
                    'anchor' => !empty($anchor) ? $anchor : '',
                ]);
            }
        }

        return $data;
    }

    /**
     * Updates the link counts for all posts that the current post is linking to.
     * Link data is from the link table.
     *
     * @param object $post
     **/
    public static function updateReportInternallyLinkedPosts($post){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";

        if(empty($post) || !is_object($post)){
            return false;
        }
        // get all the outbound internal links for the current post
        $links = $wpdb->get_results($wpdb->prepare("SELECT `clean_url` FROM {$links_table} WHERE `post_id` = '%d' AND `post_type` = '%s' AND `internal` = 1", array($post->id, $post->type)));

        // exit if there's no links
        if(empty($links)){
            return false;
        }

        // get any active redirected urls
        $redirected = Wpil_Settings::getRedirectionUrls();

        // create a list of posts that have already been updated
        $updated = array();

        //add links to array from post content
        foreach($links as $link){
            // skip if there's no link
            if(empty($link->clean_url)){
                continue;
            }

            // set up the post variable
            $p = null;

            // check to see if we've come across the link before
            if(!isset($updated[$link->clean_url])){
                // if we haven't, get the post/term that the link points at
                $p = Wpil_Post::getPostByLink($link->clean_url);

                // if we haven't found a post with the link, and there's a record of a redirect
                if(!is_a($p, 'Wpil_Model_Post') && isset($redirected[$link->clean_url])){
                    // try getting the post with the redirect link
                    $p = Wpil_Post::getPostByLink($redirected[$link->clean_url]);
                }

                // if there is a post/term
                if(is_a($p, 'Wpil_Model_Post')){
                    // update it's link counts
                    self::statUpdate($p, true);
                }

                // store the post/term url so we don't update the same post multiple times
                $updated[$link->clean_url] = true;
            }
        }

        // if any posts have been updated, return true. Otherwise, false.
        return (!empty($updated)) ? true : false;
    }

    /**
     * Get links from text
     *
     * @param $post
     * @return array
     */
    public static function getContentLinks($post)
    {
        $data = [];
        $my_host = parse_url(get_home_url(), PHP_URL_HOST);
        $post_link = $post->getLinks()->view;
        $location = 'content';
        $content = $post->getContentWithoutSetting(false);
        $content = self::process_content($content);
        $include_image_src = false;

        //get all links from content
        preg_match_all('`<a[^>]*?href=(?:\"|\')([^\"\']*?)(?:\"|\')[^>]*?>([\s\w\W]*?)<\/a>|&lt;a[^&]*?href=(?:\"|\')([^\"\']*?)(?:\"|\')[^&]*?&gt;([\s\w\W]*?)&lt;\/a&gt;|<img[^>]*?src=(?:\"|\')([^\"\']*?)(?:\"|\')[^>]*?>|<!-- wp:core-embed\/wordpress {"url":"([^"]*?)"[^}]*?"} -->|(?:>|&nbsp;|\s)((?:(?:http|ftp|https)\:\/\/)(?:[\w_-]+(?:(?:\.[\w_-]+)+))(?:[\w.,@?^=%&:/~+#-]*[\w@?^=%&/~+#-]))(?:<|&nbsp;|\s)`i', $content, $matches);

        //make array with results
        foreach ($matches[0] as $key => $value) {
            // 0 => full match
            // 1 => normal anchor URL
            // 2 => normal anchor text
            // 3 => HTML encoded anchor URL
            // 4 => HTML encoded anchor text
            // 5 => Image src URL
            // 6 => WP Embed URL
            // 7 => In-content URL

            if(!empty($matches[1][$key])){
                $url = trim($matches[1][$key]);
                $anchor = (!empty($matches[2][$key])) ? trim(strip_tags($matches[2][$key])): false;

                if(empty($anchor) && strpos($matches[0][$key], 'title=') !== false){
                    preg_match('/<a\s+(?:[^>]*?\s+)?title=(["\'])(.*?)\1/i', $matches[0][$key], $title);
                    if(!empty($title[2])){
                        $anchor = $title[2];
                    }
                }

                if(empty($anchor) || self::isJumpLink($url, $post_link)){
                    continue;
                }
            }elseif(!empty($matches[3][$key]) && !empty($matches[4][$key])){
                $url = trim($matches[3][$key]);
                $anchor = trim(strip_tags($matches[4][$key]));

                if(empty($anchor) || self::isJumpLink($url, $post_link)){
                    continue;
                }
            }elseif(!empty($matches[5][$key]) && $include_image_src){
                $url = trim($matches[5][$key]);
                $anchor = __('Link is for an image', 'wpil');
            }elseif(!empty($matches[6][$key])){
                $url = trim($matches[6][$key]);
                $anchor = __('Could not retrieve anchor text, link is embedded', 'wpil');
            }elseif(!empty($matches[7][$key])){
                $url = trim($matches[7][$key]); // if this is a link that is inserted in the content as a straight url // Mostly this means its an embed but as case history grows I'll come up with a better notice for the user
                $anchor = __('Could not retrieve anchor text, link is embedded', 'wpil');
            }else{
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);
            $p = null;

            // if we're making a point to ignore image urls
            if(false && $ignore_image_urls){
                // if the link is an image url, skip to the next match
                if(preg_match('/\.jpg|\.jpeg|\.svg|\.png|\.gif|\.ico/i', $url)){
                    continue;
                }
            }

            // ignore any links that are being used as buttons
            if(false !== strpos($url, 'javascript:void(0)')){
                continue;
            }

            // if there is no host, but it's not a jump link
            if(empty($host)){
                // set the host as the current site's
                $host = $my_host;
                // get the site url
                $site_url = get_home_url();
                // explode the site url and the current link
                $s_url_pieces = array_filter(explode('/', $site_url));
                $url_pieces = array_filter(explode('/', $url));
                // get the last word in the site url and the first word in the relative url
                $front = end($s_url_pieces); // from the front half of the url
                $end = reset($url_pieces);  // from the back half of the url

                // see if the last part of the site url is the first part of the link
                if($front === $end){
                    // if it is, remove it
                    $url_end = substr($url, strlen($end) + strpos($url, $end));
                    // and create the full url
                    $url = trailingslashit($site_url) . ltrim($url_end, '/');
                }else{
                    // if there's no overlap between the home url and the relative one, merge them together
                    $url = (trailingslashit($site_url) . ltrim($url, '/'));
                }
            }

            //check if link is internal
            if ($host == $my_host) {
                $p = Wpil_Post::getPostByLink($url);
            }

            $data[] = new Wpil_Model_Link([
                'url' => $url,
                'anchor' => $anchor,
                'host' => str_replace('www.', '', $host),
                'internal' => Wpil_Link::isInternal($url),
                'post' => $p,
                'added_by_plugin' => false,
                'location' => $location
            ]);
        }

        // get any alternate links that the post may have
        //$alternate_links = self::getAlternateLinks($post);

        if(!empty($alternate_links)){
            $data = array_merge($data, $alternate_links);
        }

        return $data;
    }

    /**
     * Processes post content so we can get links from dynamic elements like shortcodes
     **/
    public static function process_content($content = ''){
        // if the user has disabled formatting
        if(apply_filters('wpil_disable_content_link_formatting', false)){
            // return the content unchanged
            return $content;
        }

        // save a version of the content just in case the processing wipes it
        $old_content = $content;

        // get the currently active theme
        $theme = wp_get_theme();
        if(!empty($theme) && $theme->exists() &&                // if we've gotten the theme without issue
            false === stripos($theme->name, 'Acabado') &&       // and it isn't the acabado theme
            false === stripos($theme->parent_theme, 'Acabado')) // and it's not an acabado child theme
        {
            // run the content through the_content
            $content = apply_filters('the_content', $content);
        }else{
            // try processing shortcodes so we can get any links created with them
            $content = do_shortcode($content);
        }

        // if there's no content after processing
        if(empty($content) && !empty($old_content)){
            // revert to the old content
            $content = $old_content;
        }

        return $content;
    }

    public static function isJumpLink($link = '', $post_url = ''){
        $is_jump_link = false;

        // if the first char is a #
        if('#' === substr($link, 0, 1)){
            // this is a jump link
            $is_jump_link = true;
        }elseif(!empty($post_url) && strpos($link, $post_url) !== false){
            $part = explode('#', $link);
            if (strlen(str_replace($post_url, '', $part[0])) < 3) {
                // if the link is contained in the post view link, this is a jump link
                $is_jump_link = true;
            }
        }elseif(!empty($post_url) && strpos(strtok($link, '?#'), $post_url) !== false){
            // if the link is in the view link after cleaning it up, this is a jump link
            $is_jump_link = true;
        }else{
            $is_jump_link = false;
        }

        return $is_jump_link;
    }

    /**
     * Pulls in links from alternate sources like related post plugins or page builders with complex data structures
     **/
    public static function getAlternateLinks($post){
        $data = array();
        $get_related = Wpil_Settings::get_related_post_links();

        // if YARPP is active and this is a post
        if(defined('YARPP_VERSION') && $get_related && $post->type === 'post'){
            // check for the yarpp global
            global $yarpp;

            if(!empty($yarpp) && method_exists($yarpp, 'get_related')){
                $posts = $yarpp->get_related($post->id);

                if(!empty($posts)){
                    $host = parse_url(get_home_url(), PHP_URL_HOST);
                    foreach($posts as $p){
                        if(!isset($p->ID)){
                            continue;
                        }

                        $url = get_permalink($p);
                        $data[] = new Wpil_Model_Link([
                            'url' => $url,
                            'anchor' => $p->post_title,
                            'host' => str_replace('www.', '', $host),
                            'internal' => true,
                            'post' => new Wpil_Model_Post($p->ID),
                            'added_by_plugin' => false,
                            'location' => 'content'
                        ]);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get all post outbound links
     *
     * @param $post
     * @return array
     */
    public static function getOutboundLinks($post)
    {
        //create initial array
        $data = [
            'internal' => [],
            'external' => []
        ];

        //add links to array from post content
        foreach (self::getContentLinks($post) as $link) {
            if ($link->internal) {
                $data['internal'][] = $link;
            } else {
                $data['external'][] = $link;
            }
        }

        return $data;
    }

    /**
     * Show post links count update page
     */
    public static function postLinksCountUpdate()
    {
        //prepare variables
        $post = Wpil_Base::getPost();

        $start = microtime(true);

        $u = admin_url("admin.php?page=link_whisper");

        if ($post->type == 'term') {
            $prev_t = get_term_meta($post->id, 'wpil_sync_report2_time', true);

            $prev_count = [
                'inbound_internal' => (int)get_term_meta($post->id, 'wpil_links_inbound_internal_count', true),
                'outbound_internal' => (int)get_term_meta($post->id, 'wpil_links_outbound_internal_count', true),
                'outbound_external' => (int)get_term_meta($post->id, 'wpil_links_outbound_external_count', true)
            ];

            if(WPIL_STATUS_LINK_TABLE_EXISTS){
                self::update_post_in_link_table($post);
            }
            self::statUpdate($post);

            wp_cache_delete($post->id, 'term_meta');

            $time = microtime(true) - $start;
            $new_time = get_term_meta($post->id, 'wpil_sync_report2_time', true);

            $count = [
                'inbound_internal' => (int)get_term_meta($post->id, 'wpil_links_inbound_internal_count', true),
                'outbound_internal' => (int)get_term_meta($post->id, 'wpil_links_outbound_internal_count', true),
                'outbound_external' => (int)get_term_meta($post->id, 'wpil_links_outbound_external_count', true)
            ];

            $links_data = [
                'inbound_internal' => get_term_meta($post->id, 'wpil_links_inbound_internal_count_data', true),
                'outbound_internal' => get_term_meta($post->id, 'wpil_links_outbound_internal_count_data', true),
                'outbound_external' => get_term_meta($post->id, 'wpil_links_outbound_external_count_data', true)
            ];
        } else {
            $prev_t = get_post_meta($post->id, 'wpil_sync_report2_time', true);

            $prev_count = [
                'inbound_internal' => (int)get_post_meta($post->id, 'wpil_links_inbound_internal_count', true),
                'outbound_internal' => (int)get_post_meta($post->id, 'wpil_links_outbound_internal_count', true),
                'outbound_external' => (int)get_post_meta($post->id, 'wpil_links_outbound_external_count', true)
            ];

            if(WPIL_STATUS_LINK_TABLE_EXISTS){
                self::update_post_in_link_table($post);
            }
            self::statUpdate($post);

            wp_cache_delete($post->id, 'post_meta');

            $time = microtime(true) - $start;
            $new_time = get_post_meta($post->id, 'wpil_sync_report2_time', true);

            $count = [
                'inbound_internal' => (int)get_post_meta($post->id, 'wpil_links_inbound_internal_count', true),
                'outbound_internal' => (int)get_post_meta($post->id, 'wpil_links_outbound_internal_count', true),
                'outbound_external' => (int)get_post_meta($post->id, 'wpil_links_outbound_external_count', true)
            ];

            $links_data = [
                'inbound_internal' => get_post_meta($post->id, 'wpil_links_inbound_internal_count_data', true),
                'outbound_internal' => get_post_meta($post->id, 'wpil_links_outbound_internal_count_data', true),
                'outbound_external' => get_post_meta($post->id, 'wpil_links_outbound_external_count_data', true)
            ];    
        }

        include dirname(__DIR__).'/../templates/post_links_count_update.php';
    }

    /**
     * Get report data
     *
     * @param int $start
     * @param string $orderby
     * @param string $order
     * @param string $search
     * @param int $limit
     * @return array
     */
    public static function getData($start = 0, $orderby = '', $order = 'DESC', $search='', $limit=20, $orphaned = false)
    {
        global $wpdb;

        //check if it need to show categories in the list
        $options = get_user_meta(get_current_user_id(), 'report_options', true);
        $show_categories = (!empty($options['show_categories']) && $options['show_categories'] == 'off') ? false : true;
        $show_traffic = false; // Free doesn't support GSC-based traffic
        $hide_ignored = false; // Or ignoring posts
        $hide_noindex = false; // It also doesn't support hiding no index
        $process_terms = !empty(Wpil_Settings::getTermTypes());

        //calculate offset
        $offset = $start > 0 ? (($start - 1) * $limit) : 0;

        $post_types = "'" . implode("','", Wpil_Settings::getPostTypes()) . "'";

        //create search query requests
        $term_search = '';
        $title_search = '';
        $term_title_search = '';
        if (!empty($search)) {
            $is_internal = Wpil_Link::isInternal($search);
            $search_post = Wpil_Post::getPostByLink($search);
            if ($is_internal && $search_post && ($search_post->type != 'term' || ($show_categories && $process_terms))) {
                if ($search_post->type == 'term') {
                    $term_search = " AND t.term_id = {$search_post->id} ";
                    $search = " AND 2 > 3 ";
                } else {
                    $term_search = " AND 2 > 3 ";
                    $search = " AND p.ID = {$search_post->id} ";
                }
            } else {
                $search = $wpdb->prepare("%%%s%%", $search);
                $term_title_search = ", IF(t.name LIKE '{$search}', 1, 0) as title_search ";
                $title_search = ", IF(p.post_title LIKE '{$search}', 1, 0) as title_search ";
                $term_search = " AND (t.name LIKE '{$search}' OR tt.description LIKE '{$search}') ";
                $search = " AND (p.post_title LIKE '{$search}' OR p.post_content LIKE '{$search}') ";
            }
        }

        if (!empty($post_ids)) {
            $search .= " AND p.ID IN (" . implode(', ', $post_ids) . ") ";
        }

        //sorting
        if (empty($orderby) && !empty($title_search)) {
            $orderby = 'title_search';
            $order = 'DESC';
        } elseif (empty($orderby) || $orderby == 'date') {
            $orderby = 'post_date';
        }

        //get data
        $statuses_query = Wpil_Query::postStatuses('p');
        $report_post_ids = Wpil_Query::reportPostIds($orphaned, $hide_noindex);
        $report_term_ids = Wpil_Query::reportTermIds($orphaned, $hide_noindex);

        // hide ignored
        $ignored_posts = ''; // Free doesn't support ignored posts
        $ignored_terms = ''; // Or terms

        if ($orderby == 'post_date' || $orderby == 'post_title' || $orderby == 'post_type' || $orderby == 'title_search') {
            //create query for order by title or date
            $query = "SELECT p.ID, p.post_title, p.post_type, p.post_date as `post_date`, 'post' as `type` $title_search 
                        FROM {$wpdb->posts} p
                            WHERE 1 = 1 $report_post_ids $statuses_query $ignored_posts AND p.post_type IN ($post_types) $search ";

            if ($show_categories && $process_terms && !empty($report_term_ids)) {
                $taxonomies = Wpil_Settings::getTermTypes();
                $query .= " UNION
                            SELECT tt.term_id as `ID`, t.name as `post_title`, tt.taxonomy as `post_type`, '1970-01-01 00:00:00' as `post_date`, 'term' as `type` $term_title_search  
                            FROM {$wpdb->term_taxonomy} tt INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id 
                            WHERE t.term_id in ($report_term_ids) $ignored_terms AND tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') $term_search ";
            }

            $query .= " ORDER BY $orderby $order 
                        LIMIT $offset, $limit";
        } else {
            //create query for other orders
            $query = "SELECT p.ID, p.post_title, p.post_type, p.post_date as `post_date`, m.meta_value, 'post' as `type` $title_search  
                        FROM {$wpdb->prefix}posts p RIGHT JOIN {$wpdb->prefix}postmeta m ON p.ID = m.post_id
                        WHERE p.ID in (
                            SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'wpil_sync_report3' AND meta_value = '1'
                        ) AND p.post_status = 'publish' AND p.post_type IN ($post_types)  $ignored_posts AND m.meta_key LIKE '$orderby' $search";

            if ($show_categories && $process_terms && !empty($report_term_ids)) {
                $taxonomies = Wpil_Settings::getTermTypes();
                $query .= " UNION
                            SELECT t.term_id as `ID`, t.name as `post_title`, tt.taxonomy as `post_type`, '1970-01-01 00:00:00' as `post_date`, m.meta_value, 'term' as `type` $term_title_search  
                            FROM {$wpdb->prefix}termmeta m INNER JOIN {$wpdb->prefix}terms t ON m.term_id = t.term_id INNER JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id
                            WHERE t.term_id in ($report_term_ids) $ignored_terms AND tt.taxonomy IN ('" . implode("', '", $taxonomies) . "') AND m.meta_key LIKE '$orderby' $term_search";
            }

            $query .= "ORDER BY meta_value+0 $order 
                        LIMIT $offset, $limit";
        }

        $result = $wpdb->get_results($query);

        //calculate total count
        $posts_count = $wpdb->get_var("SELECT count(DISTINCT p.ID) 
            FROM {$wpdb->prefix}postmeta m INNER JOIN {$wpdb->prefix}posts p ON m.post_id = p.ID 
            WHERE m.meta_key = 'wpil_sync_report3' AND m.meta_value = '1' AND p.post_status = 'publish' AND p.post_type IN ($post_types) $search");

        $terms_count = $wpdb->get_var("SELECT count(DISTINCT t.term_id) 
            FROM {$wpdb->prefix}termmeta m INNER JOIN {$wpdb->prefix}terms t ON m.term_id = t.term_id LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON t.term_id = tt.term_id 
            WHERE m.meta_key = 'wpil_sync_report3' AND m.meta_value = '1' $term_search");

        $total_items = $posts_count + $terms_count;

        //prepare report data
        $data = [];
        foreach ($result as $key => $post) {
            if ($post->type == 'term') {
                $p = new Wpil_Model_Post($post->ID, 'term');
                $inbound = admin_url("admin.php?term_id={$post->ID}&page=link_whisper&type=inbound_suggestions_page&ret_url=" . base64_encode($_SERVER['REQUEST_URI']));
            } else {
                $p = new Wpil_Model_Post($post->ID);
                $inbound = admin_url("admin.php?post_id={$post->ID}&page=link_whisper&type=inbound_suggestions_page&ret_url=" . base64_encode($_SERVER['REQUEST_URI']));
            }

            $item = [
                'post' => $p,
                'links_inbound_page_url' => $inbound,
                'date' => $post->type == 'post' ? date('F d, Y', strtotime($post->post_date)) : 'not set'
            ];

            //get meta data
            if ($post->type == 'term') {
                foreach (self::$meta_keys as $meta_key) {
                    $item[$meta_key] = get_term_meta($post->ID, $meta_key, true);
                }
            } else {
                foreach (self::$meta_keys as $meta_key) {
                    $item[$meta_key] = get_post_meta($post->ID, $meta_key, true);
                }
            }

            $data[$key] = $item;
        }

        return array( 'data' => $data , 'total_items' => $total_items);
    }

    /**
     * Get total items depend on filters
     *
     * @param $query
     * @return string|null
     */
    public static function getTotalItems($query)
    {
        global $wpdb;

        $query = str_replace('UNION', 'UNION ALL', $query);
        $limit = strpos($query, ' LIMIT');
        $query = "SELECT count(*) FROM (" . substr($query, 0, $limit) . ") as t1";
        return $wpdb->get_var($query);
    }

    /**
     * Show screen options form
     *
     * @param $status
     * @param $args
     * @return false|string
     */
    public static function showScreenOptions($status, $args)
    {
        //Skip if it is not our screen options
        if ($args->base != Wpil_Base::$report_menu) {
            return $status;
        }

        if (!empty($args->get_option('report_options'))) {
            $options = get_user_meta(get_current_user_id(), 'report_options', true);

            // Check if the screen options have been saved. If so, use the saved value. Otherwise, use the default values.
            if ( $options ) {
                $show_categories = !empty($options['show_categories']) && $options['show_categories'] != 'off';
                $show_type = !empty($options['show_type']) && $options['show_type'] != 'off';
                $show_date = !empty($options['show_date']) && $options['show_date'] != 'off';
                $per_page = !empty($options['per_page']) ? $options['per_page'] : 20 ;
                $show_traffic = !empty($options['show_traffic']) && $options['show_traffic'] != 'off';
                $hide_ignore = !empty($options['hide_ignore']) && $options['hide_ignore'] != 'off';
                $hide_noindex = !empty($options['hide_noindex']) && $options['hide_noindex'] != 'off';
                $show_click_traffic = !empty($options['show_click_traffic']) && $options['show_click_traffic'] != 'off';
            } else {
                $show_categories = true;
                $show_date = true;
                $show_type = false;
                $per_page = 20;
                $show_traffic = false;
                $hide_ignore = false;
                $hide_noindex = false;
                $show_click_traffic = false;
            }

            //get apply button
            $button = get_submit_button( __( 'Apply', 'wp-screen-options-framework' ), 'primary large', 'screen-options-apply', false );

            //show HTML form
            ob_start();
            $report = (isset($_GET['type']) && in_array($_GET['type'], array('links', 'domains', 'error', 'clicks', 'click_details_page'), true)) ? $_GET['type']: '';
            $hide = 'style="display:none"';
            include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/report_options.php';
            return ob_get_clean();
        }

        return '';
    }

    /**
     * Save screen options
     *
     * @param $status
     * @param $option
     * @param $value
     * @return array|mixed
     */
    public static function saveOptions( $status, $option, $value ) {
        if ($option == 'report_options') {
            $value = [];
            if (isset( $_POST['report_options'] ) && is_array( $_POST['report_options'] )) {
                if (!isset($_POST['report_options']['show_categories'])) {
                    $_POST['report_options']['show_categories'] = 'off';
                }

                $cleaned_options = array();
                foreach($_POST['report_options'] as $option_key => $option_value){
                    $cleaned_options[sanitize_text_field($option_key)] = sanitize_text_field($option_value);
                }

                $value = $cleaned_options;

                if (!isset($_POST['report_options']['show_type'])) {
                    $_POST['report_options']['show_type'] = 'off';
                }
                if (!isset($_POST['report_options']['show_date'])) {
                    $_POST['report_options']['show_date'] = 'off';
                }
                if (!isset($_POST['report_options']['show_traffic'])) {
                    $_POST['report_options']['show_traffic'] = 'off';
                }
                if (!isset($_POST['report_options']['hide_ignore'])) {
                    $_POST['report_options']['hide_ignore'] = 'off';
                }
                if (!isset($_POST['report_options']['hide_noindex'])) {
                    $_POST['report_options']['hide_noindex'] = 'off';
                }
                $value = $_POST['report_options'];
            }

            return $value;
        }

        return $status;
    }

    public static function getCustomFieldsInboundLinks($url)
    {
        global $wpdb;

        if(!class_exists('ACF') || get_option('wpil_disable_acf', false)){
            return array();
        }

        $posts = [];
        $custom_fields = Wpil_Post::getAllCustomFields();
        $custom_fields = !empty($custom_fields) ? " m.meta_key IN ('" . implode("', '", $custom_fields ) . "') AND " : '';
        $statuses_query = Wpil_Query::postStatuses('p');
        $result = $wpdb->get_results($wpdb->prepare("SELECT m.post_id FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON m.post_id = p.ID WHERE $custom_fields m.meta_value LIKE '%%%s%%' AND p.post_status = 'publish'", $url));

        if ($result) {
            foreach ($result as $post) {
                $posts[] = new Wpil_Model_Post($post->post_id);
            }
        }

        return $posts;
    }

    /**
     * Creates the report links table in the database if it doesn't exist.
     * Clears the link table if it does.
     * Can be set to only create the link table if it doesn't already exist
     * @param bool $only_insert_table
     **/
    public static function setupWpilLinkTable($only_insert_table = false){
        global $wpdb;
        $wpil_links_table = $wpdb->prefix . 'wpil_report_links';
        $wpil_link_table_query = "CREATE TABLE IF NOT EXISTS {$wpil_links_table} (
                                    link_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                                    post_id bigint(20) unsigned NOT NULL,
                                    clean_url text,
                                    raw_url text,
                                    host text,
                                    anchor text,
                                    internal tinyint(1) DEFAULT 0,
                                    has_links tinyint(1) NOT NULL DEFAULT 0,
                                    post_type text,
                                    location varchar(20),
                                    broken_link_scanned tinyint(1) DEFAULT 0,
                                    PRIMARY KEY  (link_id),
                                    INDEX (post_id),
                                    INDEX (clean_url(500))
                                ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
        // create DB table if it doesn't exist
        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($wpil_link_table_query);

        // if there was an error during table creation and it was about the size of the `clean_url` index
        if(!empty($wpdb->last_error) && false !== strpos($wpdb->last_error, 'Index column size too large')){
            // build a table create query with a smaller index size and try again
            $wpil_link_table_query = str_replace('INDEX (clean_url(500))', 'INDEX (clean_url(180))', $wpil_link_table_query);
            dbDelta($wpil_link_table_query);
        }

        if(self::link_table_is_created()){
            update_option(WPIL_LINK_TABLE_IS_CREATED, true);
        }

        if(!$only_insert_table){
            // and clear any existing data
            $wpdb->query("TRUNCATE TABLE {$wpil_links_table}");
        }

        Wpil_Base::fixCollation($wpil_links_table);
    }

    /**
     * Does a full search of the DB to check for post ids that don't show up in the link table,
     * and then it processes each of those posts to extract the urls from the content to insert in the link table.
     **/
    public static function fillWpilLinkTable(){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";
        $count = 0;
        $start = microtime(true);
        $memory_break_point = self::get_mem_break_point();

        // get the ids that haven't been added to the link table yet
        $unprocessed_ids = self::get_all_unprocessed_link_post_ids();
        // if all the posts have been processed
        if(empty($unprocessed_ids)){
            // check to see if categories have been selected for processing
            if(!empty(Wpil_Settings::getTermTypes())){
                // check for categories
                $terms = [];
                $updated_terms = $wpdb->get_results("SELECT DISTINCT `post_id` FROM {$links_table} WHERE `post_type` = 'term'");
                foreach ($updated_terms as $key => $term) {
                    $terms[] = $term->post_id;
                }
                $term_query = !empty($terms) ? " AND `term_id` NOT IN (" . implode(',', $terms) . ") " : "";
                $terms = $wpdb->get_results("SELECT `term_id` FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy IN ('" . implode("','" , Wpil_Settings::getTermTypes()) . "') " . $term_query);

                // if there are categories
                $term_update_count = 0;
                if ($terms) {
                    foreach ($terms as $term) {
                        if(Wpil_Base::overTimeLimit(15, 30) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                            break;
                        }

                        // insert the term's links into the link table
                        $post = new Wpil_Model_Post($term->term_id, 'term');
                        $term_insert_count = self::insert_links_into_link_table($post);

                        // if the link insert was successful, increase the update count
                        if($term_insert_count > 0){
                            $term_update_count += $term_insert_count;
                        }
                    }
                }

                // if all the found cats have had their links loaded in the database
                if(count($terms) === $term_update_count){
                    // return success
                    return array('completed' => true, 'inserted_posts' => $term_update_count);
                }else{
                    // if not, go around again
                    return array('completed' => false, 'inserted_posts' => $term_update_count);
                }
            }
            
            return array('completed' => true, 'inserted_posts' => 0);
        }

        foreach($unprocessed_ids as $key =>  $id){
            // exit the loop if we've been at this for 30 seconds or we've passed the memory breakpoint
            if(Wpil_Base::overTimeLimit(15, 30) || ('disabled' !== $memory_break_point && memory_get_usage() > $memory_break_point)){
                break; 
            }

            // set up a new post with the current id
            if(self::insert_links_into_link_table(new Wpil_Model_Post($id))){
                $count++;
                unset($unprocessed_ids[$key]);
            }
        }
        
        // update the stored list of unprocessed ids
        set_transient('wpil_stored_unprocessed_link_ids', $unprocessed_ids, MINUTE_IN_SECONDS * 5);
        
        return array('completed' => false, 'inserted_posts' => $count);
    }

    /**
     * Checks to see if the links in the current post's content are different from the ones stored in the Links table
     *
     * @param object $post The post object that we're checking
     * @return bool True if the links have changed, False if they haven't
     **/
    public static function stored_link_content_changed($post){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";

        if(empty($post)){
            return false;
        }

        $reset_content = false;
        if(!$post->hasStoredContent()){
            $post->setContent($post->getContentDirectly());
            $reset_content = true;
        }

        $stored_links   = $wpdb->get_results($sting = $wpdb->prepare("SELECT `raw_url`, `anchor` FROM {$links_table} WHERE `post_id` = %d AND `post_type` = %s", $post->id, $post->type));
        $post_links     = self::getContentLinks($post);

        if($reset_content){
            $post->setContent(null);
        }

        // if there are links in the content and in storage, create URL-anchor strings so we can compare them
        $stored = '';
        $content = '';

        foreach($stored_links as $link){
            $stored .= ($link->raw_url . $link->anchor);
        }

        foreach($post_links as $link){
            $content .= ($link->url . $link->anchor);
        }

        return md5($stored) !== md5($content);
    }

    /**
     * Updates a post's content links by removing the existing link data from the link table and inserting new links from the post content.
     * @param int|object $post 
     * @return bool
     **/
    public static function update_post_in_link_table($post){
        // if we've just been given a post id
        if(is_numeric($post) && !is_object($post)){
            // create a new post object
            $post = new Wpil_Model_Post($post);
        }

        // if the post doesn't already have content stored
        if(!$post->hasStoredContent()){
            // clear the content to make sure we have the most recent version
            $post->getFreshContent();
        }

        $remove = self::remove_post_from_link_table($post);
        $insert = self::insert_links_into_link_table($post);

        return (empty($remove) || empty($insert)) ? false : true;
    }

    public static function remove_post_from_link_table($post, $delete_link_refs = false){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";

        // exit if a post id isn't given
        if(empty($post)){
            return 0;
        }

        // delete the rows for this post that are stored in the links table
        $results = $wpdb->delete($links_table, array('post_id' => $post->id, 'post_type' => $post->type));
        $results2 = 0;

        // if we're supposed to remove the links that point to the current post as well
        if($delete_link_refs){
            // get the url
            $url = $post->getLinks()->view;
            $cleaned_url = self::getCleanUrl($url);
            // if there is a url
            if(!empty($cleaned_url)){
                // delete the rows that have this post's url in them
                $results2 = $wpdb->delete($links_table, array('clean_url' => $cleaned_url));
            }
        }

        // add together the results of both possible delete operations to get the total rows removed
        return (((int) $results) + ((int) $results2));
    }

    /**
     * Extracts the links from the given post and inserts them into the link table.
     * @param object $post 
     * @return int $count (1 if success, 0 if failure)
     **/
    public static function insert_links_into_link_table($post){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";

        $count = 0;
        $links = self::getContentLinks($post);
        $insert_query = "INSERT INTO {$links_table} (post_id, clean_url, raw_url, host, anchor, internal, has_links, post_type) VALUES ";
        $links_data = array();
        $place_holders = array();
        foreach($links as $link){
            array_push (
                $links_data,
                $post->id,
                self::getCleanUrl($link->url),
                $link->url,
                $link->host,
                $link->anchor,
                $link->internal,
                1,
                $post->type
            );

            $place_holders [] = "('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s')";
        }

        if (count($place_holders) > 0) {
            $insert_query .= implode (', ', $place_holders);
            $insert_query = $wpdb->prepare ($insert_query, $links_data);
            $insert = $wpdb->query ($insert_query);

            // if the insert was successful
            if(false !== $insert){
                // increase the insert count
                $count += 1;
            }
        }

        // if there are no links, update the link table with null values to remove it from processing
        if(empty($links)){
            $insert = $wpdb->insert(
                $links_table,
                array(
                    'post_id' => $post->id,
                    'clean_url' => null,
                    'raw_url' => null,
                    'host' => null,
                    'anchor' => null,
                    'internal' => null,
                    'has_links' => 0,
                    'post_type' => $post->type
                )
            );

            // if the insert was successful
            if(false !== $insert){
                // increase the insert count
                $count += 1;
            }
        }
        
        return $count;
    }

    /**
     * Gets all post ids from the post table and returns an array of ids.
     * @return array $all_post_ids (an array of all post ids from the post table. Categories aren't included. We're focusing on post ids since they make up the bulk of the ids)
     **/
    public static function get_all_post_ids(){
        if (empty(self::$all_post_ids)){
            global $wpdb;

            $post_types = Wpil_Settings::getPostTypes();
            $post_type_replace_string = "";
            if (!empty($post_types)) {
                $post_type_replace_string = " AND post_type IN ('" . implode("', '", $post_types) . "') ";
            }

            self::$all_post_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE `post_status` = 'publish' $post_type_replace_string");
        }

        return self::$all_post_ids;
    }

    /**
     * Gets all post ids that aren't listed in the link table.
     * Checks a transient to see if there's a stored list of un updated ids.
     * If there isn't, it checks the database directly
     * @return array $unprocessed_ids (All of the post ids that haven't been listed in the link table yet.)
     **/
    public static function get_all_unprocessed_link_post_ids(){
        global $wpdb;
        $stored_ids = get_transient('wpil_stored_unprocessed_link_ids');

        if ($stored_ids){
            $unprocessed_ids = $stored_ids;
        } else {
            $all_post_ids = self::get_all_post_ids();
            $all_processed_ids = $wpdb->get_col("SELECT DISTINCT post_id AS ID FROM {$wpdb->prefix}wpil_report_links");
            $unprocessed_ids = array_diff($all_post_ids, $all_processed_ids);
            set_transient('wpil_stored_unprocessed_link_ids', $unprocessed_ids, MINUTE_IN_SECONDS * 5);
        }

        // and return the results of our efforts
        return $unprocessed_ids;
    }

    /**
     * Gets the total number of posts that are eligible to include in the link table.
     * This counts all post types selected in the LW settings, including categories.
     * @return int $all_post_count
     **/
    public static function get_total_post_count(){
        global $wpdb;
        $post_table  = $wpdb->prefix . "posts";
        $term_table  = $wpdb->prefix . "term_taxonomy";

        if(isset(self::$all_post_count) && !empty(self::$all_post_count)){
            return self::$all_post_count;
        }else{
            // get all of the site's posts that are in our settings group
            $post_types = Wpil_Settings::getPostTypes();
            $post_type_replace_string = !empty($post_types) ? " AND post_type IN ('" . (implode("','", $post_types)) . "') " : "";
            $statuses_query = Wpil_Query::postStatuses();
            $post_count = $wpdb->get_var("SELECT COUNT(ID) FROM {$post_table} WHERE 1=1 {$post_type_replace_string} $statuses_query");
            // if term is a selected type
            if(!empty(Wpil_Settings::getTermTypes())){
                // get all the site's categories that aren't empty
                $taxonomies = Wpil_Settings::getTermTypes();
                $cat_count = $wpdb->get_var("SELECT COUNT(DISTINCT term_id) FROM {$term_table} WHERE `taxonomy`IN ('" . implode("', '", $taxonomies) . "')");
            }else{
                $cat_count = 0;
            }

            // add the post count and term count together and return
            self::$all_post_count = ($post_count + $cat_count);
            return self::$all_post_count;
        }
    }

    /**
     * Gets the PHP memory safe usage limit so we know when to quit processing.
     * Currently, the break point is 20mb short of the PHP memory limit.
     **/
    public static function get_mem_break_point(){
        if(isset(self::$memory_break_point) && !empty(self::$memory_break_point)){
            return self::$memory_break_point;
        }else{
            $mem_limit = ini_get('memory_limit');
            
            if(empty($mem_limit) || '-1' == $mem_limit){
                self::$memory_break_point = 'disabled';
                return self::$memory_break_point;
            }

            $mem_size = 0;
            switch(substr($mem_limit, -1)){
                case 'M': 
                case 'm': 
                    $mem_size = (int)$mem_limit * 1048576;
                    break;
                case 'K':
                case 'k':
                    $mem_size = (int)$mem_limit * 1024;
                    break;
                case 'G':
                case 'g':
                    $mem_size = (int)$mem_limit * 1073741824;
                    break;
                default: $mem_size = $mem_limit;
            }

            $mem_break_point = ($mem_size - ($mem_size * 0.15)); // break point == (mem limit - 15%)
            
            if($mem_break_point < 0){
                self::$memory_break_point = 'disabled';
            }else{
                self::$memory_break_point = $mem_break_point;
            }

            return self::$memory_break_point;
        }
    }

    public static function get_loading_screen($screen = ''){
        switch($screen){
            case 'meta-loading-screen':
                ob_start();
                include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/report_prepare_meta_processing.php';
                $return_screen = ob_get_clean();
            break;
            case 'link-loading-screen':
                ob_start();
                include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/report_prepare_link_inserting_into_table.php';
                $return_screen = ob_get_clean();
            break;
            case 'post-loading-screen':
                ob_start();
                include WP_INTERNAL_LINKING_PLUGIN_DIR . 'templates/report_prepare_process_links.php';
                $return_screen = ob_get_clean();
            break;            
            default:
                $return_screen = '';
        }
        
        return $return_screen;
    }

    /**
     * Checks to see if the link table is created.
     **/
    public static function link_table_is_created(){
        global $wpdb;
        $links_table = $wpdb->prefix . "wpil_report_links";
        // check to see that the link table was successfully created
        $table = $wpdb->get_var("SHOW TABLES LIKE '$links_table'");
        if ($table != $links_table) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Gets the posts that haven't had their meta filled yet.
     **/
    public static function get_untagged_posts(){
        global $wpdb;
        $post_table  = $wpdb->prefix . "posts";
        $meta_table  = $wpdb->prefix . "postmeta";

        $args = array();
        $post_type_replace_string = '';
        $post_types = Wpil_Settings::getPostTypes();
        $type_count = (count($post_types) - 1);
        foreach($post_types as $key => $post_type){
            if(empty($post_type_replace_string)){
                $post_type_replace_string = ' AND ' . $post_table . '.post_type IN (';
            }

            $args[] = $post_type;
            if($key < $type_count){
                $post_type_replace_string .= '%s, ';
            }else{
                $post_type_replace_string .= '%s)';
            }
        }

        // First get all the site's posts
        $all_post_ids = self::get_all_post_ids();
        // Then get the ids of all the posts that have the processing flag
        $posts_with_flag = $wpdb->get_results("SELECT `post_id` FROM {$meta_table} WHERE `meta_key` = 'wpil_sync_report3' ORDER BY `post_id` ASC");

        // create a list of all posts that haven't had their meta filled yet.
        $all_post_ids = array_flip($all_post_ids);
        foreach($posts_with_flag as $flagged_post){
            $all_post_ids[$flagged_post->post_id] = false;
        }

        $unfilled_posts = array_flip(array_filter($all_post_ids, 'strlen'));

        return $unfilled_posts;
    }

    function ajax_reload() {

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        echo get_transient('wpil_report_reload') ? 'yes' : 'no';
        delete_transient('wpil_report_reload');
        die;
    }

    function ajax_back_to_report() {

        // be sure to ignore any external object caches
        Wpil_Base::ignore_external_object_cache();

        set_transient('wpil_report_reload', 'reload');
        die;
    }

}
