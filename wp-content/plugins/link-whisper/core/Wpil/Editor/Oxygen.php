<?php

use PhpOffice\PhpSpreadsheet\Writer\Ods\Content;

/**
 * Beaver editor
 *
 * Class Wpil_Editor_Oxygen
 */
class Wpil_Editor_Oxygen
{
    public static $content_types = [
        'ct_text_block',
        'oxy_rich_text',
        'oxy_tabs_content'
    ];

    public static $args_types = [
        'oxy_testimonial' => [
            'testimonial_text',
            'testimonial_author',
            'testimonial_author_info'
        ],
        'oxy_icon_box' => [
            'icon_box_text'
        ],
        'oxy_pricing_box' => [
            'pricing_box_package_title',
            'pricing_box_package_subtitle',
            'pricing_box_content'
        ]
    ];

    public static $keyword_links_count;
    public static $force_insert_link;

    /**
     * Check if editor is active
     *
     * @return bool
     */
    public static function active()
    {
        $activated_plugins = get_option('active_plugins');
        foreach ($activated_plugins as $plugin){
            if (strpos($plugin, 'oxygen/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Oxygen post content
     *
     * @param $post_id
     * @return string
     */
    public static function getContent($post_id, $remove_unprocessable = true)
    {
        $data = self::getData($post_id);
        if (!self::active() || empty($data)) {
            return '';
        }

        // if we're not removing the items we can't process
        if(!$remove_unprocessable){
            // return the rendered version of the data
            return do_shortcode(get_post_meta($post_id, 'ct_builder_shortcodes', true));
        }

        $content = '';
        foreach ($data as $item) {
            self::getItemContent($item, $content);
        }

        return $content;
    }

    /**
     * Get content from certain shortcode
     *
     * @param $item
     * @param $content
     */
    public static function getItemContent($item, &$content)
    {
        foreach (self::$args_types as $type => $types) {
            if ($item->type == $type) {
                $args = json_decode($item->args_value);
                foreach ($types as $key) {
                    if (!empty($args->original->$key)) {
                        $content .= base64_decode($args->original->$key) . "\n";
                    }
                }
            }
        }

        if (!empty($item->content) && in_array($item->type, self::$content_types)) {
            $content .= $item->content . "\n";
        }

        if (!empty($item->children)) {
            foreach ($item->children as $child)
            self::getItemContent($child, $content);
        }
    }

    /**
     * Add links to content
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content) // note: In post.php, addLinks is called with FALSE for the content arg.
    {
        $data = self::getData($post_id);
        if (!self::active() || empty($data)) {
            return;
        }

        foreach ($meta as $link) {
            self::$force_insert_link = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
            self::manageLink($data, [
                'action' => 'add',
                'sentence' => $link['sentence'],
                'replacement' => Wpil_Post::getSentenceWithAnchor($link)
            ]);
        }

        self::saveData($post_id, $data);
    }

    /**
     * Remove link from content
     *
     * @param $post_id
     * @param $url
     * @param $anchor
     */
    public static function deleteLink($post_id, $url, $anchor)
    {
        $data = self::getData($post_id);
        if (!self::active() || empty($data)) {
            return;
        }

        self::manageLink($data, [
            'action' => 'remove',
            'url' => $url,
            'anchor' => $anchor
        ]);

        self::saveData($post_id, $data);
    }

    /**
     * Remove keyword links
     *
     * @param $keyword
     * @param $post_id
     * @param bool $left_one
     */
    public static function removeKeywordLinks($keyword, $post_id, $left_one = false)
    {
        $data = self::getData($post_id);
        if (!self::active() || empty($data)) {
            return;
        }

        self::$keyword_links_count = 0;
        self::manageLink($data, [
            'action' => 'remove_keyword',
            'keyword' => $keyword,
            'left_one' => $left_one
        ]);

        self::saveData($post_id, $data);
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs($post, $url)
    {
        $data = self::getData($post->id);
        if (!self::active() || empty($data)) {
            return;
        }

        self::manageLink($data, [
            'action' => 'replace_urls',
            'url' => $url,
            'post' => $post,
        ]);

        self::saveData($post->id, $data);
    }

    /**
     * Revert URLs
     *
     * @param $post
     * @param $url
     */
    public static function revertURLs($post, $url)
    {
        $data = self::getData($post->id);
        if (!self::active() || empty($data)) {
            return;
        }

        self::manageLink($data, [
            'action' => 'revert_urls',
            'url' => $url,
        ]);

        self::saveData($post->id, $data);
    }

    /**
     * Get all content items
     *
     * @param $data
     * @param $params
     */
    public static function manageLink(&$data, $params)
    {
        if (is_countable($data)) {
            foreach ($data as $item) {
                self::checkItem($item, $params);
            }
        }
    }

    /**
     * Get content from certain item
     *
     * @param $item
     * @param $params
     */
    public static function checkItem(&$item, $params)
    {
        foreach (self::$args_types as $type => $types) {
            if ($item->type == $type) {
                $args = json_decode($item->args_value);
                foreach ($types as $key) {
                    if (!empty($args->original->$key)) {
                        $block = base64_decode($args->original->$key);
                        self::manageBlock($block, $params);
                        $args->original->$key = base64_encode($block);
                    }
                }

                $args = json_encode($args);
                if ($item->args_value != $args) {
                    $item->args_value = $args;
                }
            }
        }

        if (!empty($item->content) && in_array($item->type, self::$content_types)) {
            self::manageBlock($item->content, $params);
        }

        if (!empty($item->children)) {
            foreach ($item->children as $child) {
                self::checkItem($child, $params);
            }
        }
    }

    /**
     * Route certain item
     *
     * @param $block
     * @param $params
     */
    public static function manageBlock(&$block, $params)
    {
        if ($params['action'] == 'add') {
            self::addLinkToBlock($block, $params['sentence'], $params['replacement']);
        } elseif ($params['action'] == 'remove') {
            self::removeLinkFromBlock($block, $params['url'], $params['anchor']);
        } elseif ($params['action'] == 'remove_keyword') {
            self::removeKeywordFromBlock($block, $params['keyword'], $params['left_one']);
        } elseif ($params['action'] == 'replace_urls') {
            self::replaceURLInBlock($block, $params['url'], $params['post']);
        } elseif ($params['action'] == 'revert_urls') {
            self::revertURLInBlock($block, $params['url']);
        }
    }

    /**
     * Insert link into block
     *
     * @param $block
     * @param $sentence
     * @param $replacement
     */
    public static function addLinkToBlock(&$block, $sentence, $replacement)
    {
        if (strpos($block, $sentence) !== false) {
            Wpil_Post::insertLink($block, $sentence, $replacement, self::$force_insert_link);
        }
    }

    /**
     * Remove link from block
     *
     * @param $block
     * @param $url
     * @param $anchor
     */
    public static function removeLinkFromBlock(&$block, $url, $anchor)
    {
        // decode the url if it's base64 encoded
        if(base64_encode(base64_decode($url, true)) === $url){
            $url = base64_decode($url);
        }

        preg_match('`<a .+?' . preg_quote($url, '`') . '.+?>' . preg_quote($anchor, '`') . '</a>`i', $block,  $matches);
        if (!empty($matches[0])) {
            $block = preg_replace('|<a [^>]+' . preg_quote($url, '`') . '[^>]+>' . preg_quote($anchor, '`') . '</a>|i', $anchor,  $block);
        }
    }

    /**
     * Remove keyword links
     *
     * @param $block
     * @param $keyword
     * @param $left_one
     */
    public static function removeKeywordFromBlock(&$block, $keyword, $left_one)
    {
        $matches = Wpil_Keyword::findKeywordLinks($keyword, $block);
        if (!empty($matches[0])) {
            if (!$left_one || self::$keyword_links_count) {
                Wpil_Keyword::removeAllLinks($keyword, $block);
            }
            if($left_one && self::$keyword_links_count == 0 and count($matches[0]) > 1) {
                Wpil_Keyword::removeNonFirstLinks($keyword, $block);
            }
            self::$keyword_links_count += count($matches[0]);
        }
    }


    /**
     * Replace URL in block
     *
     * @param $block
     * @param $url
     */
    public static function replaceURLInBlock(&$block, $url, $post)
    {
        if (Wpil_URLChanger::hasUrl($block, $url)) {
            Wpil_URLChanger::replaceLink($block, $url, true, $post);
        }
    }

    /**
     * Revert URL in block
     *
     * @param $block
     * @param $url
     */
    public static function revertURLInBlock(&$block, $url)
    {
        preg_match('`data-wpil="url" (href|url)=[\'\"]' . preg_quote($url->new, '`') . '\/*[\'\"]`i', $block, $matches);
        if (!empty($matches)) {
            $block = preg_replace('`data-wpil="url" (href|url)=([\'\"])' . $url->new . '\/*([\'\"])`i', '$1=$2' . $url->old . '$3', $block);
        }
    }

    /**
     * Parse Oxygen post content
     *
     * @param $post_id
     * @return array
     */
    public static function getData($post_id)
    {
        $data = get_post_meta($post_id, 'ct_builder_shortcodes', true);
        if (!self::active() || empty($data)) {
            return [];
        }

        $data = self::getItem($data);

        return $data;
    }

    /**
     * Parse certain shortcode
     *
     * @param $data
     * @return array
     */
    public static function getItem($data)
    {
        $blocks = [];
        $begin = self::closestShortcode($data);

        $i = 0;
        while ($begin !== false) {
            $i++;
            $end = strpos($data, ' ', $begin);
            $type = substr($data, $begin + 1, $end - $begin - 1);
            $end = strpos($data, '[/' . $type . ']', $begin);
            $text = substr($data, $begin, $end - $begin + strlen($type) + 3);

            //get content
            $content_begin = strpos($text, ']');
            $sub_content_begin = strpos($text, ']"');
            // check if there's a shortcode inside the shortcode we're trying to examine
            if(!empty($sub_content_begin) && $content_begin === $sub_content_begin){
                // if there is, update the parent shortcode ending so it's actually the end and not the sub content shortcode ending...
                $content_begin = strpos($text, ']', ($sub_content_begin + 1));
            }
            $content_end = strrpos($text, '[');
            $content = substr($text, $content_begin + 1, $content_end - $content_begin - 1);

            //get sign type
            $params_end = strpos($text, ']');
            $params = substr($text, 0, $params_end);
            $params = explode(' ', $params);

            if(!isset($params[0]) || !isset($params[1])){
                if(false === $end){
                    $end = strpos($data, ']', $begin);
                }

                if(false === $end){
                    break;
                }else{
                    $begin = self::closestShortcode($data, $end + 1);
                    continue;
                }
            }

            $sig = explode('=', $params[1]);
            $sig_value = substr($sig[1], 1, -1);

            //get args
            $params = array_slice($params, 2);
            $params = implode('', $params);
            $args = preg_split('/([a-zA-Z0-9])(?:=)([\'])/', $params);
            array_shift($args);
            $args = implode('', $args);
            $args_value = trim($args, '\'');

            $blocks[] = (object)[
                'type' => $type,
                'text' => $text,
                'sig_key' => $sig[0],
                'sig_value' => $sig_value,
                'args_value' => $args_value,
                'content' => $content,
                'children' => self::getItem($content)
            ];

            $begin = self::closestShortcode($data, $end + 1);
        }

        return $blocks;
    }

    public static function closestShortcode($string = '', $offset = 0){
        if(empty($string)){
            return false;
        }
        $tags = array('[ct', '[oxy');

        $positions = array();
        foreach($tags as $tag) {
            $position = strpos($string, $tag, $offset);
            $subtag = ('"' . $tag);
            if ($position !== false && // if we've found a tag
                $position !== (strpos($string, $subtag, $offset) + 1)) // and the tag we've found doesn't belong to a sub-field shortcode
            {
                $positions[$tag] = $position; // set the position
            }
        }

        return (!empty($positions)) ? min($positions): false;
    }

    /**
     * Save Oxygen content
     *
     * @param $post_id
     * @param $data
     */
    public static function saveData($post_id, $data)
    {
        $text = '';
        foreach ($data as $item) {
            $item = self::updateItem($item);
            $text .= $item->text;
        }

        update_post_meta($post_id, 'ct_builder_shortcodes', $text);
    }

    /**
     * Update certain shortcode in the parsed data
     *
     * @param $item
     * @return mixed
     */
    public static function updateItem($item) {
        if (!empty($item->children)) {
            $item->content = '';
            foreach ($item->children as $child) {
                $updated_item = self::updateItem($child);
                $item->content .= !empty($updated_item->text) ? $updated_item->text : '';
            }
        }

        $sig_class = new OXYGEN_VSB_Signature();
        $item->sig_value = $sig_class->generate_signature( $item->type, ['ct_options' => $item->args_value], $item->content);
        $item->text = "[{$item->type} {$item->sig_key}='{$item->sig_value}' ct_options='{$item->args_value}']{$item->content}[/{$item->type}]";

        return $item;
    }
}