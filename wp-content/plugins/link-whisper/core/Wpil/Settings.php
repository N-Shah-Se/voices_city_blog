<?php

/**
 * Work with settings
 */
class Wpil_Settings
{
    public static $ignore_phrases = null;
    public static $ignore_words = null;
    public static $stemmed_ignore_words = null;
    public static $keys = [
        'wpil_2_ignore_numbers',
        'wpil_2_post_types',
        'wpil_2_term_types',
        'wpil_option_update_reporting_data_on_save',
        'wpil_skip_section_type',
        'wpil_skip_sentences',
        'wpil_selected_language',
        'wpil_ignore_links',
        'wpil_ignore_categories',
        'wpil_show_all_links',
        'wpil_disable_acf',
        'wpil_count_related_post_links',
        'wpil_domains_marked_as_internal',
        'wpil_custom_fields_to_process',
        'wpil_delete_all_data',
        'wpil_include_post_meta_in_support_export',
    ];

    /**
     * Show settings page
     */
    public static function init()
    {
        $types_active = Wpil_Settings::getPostTypes();
        $term_types_active = Wpil_Settings::getTermTypes();
        $types_available = get_post_types(['public' => true]);
        $term_types_available = array_intersect(array('category', 'post_tag', 'product_cat', 'product_tag'), get_taxonomies());
        $statuses_available = [
            'publish',
            'private',
            'future',
            'pending',
            'draft'
        ];
        $statuses_active = Wpil_Settings::getPostStatuses();

        include WP_INTERNAL_LINKING_PLUGIN_DIR . '/templates/wpil_settings_v2.php';
    }

    /**
     * Get ignore phrases
     */
    public static function getIgnorePhrases()
    {
        if (is_null(self::$ignore_phrases)) {
            $phrases = [];
            foreach (self::getIgnoreWords() as $word) {
                if (strpos($word, ' ') !== false) {
                    $phrases[] = preg_replace('/\s+/', ' ',$word);
                }
            }

            self::$ignore_phrases = $phrases;
        }

        return self::$ignore_phrases;
    }

    /**
     * Get ignore words
     */
    public static function getIgnoreWords()
    {
        if (is_null(self::$ignore_words)) {
            $words = get_option('wpil_2_ignore_words', null);
            // get the user's current language
            $selected_language = self::getSelectedLanguage();

            // if there are no stored words or the current language is different from the selected one
            if (is_null($words) || (WPIL_CURRENT_LANGUAGE !== $selected_language)) {
                $ignore_words_file = self::getIgnoreFile($selected_language);
                $words = file($ignore_words_file);

                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
            } else {

                $words = explode("\n", $words);
                $words = array_unique($words);
                sort($words);

                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
            }

            self::$ignore_words = $words;
        }

        return self::$ignore_words;
    }

    /**
     * Get stemmed versions of the ignore words
     */
    public static function getStemmedIgnoreWords()
    {
        if (is_null(self::$stemmed_ignore_words)) {
            $words = self::getIgnoreWords();
            foreach($words as $key => $word) {
                $words[$key] = trim(Wpil_Stemmer::Stem($word));
            }

            // remove any duplicates
            $words = array_keys(array_flip($words));

            self::$stemmed_ignore_words = $words;
        }

        return self::$stemmed_ignore_words;
    }

