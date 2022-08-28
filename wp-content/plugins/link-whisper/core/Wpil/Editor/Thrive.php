<?php

/**
 * Thrive editor
 *
 * Class Wpil_Editor_Thrive
 */
class Wpil_Editor_Thrive
{
    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        $thrive = get_post_meta($post_id, 'tve_updated_post', true);

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, 'tve_content_before_more', true);
            foreach ($meta as $link) {
                $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                if (strpos($thrive, $link['sentence']) === false) {
                    $link['sentence'] = addslashes($link['sentence']);
                }
                Wpil_Post::insertLink($thrive_before, $link['sentence'], $changed_sentence, $force_insert);
                Wpil_Post::insertLink($thrive, $link['sentence'], $changed_sentence, $force_insert);
            }

            update_post_meta($post_id, 'tve_updated_post', $thrive);
            update_post_meta($post_id, 'tve_content_before_more', $thrive_before);
        }

        $template = get_post_meta($post_id, 'tve_landing_page', true);
        // if the post has the Thrive Template active
        if($template){
            $thrive = get_post_meta($post_id, 'tve_updated_post_' . $template, true);

            if($thrive){
                $thrive_before = get_post_meta($post_id, 'tve_content_before_more_', true);
                foreach ($meta as $link) {
                    $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                    $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                    if (strpos($thrive, $link['sentence']) === false) {
                        $link['sentence'] = addslashes($link['sentence']);
                    }
                    Wpil_Post::insertLink($thrive_before, $link['sentence'], $changed_sentence, $force_insert);
                    Wpil_Post::insertLink($thrive, $link['sentence'], $changed_sentence, $force_insert);
                }

                update_post_meta($post_id, 'tve_updated_post_' . $template, $thrive);
                update_post_meta($post_id, 'tve_content_before_more_', $thrive_before);
            }
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
        $content_key = 'tve_updated_post';
        $before_more_key = 'tve_content_before_more';

        $thrive = get_post_meta($post_id, $content_key, true);

        // if we couldn't find the content, see if this is a thrive templated page
        if(empty($thrive)){
            // if it is
            if(get_post_meta($post_id, 'tve_landing_set', true) && $thrive_template = get_post_meta($post_id, 'tve_landing_page', true)){
                // get the template content
                $thrive = get_post_meta($post_id, 'tve_updated_post_' . $thrive_template, true);
                // and update the keys
                $content_key = 'tve_updated_post_' . $thrive_template;
                $before_more_key = 'tve_content_before_more_' . $thrive_template;
            }
        }

        if (!empty($thrive)) {
            $thrive_before = get_post_meta($post_id, $before_more_key, true);

            preg_match('|<a .+'.$url.'.+>'.$anchor.'</a>|i', $thrive,  $matches);
            if (!empty($matches[0])) {
                $url = addslashes($url);
                $anchor = addslashes($anchor);
            }

            $post = new Wpil_Model_Post($post_id); // post is just for compatibility
            $thrive_before = Wpil_Link::deleteLink($post, $url, $anchor, $thrive_before, false);
            $thrive = Wpil_Link::deleteLink($post, $url, $anchor, $thrive, false);

            update_post_meta($post_id, $content_key, $thrive);
            update_post_meta($post_id, $before_more_key, $thrive_before);
        }
    }
}
