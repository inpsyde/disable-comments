<?php

/**
 * @wordpress-plugin
 * Plugin Name: Inpsyde Disable Comments
 * Description: Entirely ditches comments as a WordPress feature.
 * Version: 1.0.3
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Update URI: false
 * Plugin URI: https://github.com/inpsyde/disable-comments
 * GitHub Plugin URI: https://github.com/inpsyde/disable-comments
 * Primary Branch: main
 */

declare(strict_types=1);

(static function (): void {
    require_once __DIR__ . '/src/CommentsDisabler.php';
    $basename = plugin_basename(__FILE__);
    $tplPath = wp_normalize_path(__DIR__ . '/resources/templates');
    Inpsyde\CommentsDisabler::new($basename, $tplPath)->init();
})();
