<?php

/**
 * Beaver editor
 *
 * Class Wpil_Editor_Beaver
 */
class Wpil_Editor_Beaver
{
    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        $beaver = get_post_meta($post_id, '_fl_builder_data', true);

        if (!empty($beaver)) {
            $serialized_beaver = serialize($beaver);
            foreach ($meta as $link) {
                //change sentence
                $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                $sentence = trim($link['sentence']); // Don't need to slash
                $is_autolink    = isset($link['keyword_data']);
                $add_same_link  = isset($link['keyword_data']) ? !empty($link['keyword_data']->add_same_link): false;
                $link_once      = isset($link['keyword_data']) ? !empty($link['keyword_data']->link_once): false;
                $force_insert   = isset($meta['keyword_data']) ? !empty($meta['keyword_data']->force_insert): false;

                // check if this is an autolink
                if($is_autolink){
                    // if it is, check if adding the link is inline with the link settings
                    if( false !== strpos($serialized_beaver, $changed_sentence) &&
                      ( !$add_same_link ||  
                        $add_same_link && $link_once)
                    )
                    {
                        // if the autolink settings don't allow link insertion, skip to the next link
                        continue;
                    }
                }

                //update beaver post content
                foreach ($beaver as $key => $item) {
                    foreach (['text', 'html'] as $element) {
                        if (!empty($item->settings->$element) && !isset($item->settings->link)) { // if the element has content that we can process and isn't something that comes with a link
                            if (strpos($item->settings->$element, $sentence) !== false) {
                                $before = md5($beaver[$key]->settings->$element);
                                Wpil_Post::insertLink($beaver[$key]->settings->$element, $sentence, $changed_sentence, $force_insert);
                                $after = md5($beaver[$key]->settings->$element);

                                if($before !== $after && 
                                    (   !$is_autolink                   ||  // this isn't an autolink
                                        !$add_same_link                 ||  // this is an autolink, and it hasn't been specifically set to be inserted multiple times
                                        $add_same_link && $link_once        // this is an autolink, but it's only supposed to be inserted once
                                    ) 
                                ){
                                    // exit these 2 loops
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            update_post_meta($post_id, '_fl_builder_data', $beaver);
            update_post_meta($post_id, '_fl_builder_draft', $beaver);
        }
    }

    /**
     * Delete link
     *
     * @param $post_id
     * @param $url
     * @param $anchor
     */
    public static function deleteLink($post_id, $url, $anchor)
    {
        $beaver = get_post_meta($post_id, '_fl_builder_data', true);

        if (!empty($beaver)) {
            foreach ($beaver as $key => $item) {
                foreach (['text', 'html'] as $element) {
                    if (!empty($item->settings->$element)) {
                        preg_match('|<a .+'.$url.'.+>'.$anchor.'</a>|i', $item->settings->$element,  $matches);
                        if (!empty($matches[0])) {
                            $beaver[$key]->settings->$element = preg_replace('|<a [^>]+'.$url.'[^>]+>'.$anchor.'</a>|i', $anchor,  $beaver[$key]->settings->$element);
                        }
                    }
                }
            }

            update_post_meta($post_id, '_fl_builder_data', $beaver);
            update_post_meta($post_id, '_fl_builder_draft', $beaver);
        }
    }
}