    /**
     * Gets all current ignore word lists.
     * The word list for the language the user is currently using is loaded from the settings.
     * All other languages are loaded from the word files
     **/
    public static function getAllIgnoreWordLists(){
        $current_language       = self::getSelectedLanguage();
        $supported_languages    = self::getSupportedLanguages();
        $all_ignore_lists       = array();

        // go over all currently supported languages
        foreach($supported_languages as $language_id => $supported_language){

            // if the current language is the user's selected one
            if($language_id === $current_language){

                $words = get_option('wpil_2_ignore_words', null);
                if(is_null($words)){
                    $words = self::getIgnoreWords();
                }else{
                    $words = explode("\n", $words);
                    $words = array_unique($words);
                    sort($words);
                    foreach($words as $key => $word) {
                        $words[$key] = trim(Wpil_Word::strtolower($word));
                    }
                }

                $all_ignore_lists[$language_id] = $words;
            }else{
                $ignore_words_file = self::getIgnoreFile($language_id);
                $words = array();
                if(file_exists($ignore_words_file)){
                    $words = file($ignore_words_file);
                }else{
                    // if there is no word file, skip to the next one
                    continue;
                }
                
                if(empty($words)){
                    $words = array();
                }
                
                foreach($words as $key => $word) {
                    $words[$key] = trim(Wpil_Word::strtolower($word));
                }
                
                $all_ignore_lists[$language_id] = $words;
            }
        }

        return $all_ignore_lists;
    }

    /**
     * Get ignore words file based on current language
     *
     * @param $language
     * @return string
     */
    public static function getIgnoreFile($language)
    {
        switch($language){
            case 'spanish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/ES_ignore_words.txt';
                break;
            case 'french':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/FR_ignore_words.txt';
                break;
            case 'german':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/DE_ignore_words.txt';
                break;
            case 'russian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/RU_ignore_words.txt';
                break;
            case 'portuguese':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/PT_ignore_words.txt';
                break;
            case 'dutch':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/NL_ignore_words.txt';
                break;
            case 'danish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/DA_ignore_words.txt';
                break;
            case 'italian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/IT_ignore_words.txt';
                break;
            case 'polish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/PL_ignore_words.txt';
                break;            
            case 'slovak':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SK_ignore_words.txt';
                break;
            case 'norwegian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/NO_ignore_words.txt';
                break;
            case 'swedish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SW_ignore_words.txt';
                break;            
            case 'arabic':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/AR_ignore_words.txt';
                break;
            case 'serbian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/SR_ignore_words.txt';
                break;
            case 'finnish':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/FI_ignore_words.txt';
                break;
            case 'hebrew':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HE_ignore_words.txt';
                break;
            case 'hindi':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HI_ignore_words.txt';
                break;
            case 'hungarian':
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/HU_ignore_words.txt';
                break;
            default:
                $file = WP_INTERNAL_LINKING_PLUGIN_DIR . 'includes/ignore_word_lists/EN_ignore_words.txt';
                break;
        }

        return $file;
    }

    /**
     * Get selected post types
     *
     * @return mixed|void
     */
    public static function getPostTypes()
    {
        return get_option('wpil_2_post_types', ['post', 'page']);
    }

    /**
     * Get merged array of post types and term types
     *
     * @return array
     */
    public static function getAllTypes()
    {
        return array_merge(self::getPostTypes(), self::getTermTypes());
    }

    /**
     * Get selected post statuses
     *
     * @return array
     */
    public static function getPostStatuses()
    {
        return get_option('wpil_2_post_statuses', ['publish']);
    }

    public static function getInternalDomains(){
        $domains = get_transient('wpil_domains_marked_as_internal');
        if(empty($domains)){
            $domains = array();
            $domain_data = get_option('wpil_domains_marked_as_internal');
            $domain_data = explode("\n", $domain_data);
            foreach ($domain_data as $domain) {
                $pieces = wp_parse_url($domain);
                if(!empty($pieces) && isset($pieces['host'])){
                    $domains[] = str_replace('www.', '', $pieces['host']);
                }
            }

            set_transient('wpil_domains_marked_as_internal', $domains, 15 * MINUTE_IN_SECONDS);
        }

        return $domains;
    }

    /**
     * Gets any custom content fields that the user has defined on his site and wants to process for content.
     * @return array $fields Returns an array if there's fields, and an empty arry if there's no fields.
     **/
    public static function getCustomFieldsToProcess(){
        $fields = get_transient('wpil_custom_fields_to_process');
        if(empty($fields)){
            $fields = get_option('wpil_custom_fields_to_process', array());

            if(empty($fields)){
                $fields = 'no-fields';
            }else{
                $fields = explode("\n", $fields);
            }

            set_transient('wpil_custom_fields_to_process', $fields, 15 * MINUTE_IN_SECONDS);
        }

        if($fields === 'no-fields'){
            return array();
        }

        return $fields;
    }

