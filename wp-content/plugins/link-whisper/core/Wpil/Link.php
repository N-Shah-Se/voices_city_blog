<?php

/**
 * Work with links
 */
class Wpil_Link
{
    /**
     * Register services
     */
    public function register()
    {
        add_action('wp_ajax_wpil_get_link_title', ['Wpil_Link', 'getLinkTitle']);
    }

    /**
     * Delete link from post
     * @param null $params
     */
    public static function delete($params = null, $no_die = false)
    {
        foreach (['post_id', 'post_type', 'url', 'anchor', 'link_id'] as $key) {
            $$key = self::getDeleteParam($params, $key);
        }
        $anchor = !empty($anchor) ? base64_decode($anchor) : null;

        if ($post_id && $post_type && $url) {
            $post = new Wpil_Model_Post($post_id, $post_type);
            $content = $post->getCleanContent();
            $excerpt = $post->maybeGetExcerpt();

            // create the search content so we can examine more than just the post content for the link
            $search_content = trim($content . ' ' . $excerpt);

            if(self::checkIfBase64ed($url)){
                $url = base64_decode($url);
            }

            // if the url isn't in the content, check if the url in the content is relative
            if(false === strpos($search_content, '"' . $url . '"')){
                $site_url = get_home_url();
                $relative = wp_make_link_relative($url);
                // if it is, make the url the relative version
                if(false !== strpos($search_content, '"' . $relative . '"')){
                    $url = $relative;
                }elseif(false !== strpos($url, $site_url)){ // if the wp_relative function didn't work, try removing the site URL from the link and see if that works
                    // create a new relative version of the link
                    $relative = ltrim(str_replace($site_url, '', $url), '/');
                    // if the link is more than just a directory separator and does appear in the content
                    if(strlen($relative) > 1 && false !== strpos($search_content, '"/' . $relative . '"')){
                        // go with this version
                        $url = ('/' . $relative);
                    }elseif(strlen($relative) > 1 && false !== strpos($search_content, '"' . $relative . '"')){ // if the link is more than just a directory separator, and appears in the content without a leading slash
                        // go with this version
                        $url = $relative;
                    }
                }
            }

            // check if the current URL is for an image
            $is_image = false;
            if(preg_match('/\.jpg|\.jpeg|\.svg|\.png|\.gif|\.ico/i', $url) && empty($anchor)){
                // if it is, check to see if there's an image tag with this URL in the post // Since the link is already for an image, we'll check if there's an image tag on the assumption that the user is deleting an image.
                if(preg_match('`<img [^><]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^><]*>|&lt;img [^&>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^&>]*&gt;`', $content)){
                    $is_image = true;
                }
            }

            if($is_image){
                $content = self::deleteImage($post, $url, $content);
                self::deleteLinkFromMetaFields($post, $url, '', true);
                $excerpt = self::deleteImage($post, $url, $excerpt);
            }else{
                $content = self::deleteLink($post, $url, $anchor, $content);
                self::deleteLinkFromMetaFields($post, $url, $anchor);
                $excerpt = self::deleteLink($post, $url, $anchor, $excerpt);
            }

            $updated = $post->updateContent($content, $excerpt);

            if($updated){
                $post->setContent($content);
                $post->clearPostCache();
            }

            //delete link record from wpil_broken_links table
            if (!empty($link_id)) {
                Wpil_Error::deleteLink($link_id);
            }

            if (WPIL_STATUS_LINK_TABLE_EXISTS){
                Wpil_Report::update_post_in_link_table($post);
            }

            Wpil_Report::statUpdate($post);

            //update second post if link was internal inbound
            $second_post = Wpil_Post::getPostByLink($url);
            if (!empty($second_post)) {
                Wpil_Report::statUpdate($second_post);
            }
        }

        if (!$no_die) {
            die;
        }
    }

