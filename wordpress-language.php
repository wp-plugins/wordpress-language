<?php
/*
Plugin Name: Wordpress Language
Plugin URI: http://wpml.org/documentation/related-projects/wordpress-language/
Description: Easily switch languages and download translations for WordPress. <a href="http://wpml.org/documentation/related-projects/wordpress-language/">Documentation</a>.
Author: ICanLocalize
Author URI: http://wpml.org
Version: 1.1.1
*/

if(defined('WP_LANGUAGE_VERSION')) return;

define('WP_LANGUAGE_VERSION', '1.1.1');
define('WP_LANG_PATH', dirname(__FILE__));
define('WP_LANG_FOLDER', basename(WP_LANG_PATH));
define('WP_LANG_URL', plugins_url() . '/' . WP_LANG_FOLDER);

global $sitepress;

if (!isset($sitepress)) {

    require_once WP_LANG_PATH . '/inc/wp_trans_class.php';
    require_once WP_LANG_PATH . '/inc/admin_bar_lang_selector.php';
    require_once WP_LANG_PATH . '/inc/schema.php';
    require_once WP_LANG_PATH . '/inc/auto-download-locales.php';
    
    $WordPress_language = new WordPress_language_class();
    
    register_activation_hook( __FILE__, 'wp_language_activate' );
    if ( function_exists('is_multisite') && is_multisite() ) {
        if (!get_option('wordpress-language-activated', false)) {
            wp_language_activate();
        }
    }
    
} else {
    function wp_lang_show_wpml_message() {
        echo '<div id="wp_lang_wpml_message" class="error">';
        echo sprintf(__('WordPress Language and WPML should not run together. Please %sdeactivate the WordPress Language plugin%s.', 'wordpress-language'),
                        '<a href="' . admin_url('plugins.php?s=WordPress+language') . '">',
                        '</a>');
        echo '</div>';
    }
    add_action('admin_notices', 'wp_lang_show_wpml_message');
}