    /**
     * Gets the currently supported languages
     * 
     * @return array
     **/
    public static function getSupportedLanguages(){
        $languages = array(
            'english'       => 'English',
            'spanish'       => 'Español',
            'french'        => 'Français',
            'german'        => 'Deutsch',
            'russian'       => 'Русский',
            'portuguese'    => 'Português',
            'dutch'         => 'Nederlands',
            'danish'        => 'Dansk',
            'italian'       => 'Italiano',
            'polish'        => 'Polskie',
            'norwegian'     => 'Norsk bokmål',
            'swedish'       => 'Svenska',
            'slovak'        => 'Slovenčina',
            'arabic'        => 'عربي',
            'serbian'       => 'Српски / srpski',
            'finnish'       => 'Suomi',
            'hebrew'        => 'עִבְרִית',
            'hindi'         => 'हिन्दी',
            'hungarian'     => 'Magyar',
        );
        
        return $languages;
    }

    /**
     * Gets the currently selected language
     * 
     * @return array
     **/
    public static function getSelectedLanguage(){
        return get_option('wpil_selected_language', 'english');
    }

    /**
     * Gets the language for the current processing run.
     * Does a check to see if there's a translation plugin active.
     * If there is, it tries to set the current language to the current post's language.
     * If that's not possible, or there isn't a translation plugin, it defaults to the set language
     **/
    public static function getCurrentLanguage(){

        // if Polylang is active
        if(defined('POLYLANG_VERSION')){
            // see if we're creating suggestions and there's a post
            if( isset($_POST['action']) && ($_POST['action'] === 'get_post_suggestions' || $_POST['action'] === 'update_suggestion_display') &&
                isset($_POST['post_id']) && !empty($_POST['post_id']))
            {
                global $wpdb;
                $post_id = (int) $_POST['post_id'];

                // get the language ids
                $language_ids = $wpdb->get_col("SELECT `term_taxonomy_id` FROM $wpdb->term_taxonomy WHERE `taxonomy` = 'language'");

                // if there are no ids, return the selected language from the settings
                if(empty($language_ids)){
                    return self::getSelectedLanguage();
                }

                $language_ids = implode(', ', $language_ids);

                // check the term_relationships to see if any are applied to the current post
                $tax_id = $wpdb->get_var("SELECT `term_taxonomy_id` FROM $wpdb->term_relationships WHERE `object_id` = {$post_id} AND `term_taxonomy_id` IN ({$language_ids})");

                // if there are no ids, return the selected language from the settings
                if(empty($tax_id)){
                    return self::getSelectedLanguage();
                }

                // query the wp_terms to get the language code for the applied language
                $code = $wpdb->get_var("SELECT `slug` FROM $wpdb->terms WHERE `term_id` = {$tax_id}");

                // if we've gotten the language code, see if we support the language
                if($code){
                    $supported_language_codes = array(
                        'en' => 'english',
                        'es' => 'spanish',
                        'fr' => 'french',
                        'de' => 'german',
                        'ru' => 'russian',
                        'pt' => 'portuguese',
                        'nl' => 'dutch',
                        'da' => 'danish',
                        'it' => 'italian',
                        'pl' => 'polish',
                        'sk' => 'slovak',
                        'nb' => 'norwegian',
                        'sv' => 'swedish',
                        'sd' => 'arabic',
                        'snd' => 'arabic',
                        'sr' => 'serbian',
                        'fi' => 'finnish',
                        'he' => 'hebrew',
                        'hi' => 'hindi',
                        'hu' => 'hungarian'
                    );

                    // if we support the language, return it as the active one
                    if(isset($supported_language_codes[$code])){
                        return $supported_language_codes[$code];
                    }
                }
            }
        }

        // if WPML is active
        if(self::wpml_enabled()){
            // see if we're creating suggestions and there's a post
            if( isset($_POST['action']) && ($_POST['action'] === 'get_post_suggestions' || $_POST['action'] === 'update_suggestion_display') &&
            isset($_POST['post_id']) && !empty($_POST['post_id']))
            {
                global $wpdb;
                $post_id = (int) $_POST['post_id'];
                $post_type = get_post_type($post_id);
                $post_type = 'post_' . $post_type;
                $code = $wpdb->get_var("SELECT language_code FROM {$wpdb->prefix}icl_translations WHERE element_id = $post_id AND `element_type` = '{$post_type}'");

                if(!empty($code)){

                    $supported_language_codes = array(
                        'en' => 'english',
                        'es' => 'spanish',
                        'fr' => 'french',
                        'de' => 'german',
                        'ru' => 'russian',
                        'pt-br' => 'portuguese',
                        'pt-pt' => 'portuguese',
                        'nl' => 'dutch',
                        'da' => 'danish',
                        'it' => 'italian',
                        'pl' => 'polish',
                        'sk' => 'slovak',
                        'no' => 'norwegian',
                        'sv' => 'swedish',
                        'ar' => 'arabic',
                        'sr' => 'serbian',
                        'fi' => 'finnish',
                        'he' => 'hebrew',
                        'hi' => 'hindi',
                        'hu' => 'hungarian'
                    );

                    // if we support the language, return it as the active one
                    if(isset($supported_language_codes[$code])){
                        return $supported_language_codes[$code];
                    }
                }
            }
        }

        return self::getSelectedLanguage();
    }

