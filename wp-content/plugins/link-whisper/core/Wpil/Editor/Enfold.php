<?php

/**
 * Enfold (Avia) editor
 *
 * Class Wpil_Editor_Enfold
 */
class Wpil_Editor_Enfold
{
    /**
     * Add links
     *
     * @param $meta
     * @param $post_id
     */
    public static function addLinks($meta, $post_id, &$content)
    {
        if (defined('AV_FRAMEWORK_VERSION') && 'active' === get_post_meta($post_id, '_aviaLayoutBuilder_active', true)) {
            $enfold_content = get_post_meta($post_id, '_aviaLayoutBuilderCleanData', true);
            foreach ($meta as $link) {
                $force_insert = (isset($link['keyword_data']) && !empty($link['keyword_data']->force_insert)) ? true: false;
                $changed_sentence = Wpil_Post::getSentenceWithAnchor($link);
                if (strpos($enfold_content, $link['sentence']) === false) {
                    $link['sentence'] = addslashes($link['sentence']);
                }
                Wpil_Post::insertLink($enfold_content, $link['sentence'], $changed_sentence, $force_insert);
            }

            update_post_meta($post_id, '_aviaLayoutBuilderCleanData', $enfold_content);
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
        if (defined('AV_FRAMEWORK_VERSION') && 'active' === get_post_meta($post_id, '_aviaLayoutBuilder_active', true)) {
            $enfold_content = get_post_meta($post_id, '_aviaLayoutBuilderCleanData', true);

            preg_match('|<a .+'.$url.'.+>'.$anchor.'</a>|i', $enfold_content,  $matches);
            if (!empty($matches[0])) {
                $url = addslashes($url);
                $anchor = addslashes($anchor);
            }

            $enfold_content = preg_replace('|<a [^>]+'.$url.'[^>]+>'.$anchor.'</a>|i', $anchor,  $enfold_content);

            update_post_meta($post_id, '_aviaLayoutBuilderCleanData', $enfold_content);
        }
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
        if (defined('AV_FRAMEWORK_VERSION') && 'active' === get_post_meta($post_id, '_aviaLayoutBuilder_active', true)) {
            $enfold_content = get_post_meta($post_id, '_aviaLayoutBuilderCleanData', true);

            $matches = Wpil_Keyword::findKeywordLinks($keyword, $enfold_content);
            if (!empty($matches[0])) {
                $keyword->link = addslashes($keyword->link);
                $keyword->keyword = addslashes($keyword->keyword);
            }

            if ($left_one) {
                Wpil_Keyword::removeNonFirstLinks($keyword, $enfold_content);
            } else {
                Wpil_Keyword::removeAllLinks($keyword, $enfold_content);
            }

            update_post_meta($post_id, '_aviaLayoutBuilderCleanData', $enfold_content);
        }
    }

    /**
     * Replace URLs
     *
     * @param $post
     * @param $url
     */
    public static function replaceURLs($post, $url)
    {
        if (defined('AV_FRAMEWORK_VERSION') && 'active' === get_post_meta($post->id, '_aviaLayoutBuilder_active', true)) {
            $enfold_content = get_post_meta($post->id, '_aviaLayoutBuilderCleanData', true);

            Wpil_URLChanger::replaceLink($enfold_content, $url);
            update_post_meta($post->id, '_aviaLayoutBuilderCleanData', $enfold_content);
        }
    }

    /**
     * Revert URLs
     *
     * @param $post
     * @param $url
     */
    public static function revertURLs($post, $url)
    {
        if (defined('AV_FRAMEWORK_VERSION') && 'active' === get_post_meta($post->id, '_aviaLayoutBuilder_active', true)) {
            $enfold_content = get_post_meta($post->id, '_aviaLayoutBuilderCleanData', true);
            Wpil_URLChanger::revertURL($enfold_content, $url);

            update_post_meta($post->id, '_aviaLayoutBuilderCleanData', $enfold_content);
        }
    }
}