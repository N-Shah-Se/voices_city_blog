<?php

/**
 * PageBuilder by Site Origin editor
 *
 * Class Wpil_Editor_Origin
 */
class Wpil_Editor_Origin
{
    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        $data = get_post_meta($post_id, 'panels_data', true);

        if (!empty($data['widgets'])) {
            foreach ($meta as $link) {
                foreach($data['widgets'] as $key => $widget) {
                    if (!empty($widget['text']) && strpos($widget['text'], $link['sentence']) !== false) {
                        $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                        $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                        $changed_sentence = str_replace('"', "'", $changed_sentence);
                        Wpil_Post::insertLink($data['widgets'][$key]['text'], $link['sentence'], $changed_sentence, $force_insert);
                    }
                }
            }

            update_post_meta($post_id, 'panels_data', $data);
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
        $data = get_post_meta($post_id, 'panels_data', true);

        if (!empty($data['widgets'])) {
            foreach($data['widgets'] as $key => $widget) {
                if (!empty($widget['text'])) {
                    preg_match('|<a .+'.$url.'.+>'.$anchor.'</a>|i', $widget['text'],  $matches);
                    if (!empty($matches[0])) {
                        $data['widgets'][$key]['text'] = preg_replace('|<a [^>]+'.$url.'[^>]+>'.$anchor.'</a>|i', $anchor,  $widget['text']);
                    }
                }
            }

            update_post_meta($post_id, 'panels_data', $data);
        }
    }
}