    public static function getProcessingBatchSize(){
        $batch_size = (int) get_option('wpil_option_suggestion_batch_size', 300);
        if($batch_size < 10){
            $batch_size = 10;
        }
        return $batch_size;
    }

    /**
     * This function is used handle settting page submission
     *
     * @return  void
     */
    public static function save()
    {
        if (isset($_POST['wpil_save_settings_nonce'])
            && wp_verify_nonce($_POST['wpil_save_settings_nonce'], 'wpil_save_settings')
            && isset($_POST['hidden_action'])
            && $_POST['hidden_action'] == 'wpil_save_settings'
        ) {
            //prepare ignore words to save
            $ignore_words = sanitize_textarea_field(stripslashes(trim(base64_decode($_POST['ignore_words']))));
            $ignore_words = mb_split("\n|\r", $ignore_words);
            $ignore_words = array_unique($ignore_words);
            $ignore_words = array_filter(array_map('trim', $ignore_words));
            sort($ignore_words);
            $ignore_words = implode(PHP_EOL, $ignore_words);

            //update ignore words
            update_option(WPIL_OPTION_IGNORE_WORDS, $ignore_words);

            if (empty($_POST[WPIL_OPTION_POST_TYPES]))
            {
                $_POST[WPIL_OPTION_POST_TYPES] = [];
            }

            if (empty($_POST['wpil_2_term_types'])) {
                $_POST['wpil_2_term_types'] = [];
            }

            //save other settings
            $opt_keys = self::$keys;
            foreach($opt_keys as $opt_key) {
                if (array_key_exists($opt_key, $_POST)) {
                    if(is_array($_POST[$opt_key])){
                        update_option($opt_key, array_map('sanitize_text_field', $_POST[$opt_key]));
                    }else{
                        update_option($opt_key, sanitize_text_field($_POST[$opt_key]));
                    }
                }
            }

            // clear the item caches if they're set
            delete_transient('wpil_ignore_links');
            delete_transient('wpil_ignore_external_links');
            delete_transient('wpil_ignore_keywords_posts');
            delete_transient('wpil_ignore_categories');
            delete_transient('wpil_domains_marked_as_internal');
            delete_transient('wpil_links_to_ignore');
            delete_transient('wpil_suggest_to_outbound_posts');
            delete_transient('wpil_ignore_acf_fields');
            delete_transient('wpil_ignore_click_links');
            delete_transient('wpil_sponsored_domains');
            delete_transient('wpil_custom_fields_to_process');

            wp_redirect(admin_url('admin.php?page=link_whisper_settings&success'));
            exit;
        }
    }