    /**
     * Deletes a link from the supplied post content.
     * @param $post The Wpil post object that we're deleting the link from
     * @param string $url The url of the link that we're removing
     * @param string|null $anchor The anchor text of the link we're removing
     * @param string $content The post content that we're removing the link from
     * @param string $run_editors Should we also tell the editors to remove the link? Defaults to true
     * @return string $content The content with the link removed.
     **/
    public static function deleteLink($post, $url, $anchor, $content, $run_editors = true){
        if ($post->type == 'post' && $run_editors && is_string($content)) {
            Wpil_Post::editors('deleteLink', [$post->id, $url, $anchor]);
            Wpil_Editor_Kadence::deleteLink($content, $url, $anchor);
        }

        // if there's no content to process, exit here
        if(empty($content)){
            return $content;
        }

        $has_anchor = !empty($anchor);
        $old_content = md5($content);

        //delete link from post content
        if($has_anchor){
            $content = preg_replace('`<a [^>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*>' . preg_quote($anchor, '`') . '</a>`i', $anchor,  $content);

            // if the link hasn't been removed
            if($old_content === md5($content)){
                // use a more aggresive regex to remove it
                $content = preg_replace('`<a [^>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*>(.*?)' . preg_quote($anchor, '`') . '(.*?)</a>`i', $anchor,  $content);

                // if that still didn't work, try removing encoded anchors
                if($old_content === md5($content)){
                    $content = preg_replace('`&lt;a [^&]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*&gt;' . preg_quote($anchor, '`') . '&lt;/a&gt;`i', $anchor,  $content);
                }
            }
        }

        // if there's no anchor or the link couldn't be deleted
        if (!$has_anchor || md5($content) === $old_content) {
            $content = preg_replace('`<a [^>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*>([\s\S]*?)</a>`i', '$3',  $content);

            // if the link hasn't been removed
            if($old_content === md5($content)){
                // try removing the encoded version of the anchor
                $content = preg_replace('`&lt;a [^&]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^>]*&gt;([\s\S]*?)&lt;/a&gt;`i', '$3',  $content);
            }
        }

        return $content;
    }

    /**
     * Deletes a specific image from a post.
     * @param $post The Wpil post object that we're deleting the link from
     * @param string $url The url of the link that we're removing
     * @param string $content The post content that we're removing the link from
     * @return string $content The content with the link removed.
     **/
    public static function deleteImage($post, $url, $content){
        /*
        if ($post->type == 'post') { // todo: look into if needed
            Wpil_Post::editors('deleteLink', [$post->id, $url]);
            Wpil_Editor_Kadence::deleteLink($content, $url);
        }*/

        // if there's no content to process, exit here
        if(empty($content)){
            return $content;
        }

        $old_content = md5($content);

        // try removing the image
        $content = preg_replace('`<img [^><]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^><]*>`i', '',  $content);

        // if the image hasn't been removed
        if($old_content === md5($content)){
            // try removing the encoded version of the tag
            $content = preg_replace('`&lt;img [^&>]+(\'|\")' . preg_quote($url, '`') . '(\'|\")[^&>]*&gt;`i', '',  $content);
        }

        return $content;
    }

    public static function getDeleteParam($params, $key)
    {
        if (!empty($params[$key])) {
            return $params[$key];
        } elseif (!empty($_POST[$key])) {
            return $_POST[$key];
        } else {
            return null;
        }
    }

    public static function deleteLinkFromMetaFields($post, $url, $anchor = '', $is_image = false){
        $fields = Wpil_Post::getMetaContentFieldList($post->type);

        // if this is a post, include any ACF fields that may exist
        if($post->type === 'post'){
            $fields = array_merge($fields, Wpil_Post::getAdvancedCustomFieldsList($post->id));
        }

        if(!empty($fields)){
            foreach($fields as $field){
                if($post->type === 'post'){
                    $content = get_post_meta($post->id, $field, true);
                }else{
                    $content = get_term_meta($post->id, $field, true);
                }

                if(!$is_image){
                    $content = self::deleteLink($post, $url, $anchor, $content);
                }else{
                    $content = self::deleteImage($post, $url, $content);
                }

                if($post->type === 'post'){
                    update_post_meta($post->id, $field, $content);
                }else{
                    update_term_meta($post->id, $field, $content);
                }
            }
        }

        /**
         * Allows the user to delete links from a custom content location
         **/
        do_action('wpil_meta_content_data_delete_link', $post->id, $post->type, $url, $anchor, $is_image);
    }

