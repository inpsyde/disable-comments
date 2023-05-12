<?php

/*
 * This file is part of the "inpsyde-disable-comments" package.
 *
 * Copyright (C) 2023 Inpsyde GmbH
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

declare(strict_types=1);

namespace Inpsyde;

final class CommentsDisabler
{
    /**
     * @var list<non-empty-string>
     */
    private const COMMENT_SCREENS = [
        'comment.php',
        'edit-comments.php',
        'moderation.php',
        'options-discussion.php',
    ];

    private bool $initialized = false;

    /**
     * @param string $basename
     * @param string $templatesPath
     * @return CommentsDisabler
     */
    public static function new(string $basename, string $templatesPath): CommentsDisabler
    {
        return new self($basename, $templatesPath);
    }

    /**
     * @param string $basename
     * @param string $templatesPath
     */
    private function __construct(private string $basename, private string $templatesPath)
    {
    }

    /**
     * @return void
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;

        $addAction = function (string $action, string $method, int $priority = PHP_INT_MAX): void {
            /**
             * @psalm-suppress MissingClosureParamType
             * @psalm-suppress MissingClosureReturnType
             */
            add_action($action, fn(...$args) => $this->{$method}(...$args), $priority);
        };
        $addFilter = function (string $filter, string $method, int $args = 1): void {
            /**
             * @psalm-suppress MissingClosureParamType
             * @psalm-suppress MissingClosureReturnType
             */
            add_filter($filter, fn(...$args) => $this->{$method}(...$args), PHP_INT_MAX, $args);
        };

        $addAction('registered_post_type', 'removePostTypeSupport');
        $addAction('init', 'deregisterCommentReplyScript');
        $addAction('init', 'preventBlockRegistration', 9);
        $addAction('pre_get_posts', 'removeFromQueryVars');
        $addAction('the_post', 'forceCommentsClosedOnRead');
        $addAction('pre_comment_on_post', 'throwOnPreComment');
        $addAction('admin_init', 'filterOptionsAndMetaboxes');
        $addAction('admin_menu', 'removeFromAdminMenu');
        $addAction('admin_bar_menu', 'removeFromAdminBar');
        $addAction('widgets_init', 'unregisterWidget');
        $addAction('admin_footer-index.php', 'removeDashboardCommentsDomNodes');
        $addAction('personal_options', 'removeKeyboardShortcutsOptionFromProfile');
        $addAction('admin_print_footer_scripts', 'removeDiscussionEditorPanel');
        $addAction('template_redirect', 'redirectCommentFeed');
        $addAction('comment_form_comments_closed', 'removeCommentsClosedActions', PHP_INT_MIN);

        $addFilter('the_posts', 'closeCommentsForQueries', 2);
        $addFilter('wp_insert_post_data', 'forceCommentsClosedOnSave', 2);
        $addFilter('rest_endpoints', 'removeFromRest');
        $addFilter('comments_template', 'commentsTemplate');
        $addFilter('wp_headers', 'removePingbackHeader');
        $addFilter('xmlrpc_methods', 'replaceXmlrpcMethods');
        $addFilter('rewrite_rules_array', 'filterRewriteRules');
        $addFilter('wp_count_comments', 'filterCountComments');
        $addFilter('allowed_block_types_all', 'disableBlocks');

        add_filter('comments_open', '__return_false', PHP_INT_MAX);
        add_filter('pings_open', '__return_false', PHP_INT_MAX);
        add_filter('comments_pre_query', '__return_empty_array', PHP_INT_MAX);
        add_filter('feed_links_show_comments_feed', '__return_false', PHP_INT_MAX);
        add_filter('feed_links_extra_show_post_comments_feed', '__return_false', PHP_INT_MAX);
        add_filter('post_comments_feed_link', '__return_empty_string', PHP_INT_MAX);
        add_filter('get_comments_number', '__return_zero', PHP_INT_MAX);
        add_filter('get_comments_link', '__return_empty_string', PHP_INT_MAX);
        add_filter('respond_link', '__return_empty_string', PHP_INT_MAX);
        add_filter('comments_rewrite_rules', '__return_empty_array', PHP_INT_MAX);
        add_filter('notify_post_author', '__return_false', PHP_INT_MAX);
    }

    /**
     * @return void
     *
     * @wp-hook init
     */
    private function deregisterCommentReplyScript(): void
    {
        wp_scripts()->remove('comment-reply');
        add_action(
            'wp_enqueue_scripts',
            static function (): void {
                $wpScripts = wp_scripts();
                $wpScripts->remove('comment-reply');
                $wpScripts->dequeue('comment-reply');
            },
            PHP_INT_MIN
        );
    }

    /**
     * @return void
     *
     * @wp-hook init
     */
    private function preventBlockRegistration(): void
    {
        remove_action('init', 'register_block_core_latest_comments');
        remove_action('init', 'register_block_core_comment_author_name');
        remove_action('init', 'register_block_core_comment_content');
        remove_action('init', 'register_block_core_comment_date');
        remove_action('init', 'register_block_core_comment_edit_link');
        remove_action('init', 'register_block_core_comment_reply_link');
        remove_action('init', 'register_block_core_comment_template');
        remove_action('init', 'register_block_core_comments_pagination_next');
        remove_action('init', 'register_block_core_comments_pagination_numbers');
        remove_action('init', 'register_block_core_comments_pagination_previous');
        remove_action('init', 'register_block_core_comments_pagination');
        remove_action('init', 'register_block_core_comments_title');
        remove_action('init', 'register_block_core_comments');
        remove_action('init', 'register_block_core_post_comments_form');
    }

    /**
     * @param mixed $posts
     * @param mixed $query
     * @return mixed
     *
     * @wp-hook the_posts
     */
    private function closeCommentsForQueries(mixed $posts, mixed $query): mixed
    {
        if (!is_array($posts) || ($posts === [])) {
            return $posts;
        }

        $newPosts = [];
        foreach ($posts as $post) {
            if ($post instanceof \WP_Post) {
                $post->comment_status = 'closed';
                $post->ping_status = 'closed';
                $newPosts[] = $post;
            }
        }
        if ($query instanceof \WP_Query) {
            $query->posts = $newPosts;
            $query->comments = [];
            /** @psalm-suppress PossiblyNullPropertyAssignmentValue */
            $query->comment = null;
        }

        return $newPosts;
    }

    /**
     * @param mixed $newData
     * @param mixed $currentData
     * @return array
     *
     * @wp-hook wp_insert_post_data
     */
    private function forceCommentsClosedOnSave(mixed $newData, mixed $currentData): array
    {
        if (!is_array($newData) || !is_array($currentData)) {
            return is_array($currentData) ? $currentData : [];
        }

        $newData['comment_status'] = 'closed';
        $newData['ping_status'] = 'closed';

        return $newData;
    }

    /**
     * @param mixed $endpoints
     * @return array
     *
     * @wp-hook rest_endpoints
     */
    private function removeFromRest(mixed $endpoints): array
    {
        if (!is_array($endpoints)) {
            return [];
        }
        $filtered = [];
        foreach ($endpoints as $path => $endpoint) {
            if (is_string($path) && preg_match('~^/wp/v[0-9]+/comment~i', $path) !== 1) {
                $filtered[$path] = $endpoint;
            }
        }

        return $filtered;
    }

    /**
     * @param mixed $query
     * @return void
     *
     * @wp-hook pre_get_posts
     */
    private function removeFromQueryVars(mixed $query): void
    {
        if ($query instanceof \WP_Query) {
            $query->is_comment_feed = false;
            $query->is_trackback = false;
            $query->set('comments_per_page', -1);
            $query->set('comment_count', null);
            $query->set('comment_status', null);
        }
    }

    /**
     * @param mixed $post
     * @return void
     *
     * @wp-hook the_post
     */
    public function forceCommentsClosedOnRead(mixed $post): void
    {
        if ($post instanceof \WP_Post) {
            $post->comment_status = 'closed';
            $post->ping_status = 'closed';
        }
    }

    /**
     * @return void
     *
     * @wp-hook pre_comment_on_post
     */
    public function throwOnPreComment(): void
    {
        throw new \Error(esc_html__('Something went wrong.'));
    }

    /**
     * @param mixed $postType
     * @return void
     *
     * @wp-hook registered_post_type
     */
    private function removePostTypeSupport(mixed $postType): void
    {
        if (is_string($postType)) {
            remove_post_type_support($postType, 'comments');
        }
    }

    /**
     * @return void
     *
     * @wp-hook admin_init
     */
    private function filterOptionsAndMetaboxes(): void
    {
        $closed = static fn(): string => 'closed';

        add_filter('pre_option_comments_notify', '__return_zero');
        add_filter('default_pingback_flag', '__return_zero');
        add_filter('default_default_comment_status', $closed);
        add_filter('default_default_ping_status', $closed);

        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        foreach (get_post_types() as $postType) {
            if (!is_string($postType)) {
                continue;
            }
            remove_meta_box('commentstatusdiv', $postType, 'normal');
            remove_meta_box('commentsdiv', $postType, 'normal');
            remove_meta_box('trackbacksdiv', $postType, 'normal');
        }

        global $pagenow;
        if (in_array($pagenow, self::COMMENT_SCREENS, true)) {
            wp_die(esc_html__('Something went wrong.'), 403);
        }
    }

    /**
     * @return void
     *
     * @wp-hook admin_menu
     */
    private function removeFromAdminMenu(): void
    {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * @return void
     *
     * @wp-hook admin_footer-index.php
     */
    private function removeDashboardCommentsDomNodes(): void
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('.welcome-comments').parent().remove();
                $('div.table_discussion:first').remove();
                $('#dash-right-now, #dashboard_right_now').find('.comment-count').remove();
                $('#latest-comments').remove();
            });
        </script>
        <?php
    }

    /**
     * @param mixed $adminBar
     * @return void
     *
     * @wp-hook admin_bar_menu
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private function removeFromAdminBar(mixed $adminBar): void
    {
        if (!($adminBar instanceof \WP_Admin_Bar)) {
            return;
        }
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
        $adminBar->remove_node('comments');

        if (is_scalar($GLOBALS['blog_id'] ?? null)) {
            $adminBar->remove_node(sprintf('blog-%s-c', (string)$GLOBALS['blog_id']));
        }

        if (!is_multisite()) {
            return;
        }

        if (!function_exists('is_plugin_active_for_network') && defined('ABSPATH')) {
            require_once(\ABSPATH . '/wp-admin/includes/plugin.php');
        }

        /** @psalm-suppress MixedPropertyFetch */
        $blogs = is_plugin_active_for_network($this->basename)
            ? ($adminBar->user->blogs ?? null)
            : null;
        if (!is_array($blogs)) {
            return;
        }
        foreach ($blogs as $blog) {
            if (is_object($blog) && is_scalar($blog->userblog_id ?? null)) {
                $adminBar->remove_node(sprintf('blog-%s-c', (string)$blog->userblog_id));
            }
        }
    }

    /**
     * @return void
     *
     * @wp-hook template_redirect
     */
    private function redirectCommentFeed(): void
    {
        if (!is_comment_feed()) {
            return;
        }

        if (isset($_GET['feed'])) { // phpcs:ignore
            wp_safe_redirect(remove_query_arg('feed'), 301);
            exit();
        }

        set_query_var('feed', '');
    }

    /**
     * @return void
     *
     * @wp-hook comment_form_comments_closed
     */
    private function removeCommentsClosedActions(): void
    {
        remove_all_actions('comment_form_comments_closed');
    }

    /**
     * @param mixed $headers
     * @return array
     *
     * @wp-hook wp_headers
     */
    private function removePingbackHeader(mixed $headers): array
    {
        if (!is_array($headers)) {
            return [];
        }

        $filtered = [];
        foreach ($headers as $name => $value) {
            if (strtolower((string)$name) !== 'x-pingback') {
                $filtered[$name] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @return void
     *
     * @wp-hook widgets_init
     */
    private function unregisterWidget(): void
    {
        unregister_widget('WP_Widget_Recent_Comments');
    }

    /**
     * @return void
     *
     * @wp-hook personal_options
     */
    private function removeKeyboardShortcutsOptionFromProfile(): void
    {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#comment_shortcuts').closest('tr').remove();
            });
        </script>
        <?php
    }

    /**
     * @return string
     *
     * @wp-hook comments_template
     */
    private function commentsTemplate(): string
    {
        return trailingslashit($this->templatesPath) . 'comments-template.php';
    }

    /**
     * @param mixed $methods
     * @return array
     *
     * @wp-hook xmlrpc_methods
     */
    private function replaceXmlrpcMethods(mixed $methods): array
    {
        if (!is_array($methods)) {
            return [];
        }

        return array_replace(
            $methods,
            [
                'wp.getCommentCount' => ['', ''],
                'wp.getComment' => ['', ''],
                'wp.getComments' => ['', ''],
                'wp.deleteComment' => ['', ''],
                'wp.editComment' => ['', ''],
                'wp.newComment' => ['', ''],
                'wp.getCommentStatusList' => ['', ''],
            ]
        );
    }

    /**
     * @return void
     *
     * @wp-hook admin_print_footer_scripts
     */
    private static function removeDiscussionEditorPanel(): void
    {
        ?>
        <script>
        (function (wp) {
            if (wp && (typeof wp.domReady === 'function') && (typeof wp.data !== 'undefined')) {
                wp.domReady(() => {
                    const editingPost = wp.data.dispatch('core/edit-post');
                    if (editingPost && (typeof editingPost.removeEditorPanel === 'function')) {
                        editingPost.removeEditorPanel('discussion-panel');
                    }
                });
            }
        })(window.wp || null);
        </script>
        <?php
    }

    /**
     * @param mixed $rules
     * @return mixed
     *
     * @wp-hook rewrite_rules_array
     */
    private function filterRewriteRules(mixed $rules): array
    {
        if (!is_array($rules)) {
            return [];
        }

        foreach ($rules as $key => $value) {
            if (str_contains((string)$key, '|commentsrss2')) {
                unset($rules[$key]);
                $rules[str_replace('|commentsrss2', '', (string)$key)] = $value;
            }
        }

        foreach ($rules as $key => $value) {
            if (str_contains((string)$key, 'comment-page-')) {
                unset($rules[$key]);
            }
        }

        return $rules;
    }

    /**
     * @return object
     *
     * @wp-hook wp_count_comments
     */
    private function filterCountComments(): object
    {
        return (object)[
            'approved' => 0,
            'spam' => 0,
            'trash' => 0,
            'post-trashed' => 0,
            'total_comments' => 0,
            'all' => 0,
            'moderated' => 0,
        ];
    }

    /**
     * @param mixed $enabled
     * @return bool|array
     *
     * @wp-hook allowed_block_types_all
     */
    private function disableBlocks(mixed $enabled): bool|array
    {
        if ($enabled === false) {
            return false;
        }

        is_array($enabled) or $enabled = [];
        $filtered = [];
        $registered = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
        foreach ($registered as $blockName) {
            if ($enabled && !in_array($blockName, $enabled, true)) {
                continue;
            }
            if (preg_match('~^core/.*?comment.*?~i', (string)$blockName) !== 1) {
                $filtered[] = $blockName;
            }
        }

        return $filtered;
    }
}