    public static function getSkipSectionType()
    {
        return 'sentences';
    }

    public static function getSkipSentences()
    {
        return get_option('wpil_skip_sentences', 3);
    }

    /**
     * Checks to see if the site has a translation plugin active
     * 
     * @return bool
     **/
    public static function translation_enabled(){
        if(defined('POLYLANG_VERSION')){
            return true;
        }elseif(self::wpml_enabled()){
            return true;
        }

        return false;
    }

    /**
     * Check if WPML installed and has at least 2 languages
     *
     * @return bool
     */
    public static function wpml_enabled()
    {
        global $wpdb;

        // if WPML is activated
        if(function_exists('icl_object_id') || class_exists('SitePress')){
            $languages_count = 1;
            $table = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}icl_languages'");
            if ($table == $wpdb->prefix . 'icl_languages') {
                $languages_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}icl_languages WHERE active = 1");
            } else {
                $languages_count = $wpdb->get_var("SELECT count(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'language'");
            }

            if (!empty($languages_count) && $languages_count > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get checked term types
     *
     * @return array
     */
    public static function getTermTypes()
    {
        $terms = get_option('wpil_2_term_types', []);
        return array_intersect(array('category', 'post_tag', 'product_cat', 'product_tag'), $terms);
    }

    /**
     * Get ignore posts
     * Currently disabled.
     * Pulls posts from cache if available to save processing time.
     *
     * @return array
     */
    public static function getIgnorePosts()
    {
        return array();
        $posts = get_transient('wpil_ignore_links');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_links');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            set_transient('wpil_ignore_links', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Get ignored orphaned posts
     * Used in the link report page
     *
     * @return array
     */
    public static function getIgnoreOrphanedPosts()
    {
        return array();
        $posts = [];
        $links = get_option('wpil_ignore_orphaned_posts');
        $links = explode("\n", $links);
        foreach ($links as $link) {
            $post = Wpil_Post::getPostByLink($link);
            if (!empty($post)) {
                $posts[] = $post->type . '_' . $post->id;
            }
        }

        return $posts;
    }

    /**
     * Get categories list to be ignored
     *
     * @return array
     */
    public static function getIgnoreCategoriesPosts()
    {
        return array(); // todo: remove if we implement ignore categories
        $posts = get_transient('wpil_ignore_categories');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_ignore_categories', '');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $category = Wpil_Post::getPostByLink(trim($link));
                if (!empty($category)) {
                    $posts = array_merge($posts, Wpil_Post::getCategoryPosts($category->id));
                }
            }
            $posts = array_values(array_flip(array_flip($posts)));

            set_transient('wpil_ignore_categories', $posts, 15 * MINUTE_IN_SECONDS);
        }

        return $posts;
    }

    /**
     * Gets an array of post ids to affirmatively make outbound links to.
     *
     * @return array
     */
    public static function getOutboundSuggestionPostIds()
    {
        $posts = get_transient('wpil_suggest_to_outbound_posts');
        if(empty($posts)){
            $posts = [];
            $links = get_option('wpil_suggest_to_outbound_posts', '');
            $links = explode("\n", $links);
            foreach ($links as $link) {
                $post = Wpil_Post::getPostByLink($link);
                if (!empty($post)) {
                    $posts[] = $post->type . '_' . $post->id;
                }
            }

            if(empty($posts)){
                $posts = 'no-posts';
            }

            set_transient('wpil_suggest_to_outbound_posts', $posts, 15 * MINUTE_IN_SECONDS);
        }

        // if there are no posts
        if($posts === 'no-posts'){
            // return an empty array
            $posts = array();
        }

        return $posts;
    }

    /**
     * Gets an array of type specific ids from the url input settings.
     */
    public static function getItemTypeIds($ids = array(), $type = 'post'){
        if($type === 'post'){
            $ids = array_map(function($id){ if(false !== strpos($id, 'post_')){ return substr($id, 5); }else{ return false;} }, $ids);
            $ids = array_filter($ids);
        }else{
            $ids = array_map(function($id){ if(false !== strpos($id, 'term_')){ return substr($id, 5); }else{ return false;} }, $ids);
            $ids = array_filter($ids);
        }

        return $ids;
    }