    /**
     * Check if link is internal
     *
     * @param $url
     * @return bool
     */
    public static function isInternal($url)
    {
        if (strpos($url, '//') === false) {
            return true;
        }

        if (self::markedAsExternal($url)) {
            return false;
        }

        if(self::isAffiliateLink($url)){
            return false;
        }

        $localhost = parse_url(get_home_url(), PHP_URL_HOST);
        $host = parse_url($url, PHP_URL_HOST);

        if (!empty($localhost) && !empty($host)) {
            $localhost = str_replace('www.', '', $localhost);
            $host = str_replace('www.', '', $host);
            if ($localhost == $host) {
                return true;
            }

            $internal_domains = Wpil_Settings::getInternalDomains();

            if(in_array($host, $internal_domains, true)){
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the url is a known cloaked affiliate link.
     * 
     * @param string $url The url to be checked
     * @return bool Whether or not the url is to a cloaked affiliate link. 
     **/
    public static function isAffiliateLink($url){
        // if ThirstyAffiliates is active
        if(class_exists('ThirstyAffiliates')){
            $links = self::getThirstyAffiliateLinks();

            if(isset($links[$url])){
                return true;
            }
        }

        return false;
    }

    /**
     * Get link title by URL
     */
    public static function getLinkTitle()
    {
        $link = !empty($_POST['link']) ? $_POST['link'] : '';
        $title = '';
        $id = '';
        $type = '';
        $date = __('Not Available', 'wpil');

        if ($link) {
            if (self::isInternal($link)) {
                $post_id = url_to_postid($link);
                if ($post_id) {
                    $post = get_post($post_id);
                    $title = $post->post_title;
                    $link = '/' . $post->post_name;
                    $id = $post_id;
                    $type = 'post';
                    $date = get_the_date('F j, Y', $post_id);
                } else {
                    $slugs = array_filter(explode('/', $link));
                    $term = Wpil_Term::getTermBySlug(end($slugs));
                    if (!empty($term)) {
                        $title = $term->name;
                        $link = get_term_link($term->term_id);
                        $id = $term->term_id;
                        $type = 'term';
                    }
                }
            }

            //get title if link is not post or term
            if (!$title) {
                $str = file_get_contents($link);
                if(strlen($str)>0){
                    $str = trim(preg_replace('/\s+/', ' ', $str)); // supports line breaks inside <title>
                    preg_match("/\<title\>(.*)\<\/title\>/i",$str,$title); // ignore case
                    $title = $title[1];
                }
            }

            echo json_encode([
                'title' => $title,
                'link' => $link,
                'id' => $id,
                'type' => $type,
                'date' => $date
            ]);
        }

        die;
    }

    /**
     * Remove class "wpil_internal_link" from links
     */
    public static function removeLinkClass()
    {
        global $wpdb;

        $wpdb->get_results("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'wpil_internal_link', '') WHERE post_content LIKE '%wpil_internal_link%'");
    }

    /**
     * Clean link from trash symbols
     *
     * @param $link
     * @return string
     */
    public static function clean($link)
    {
        $link = str_replace(['http://', 'https://', '//www.'], '//', strtolower(trim($link)));
        if (substr($link, -1) == '/') {
            $link = substr($link, 0, -1);
        }

        return $link;
    }

    /**
     * Check if link was marked as external
     *
     * @param $link
     * @return bool
     */
    public static function markedAsExternal($link)
    {
        $external_links = Wpil_Settings::getMarkedAsExternalLinks();

        if (in_array($link, $external_links)) {
            return true;
        }

        foreach ($external_links as $external_link) {
            if (substr($external_link, -1) == '*' && strpos($link, substr($external_link, 0, -1)) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks to see if the supplied text contains a link.
     * The check is pretty simple at this point, just seeing if the form of an opening tag or a closing tag is present in the text
     * 
     * @param string $text
     * @return bool
     **/
    public static function hasLink($text = '', $replace_text = ''){

        // if there's no link anywhere to be seen, return false
        if(empty(preg_match('/<a [^><]*?(href|src)[^><]*?>|<\/a>/i', $text))){
            return false;
        }

        // if there is a link in the replace text, return true
        if(preg_match('/<a [^><]*?(href|src)[^><]*?>|<\/a>/i', $replace_text)){
            return true;
        }

        // if there is a link, see if it ends before the replace text
        $replace_start = mb_strpos($text, $replace_text);
        if(preg_match('/<\/a>/i', mb_substr($text, 0, $replace_start)) ){
            // if it does, no worries!
            return false;
        }elseif(preg_match('/<a [^><]*?(href|src)[^><]*?>/i', mb_substr($text, 0, $replace_start)) || preg_match('/<\/a>/i', mb_substr($text, $replace_start)) ){
            // if there's an opening tag before the replace text or somewhere after the start, then presumably the replace text is in the middle of a link
            return true;
        }

        return false;
    }


    /**
     * Checks to see if the supplied text contains a heading tag.
     * The check is pretty simple at this point, just seeing if the form of an opening tag or a closing tag is present in the text
     * 
     * @param string $text
     * @return bool
     **/
    public static function hasHeading($text = '', $replace_text = '', $sentence = ''){
        // if there's no heading anywhere to be seen, return false
        if(empty(preg_match('/<h[1-6][^><]*?>|<\/h[1-6]>/i', $text))){
            return false;
        }

        // if there is a heading, see if it ends before the replace text
        $replace_start = mb_strpos($text, $sentence);
        if(preg_match('/<\/h[1-6]>/i', mb_substr($text, 0, $replace_start)) ){
            // if it does, no worries!
            return false;
        }elseif(preg_match('/<h[1-6][^><]*?>/i', mb_substr($text, 0, $replace_start)) || (preg_match('/<\/h[1-6]>/i', mb_substr($text, $replace_start)) && !preg_match('/<h[1-6][^><]*?>/i', mb_substr($text, $replace_start)) ) ){
            // if there's an opening tag before the replace text or somewhere after the start, then presumably the replace text is in the middle of a heading
            return true;
        }

        // if there is a heading in the replace text, return true
        if(substr_count($replace_text, $sentence) > 1 && preg_match('/<h[1-6][^><]*?>|<\/h[1-6]>/i', $replace_text)){
            return true;
        }

        return false;
    }

    /**
     * Checks to see if the current slice of text contains any tags that we don't want to insert a link into
     * 
     * @param string $text
     * @return bool
     **/
    public static function checkForForbiddenTags($text, $replace_text, $sentence, $ignore_links = false){
        if(self::hasLink($text, $replace_text) && !$ignore_links){
            return true;
        }elseif(self::hasHeading($text, $replace_text, $sentence)){
            return true;
        }

        return false;
    }

    public static function remove_all_links_from_text($text = ''){
        if(empty($text)){
            return $text;
        }

        $text = preg_replace('/<a[^>]+>(.*?)<\/a>/', '$1', $text);

        return $text;
    }

    /**
     * Gets all ThirstyAffiliate links in an array keyed with the urls.
     * Caches the results to save processing time later
     **/
    public static function getThirstyAffiliateLinks(){
        global $wpdb;
        $links = get_transient('wpil_thirsty_affiliate_links');

        if(empty($links)){
            // query for the link posts
            $results = $wpdb->get_col("SELECT `ID` FROM {$wpdb->posts} WHERE `post_type` = 'thirstylink'");

            // store a flag if there are no link posts
            if(empty($results)){
                set_transient('wpil_thirsty_affiliate_links', 'no-links', 5 * MINUTE_IN_SECONDS);
                return array();
            }

            // get the urls to the link posts
            $links = array();
            foreach($results as $id){
                $links[] = get_permalink($id);
            }

            // flip the array for easy searching
            $links = array_flip($links);

            // store the results
            set_transient('wpil_thirsty_affiliate_links', $links, 5 * MINUTE_IN_SECONDS);

        }elseif($links === 'no-links'){
            return array();
        }

        return $links;
    }

    /**
     * Checks to see if the supplied text is base64ed.
     * @param string $text The text to check if base64 encoded.
     * @return bool True if the text is base64 encoded, false if the string is empty or not encoded
     **/
    public static function checkIfBase64ed($text = ''){
        if(empty($text)){
            return false;
        }
        $possible = preg_match('`^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{3}=|[A-Za-z0-9+/]{2}==)?$`', $text);

        if($possible === 0){
            return false;
        }

        if(!empty(mb_detect_encoding(base64_decode($text)))){
            return true;
        }

        return false;
    }
}