    /**
     * Gets if the user wants to count links from related post plugins in the Links Report.
     * Returns false if the user has opted to show all links because that includes related post links already.
     **/
    public static function get_related_post_links()
    {
        return !empty(get_option('wpil_count_related_post_links', false));
    }

    /**
     * Get links that was marked as external
     *
     * @return array
     */
    public static function getMarkedAsExternalLinks()
    {
        $links = get_option('wpil_marked_as_external', '');

        if (!empty($links)) {
            $links = explode("\n", $links);
            foreach ($links as $key => $link) {
                $links[$key] = trim($link);
            }

            return $links;
        }

        return [];
    }

    /**
     * Gets a list of posts that have had redirects applied to their urls.
     * Obtains the redirect list from plugins that offer redirects.
     * Results are cached for 5 minutes
     * 
     * @param bool $flip Should we return a flipped array of post ids so they can be searched easily?
     * @return array $post_ids And array of posts that have had redirections applied to them
     **/
    public static function getRedirectedPosts($flip = false){
        global $wpdb;

        $post_ids = get_transient('wpil_redirected_post_ids');

        if(!empty($post_ids) && $post_ids !== 'no-ids'){
            // refresh the transient
            set_transient('wpil_redirected_post_ids', $post_ids, 5 * MINUTE_IN_SECONDS);
            // and return the ids
            return ($flip) ? array_flip($post_ids) : $post_ids;
        }elseif($post_ids === 'no-ids'){
            // if a prevsious run hadn't found any ids, return an empty array
            return array();
        }

        // set up the id array
        $post_ids = array();

        // if RankMath is active and the redirections table exists
        if(defined('RANK_MATH_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections'"))){
            $dest_url_cache = array();

            $permalink_format = get_option('permalink_structure', '');
            $post_name_position = false;

            if(false !== strpos($permalink_format, '%postname%')){
                $pieces = explode('/', $permalink_format);
                $piece_count = count($pieces);
                $post_name_position = array_search('%postname%', $pieces);
            }

            // get the active redirect rules from Rank Math
            $active_redirections = $wpdb->get_results("SELECT `id`, `url_to` FROM {$wpdb->prefix}rank_math_redirections WHERE `status` = 'active'");

            // if there are redirections
            if(!empty($active_redirections)){
                $redirection_ids = array();
                foreach($active_redirections as $dat){
                    if(!isset($dest_url_cache[$dat->url_to])){
                        $id = url_to_postid($dat->url_to);
                        $dest_url_cache[$dat->url_to] = $id;
                    }

                    $redirection_ids[] = $dat->id;
                }

                // if there are posts with updated urls, get the ids so we can ignore them
                $ignore_posts = '';
                if(!empty($dest_url_cache) && !empty(array_filter(array_values($dest_url_cache)))){
                    $ignore_posts = "AND `object_id` NOT IN (" . implode(', ',array_filter(array_values($dest_url_cache))) . ")";
                }

                $redirection_ids = implode(', ', $redirection_ids);
                $redirection_data = $wpdb->get_results("SELECT `from_url`, `object_id` FROM {$wpdb->prefix}rank_math_redirections_cache WHERE `redirection_id` IN ({$redirection_ids}) {$ignore_posts}"); // we're getting the redriects from the cache to save processing time. Rules based searching could take a long time

                // go over the data from the Rank Math cache
                $post_names = array();
                foreach($redirection_data as $dat){
                    // if a redirect was specified for a post, grab the id directly
                    if(isset($dat->object_id) && !empty($dat->object_id)){
                        $post_ids[] = $dat->object_id;
                    }else{
                        // if a url was redirected based on a rule, try to get the post name from the data so we can search the post table for it
                        $url_pieces = explode('/', $dat->from_url);
                        $url_pieces_count = count($url_pieces);

                        if($post_name_position && $url_pieces_count === $piece_count){  // if the url uses the permalink settings and therefor has the same number of pieces as the permalink string (EX: it's a post)
                            $post_names[] = $url_pieces[$post_name_position];
                        }elseif($url_pieces_count === 1){                               // if the url is just the slug
                            $post_names[] = $dat->from_url;
                        }elseif($url_pieces_count === 2 || $url_pieces_count === 3){    // if the url is just the slug, but there's a slash or two
                            $post_names[] = $url_pieces[1];
                        }
                    }
                }

                // if we've found the post names
                if(!empty($post_names)){
                    // query the post table with them to get the post ids
                    $post_names = implode('\', \'', $post_names);
                    $ids = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE `post_name` IN ('{$post_names}')");

                    // if there's ids
                    if(!empty($ids)){
                        // add them to the list of post ids that are redirected away from
                        $post_ids = array_merge($post_ids, $ids);
                    }
                }
            }
        }

        // if there aren't any ids
        if(empty($post_ids)){
            // make a note that there aren't any and return an empty
            set_transient('wpil_redirected_post_ids', 'no-ids', 5 * MINUTE_IN_SECONDS);
        }else{
            // save the fruits of our labours in the cache
            set_transient('wpil_redirected_post_ids', $post_ids, 5 * MINUTE_IN_SECONDS);
        }

        return ($flip && !empty($post_ids)) ? array_flip($post_ids) : $post_ids;
    }

    /**
     * Obtains an array of URLs that have been redirected away from and their destination URLs.
     * The output is an array of new URLs keyed to the old URLs that are being redirected away from.
     * All URLs are trailing slashed for consistency.
     * When comparing URLs in content to the URLs, be sure to slash them.
     *
     * Currently supports Rank Math and Redirection (John Godley)
     * At the moment, we're only focusing on the absolute versions of the URLs.
     * Nobody has asked for relative, and there's only been a couple users that have ever mentioned using relative links.
     * Added to this is the fact that the inbound linking functionality only counts absolute URLs makes adding relative moot.
     **/
    public static function getRedirectionUrls(){
        global $wpdb;

        $urls = get_transient('wpil_redirected_post_urls');

        if($urls !== 'no-redirects' && !empty($urls)){
            // refresh the transient
            set_transient('wpil_redirected_post_urls', $urls, 5 * MINUTE_IN_SECONDS);
            // and return the URLs
            return $urls;
        }elseif($urls === 'no-redirects'){
            return array();
        }

        // set up the url array
        $urls = array();

        if(defined('RANK_MATH_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}rank_math_redirections'"))){
            // get the active redirect rules from Rank Math
            $active_redirections = $wpdb->get_results("SELECT `id`, `url_to` FROM {$wpdb->prefix}rank_math_redirections WHERE `status` = 'active'");

            // if there are redirections
            if(!empty($active_redirections)){

                $redirection_ids = array();
                foreach($active_redirections as $dat){
                    $redirection_ids[$dat->id] = trailingslashit($dat->url_to);
                }

                $id_string = implode(', ', array_keys($redirection_ids));
                $redirection_data = $wpdb->get_results("SELECT `from_url`, `object_id`, `redirection_id` FROM {$wpdb->prefix}rank_math_redirections_cache WHERE `redirection_id` IN ({$id_string})"); // we're getting the redriects from the cache to save processing time. Rules based searching could take a long time

                // go over the data from the Rank Math cache
                foreach($redirection_data as $dat){
                    $url = trailingslashit(self::makeLinkAbsolute($dat->from_url));
                    $redirected_url = trailingslashit(self::makeLinkAbsolute($redirection_ids[$dat->redirection_id]));
                    $urls[$url] = $redirected_url;
                }
            }
        }

        if(defined('WPSEO_VERSION')){
            $active_redirections   = $wpdb->get_results("SELECT option_name, option_value FROM  {$wpdb->options} WHERE option_name = 'wpseo-premium-redirects-export-plain'");
            foreach ( $active_redirections as $redirection ) {
                $dat = maybe_unserialize($redirection->option_value);
                if(!empty($dat)){
                    foreach($dat as $key => $d){
                        $url = trailingslashit(self::makeLinkAbsolute($key));
                        $redirected_url = trailingslashit(self::makeLinkAbsolute($d['url']));
                        $urls[$url] = $redirected_url;
                    }
                }
            }
        }

        /**
         * Search for the redirects from the dedicated redirect pl;ugin last to override the SEO plugins' redirects
         **/
        if(defined('REDIRECTION_VERSION') && !empty($wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}redirection_items'"))){
            // get the redirect plugin data
            $active_redirections = $wpdb->get_results("SELECT `url`, `action_data` FROM {$wpdb->prefix}redirection_items WHERE `match_type` ='url' AND `match_url` != 'regex'");

            // add the redirections to the url list
            foreach($active_redirections as $dat){
                if(is_string($dat->action_data)){
                    $url = trailingslashit(self::makeLinkAbsolute($dat->url));
                    $action_data = trailingslashit(self::makeLinkAbsolute($dat->action_data));
                    $urls[$url] = $action_data;
                }
            }
        }

        // if we've found some redirected urls
        if(!empty($urls)){
            // save the fruits of our labours in the cache
            set_transient('wpil_redirected_post_urls', $urls, 5 * MINUTE_IN_SECONDS);
        }else{
            // otherwise, set a flag so we know there's no urls to keep an eye out for
            set_transient('wpil_redirected_post_urls', 'no-redirects', 5 * MINUTE_IN_SECONDS);
        }

        if('no-redirects' === $urls){
            return array();
        }

        return $urls;
    }

    /**
     * Makes the supplied link an absolute one.
     * If the link is already absolute, the link is returned unchanged
     * 
     * @param string $url The relative link to make absolute
     * @return string $url The absolute version of the link
     **/
    public static function makeLinkAbsolute($url){
        $site_url = trailingslashit(get_home_url());
        $site_domain = wp_parse_url($site_url, PHP_URL_HOST);
        $site_scheme = wp_parse_url($site_url, PHP_URL_SCHEME);
        $url_domain = wp_parse_url($url, PHP_URL_HOST);

        // if the link isn't pointing to the current domain, 
        if( strpos($url, $site_domain) === false && 
            empty($url_domain) &&                       // but also isn't pointing to an external one
            strpos($url, 'www.') !== 0)                 // and doesn't start with "www.". (Even though browsers DO consider this to be a relative URL. The user didn't mean for it to be)
        {
            $url = ltrim($url, '/');
            $url_pieces = array_reverse(explode('/', rtrim(trim($site_url), '/')));

            foreach($url_pieces as $piece){
                if(empty($piece) || false === strpos(trim($url), $piece)){
                    $url = $piece . '/' . $url;
                }
            }
        }elseif(strpos($url, 'http') === false){
            $url = rtrim($site_scheme, ':') . '://' . ltrim($url, '/');
        }

        return $url;
    }

    /**
     * Gets the labels for the given post types.
     * Currently, only gets the labels for the public post types because the non-public ones are usually utility post types and the labels are often generic.
     * So if we used their given labels, it may confuse the user.
     *
     * @param string|array $post_types The list of post types that we're getting the labels for. Can also accept a single post type string
     * @return array $labled_types An array of post type labels keyed to their respective post types. Or an empty array if we can't find the post types...
     **/
    public static function getPostTypeLabels($post_types = array()){
        $labled_types = array();

        if(empty($post_types) || (!is_array($post_types) && !is_string($post_types))){
            return $labled_types;
        }

        if(is_string($post_types)){
            $post_types = array($post_types);
        }

        foreach($post_types as $type){
            $type_object = get_post_type_object($type);
            if(!empty($type_object)){
                if(!empty($type_object->public)){
                    $labled_types[$type_object->name] = $type_object->label;
                }else{
                    $labled_types[$type_object->name] = $type_object->name;
                }
            }
        }

        return $labled_types;
    }
}
