<?php

require_once(WP_LANG_PATH . '/inc/wp_languages_class.php');

class WordPress_language_class {
    function __construct(){
        
        add_action('init', array($this, 'init'));
        add_filter('locale', array($this, 'locale'));
        
        $this->language_switched = false;
        $this->download_lang = null;
        
        $this->languages = new WP_languages();

        add_filter('gettext_with_context', array($this, 'icl_sw_filters_gettext_with_context'), 9, 4);
        
        $this->settings = get_option('wp_languages_options');
        $this->current_scope = 'back-end';
    }
    
    function init() {

        $this->plugin_localization();
        
        if(is_admin()){
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_footer', array($this, 'render_lang_switch_popup'));
            add_action('admin_head', array($this, 'render_lang_switch_js'));
            add_action('wp_ajax_wp_language_get_info', array($this, 'ajax_get_lang_info'));
            add_action('wp_ajax_wp_install_language', array($this, 'ajax_install_language'));
            add_action('wp_ajax_wp_language_check_for_updates', array($this, 'ajax_check_for_updates'));
            add_action('wp_ajax_wp_show_hide_language_selector', array($this, 'ajax_show_hide_language_selector'));
            add_action('wp_ajax_wp_languages_distinct_languages', array($this, 'ajax_wp_languages_distinct_languages'));
            
            add_thickbox();
            
            if(isset($_GET['page']) && $_GET['page'] == 'wordpress_language'){
                wp_enqueue_script('wp-trans', WP_LANG_URL . '/res/js/wp-trans.js', 'jquery', WP_LANGUAGE_VERSION);
                if(isset($_GET['scope']) && $_GET['scope']=='front-end'){
                    $this->current_scope = 'front-end';
                }
            }
            
            
            
        }
        
        add_filter('gettext', array($this, 'icl_sw_filters_gettext'), 9, 3);
        
        
    }

    function save_selected_locale() {
        if(is_admin() && function_exists('wp_verify_nonce')){
            global $pagenow, $wpdb;
            static $save_started = false;
            
            if (!$save_started) {
                $save_started = true;
                if ($pagenow == 'options-general.php' &&
                        isset($_GET['page']) &&
                        $_GET['page'] == 'wordpress_language' &&
                        isset($_GET['switch_to']) &&
                        isset($_GET['_wpnonce'])) {
                    if (wp_verify_nonce($_GET['_wpnonce'], 'wp_lang_get_lang_info')) {
                        if(isset($_GET['scope']) && $_GET['scope'] == 'front-end'){
                            update_option('wp_language_locale_front', $_GET['switch_to']);
                        }else{
                            update_option('wp_language_locale', $_GET['switch_to']);    
                        }                        
                        $this->language_switched = true;
                    }
                }
            }
        }
    }
        
    function locale($locale) {
        $this->save_selected_locale();
        
        if($this->settings['different_languages'] && !is_admin()){
            $stored_locale = get_option('wp_language_locale_front');    
        }else{
            $stored_locale = get_option('wp_language_locale');
        }
        
        
        if ($this->download_lang) {
            $stored_locale = $this->get_locale($this->download_lang);
        }
        
        
        if ($stored_locale != '') {
            return $stored_locale;
        } else {
            return $locale;
        }
    }
    
    // Localization
    function plugin_localization(){
        $locale = get_locale();
        load_textdomain( 'wordpress-language', WP_LANG_PATH . '/locale/wordpress-language-' . $locale . '.mo');
    }
    
    function admin_menu(){

        add_submenu_page('options-general.php',
                            __('Language', 'wordpress-language'),
                            __('Language', 'wordpress-language'),
                            'manage_options',
                            'wordpress_language',
                            array($this, 'language_menu'));
    }
    
    function language_menu() {
        
        $icon_url = WP_LANG_URL . '/res/img/wplang_2_32_c.jpg';
        
        global $lang_locales;
        
        $different_languages = isset($this->settings['different_languages']) ? $this->settings['different_languages'] : 0;

        $current_locale_front = get_option('wp_language_locale_front');    
        if(empty($current_locale_front)){
            $current_locale_front = get_locale();
        }
        
        if($different_languages){
            if($this->current_scope == 'front-end'){
                $current_locale = $current_locale_front;    
            }else{
                $current_locale = get_locale();    
            }
        }else{
            $current_locale = get_locale();    
        }
        
        $current_lang_code_front = $this->get_lang_code($current_locale_front);
        $current_lang_code_back  = $this->get_lang_code(get_locale());
        
        $current_lang_code = $this->get_lang_code($current_locale);
        $current_lang = $this->get_own_lang_name($current_lang_code);
        
        ?>
            <div class="icon32" style='background:url("<?php echo $icon_url; ?>") no-repeat;'><br /></div>
            <h2><?php echo __('WordPress Language', 'wordpress-language'); ?></h2>

            <br />
            <?php $show_lang_switcher = get_option('wp_language_show_switcher', 'on') == 'on' ? ' checked="checked"' : '';?>
            <p style="margin: 10px 0;">
            <label><input type="checkbox" id="wp_lang_show_hide_selector" <?php echo $show_lang_switcher; ?> onclick="wp_lang_show_hide_selector();"/> <?php echo __('Show a language selector in the WordPress admin bar', 'wordpress-language'); ?></label>
            </p>
            
            <p style="margin:0">
            <label class="menu-name-label">
                <input name="different_languages" type="radio" value="0" <?php if(!$different_languages): ?>checked="checked"<?php endif; ?> />&nbsp;
                <?php _e('Use the same language for WordPress admin and public pages.', 'wordpress-language'); ?>
            </label>            
            </p>    
            <p style="margin:5px 0 0 0">
            <label class="menu-name-label">
                <input name="different_languages" type="radio" value="1" <?php if($different_languages): ?>checked="checked"<?php endif; ?> />&nbsp;
                <?php _e('Use different languages for WordPress admin and public pages.', 'wordpress-language'); ?>
            </label>
            </p>
            <br clear="all" />
            
            <div id="menu-management-liquid">
                <div id="menu-management" style="margin-right:10px;width:auto;">            
                    <div class="nav-tabs-nav" <?php if(empty($different_languages)): ?>style="display: none;"<?php endif; ?>>
                        <div class="nav-tabs-wrapper">
                            <div class="nav-tabs" style="padding: 0px; margin-right: -46px; margin-left: 0px;">
                                <span id="nav-tab-back-end" class="nav-tab<?php if($this->current_scope == 'back-end'): ?> nav-tab-active<?php endif ?>">
                                    <?php echo $this->get_flag($current_lang_code_back) ?>
                                    <?php if($this->current_scope != 'back-end'): ?>
                                        <a style="text-decoration: none" href="<?php echo admin_url('options-general.php?page=wordpress_language') ?>"><?php endif; ?>
                                        <?php _e('Language for WordPress admin', 'wordpress-language') ?>
                                    <?php if($this->current_scope != 'back-end'): ?></a><?php endif; ?>                                        
                                </span>
                                <span id="nav-tab-front-end" class="nav-tab<?php if($this->current_scope == 'front-end'): ?> nav-tab-active<?php endif ?>">
                                    <?php echo $this->get_flag($current_lang_code_front) ?>
                                    <?php if($this->current_scope != 'front-end'): ?>
                                        <a style="text-decoration: none" href="<?php echo admin_url('options-general.php?page=wordpress_language&scope=front-end') ?>"><?php endif; ?>
                                        <?php _e('Language for public pages', 'wordpress-language') ?>
                                    <?php if($this->current_scope != 'front-end'): ?></a><?php endif; ?>                                        
                                </span>                                
                            </div>
                        </div>
                    </div>
                    <div class="menu-edit" <?php if(version_compare($GLOBALS['wp_version'], '3.2.1', '<=')): ?>style="border-style:solid;border-radius:3px;border-width:1px;border-color:#DFDFDF;<?php endif;?>">
                        <div id="nav-menu-header">
                            &nbsp;                        
                        </div>
                        <div id="post-body" style="border-style: solid;border-width: 1px 0;padding: 10px;">
                            <div id="post-body-content">
                                <p><?php echo sprintf(__('Current language is %s. Current locale is %s.', 'wordpress-language'), 
                                        $this->get_flag($current_lang_code) . $current_lang, $current_locale) ?></p>
                                <?php 
                                    if (isset($_GET['download_complete'])) {
                                        $this->download_complete_div($current_lang, $current_locale, true);
                                    }
                                    if (isset($_GET['no_translation_available'])) {
                                        $this->no_translation_available_div($current_lang, $current_locale); 
                                    }
                                    
                                    if (isset($_GET['update_lang']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wp_lang_get_lang_info')) {
                                        $update_lang = true;
                                    } else {
                                        $update_lang = false;
                                    }
                                    
                                    $ST_MO_Downloader = new ST_MO_Downloader();
                                    $wptranslations = $ST_MO_Downloader->get_option('translations');
                                    
                                    $installing_translations = false;
                                    if ($this->language_switched || $update_lang) {
                                        // check if we need to download translations.

                                        if (!$ST_MO_Downloader->is_locale_installed($current_locale)) {
                                            
                                            $installing_translations = true;

                                            $this->downloading_div();
                                            ?>
                                            
                                            <?php $this->download_complete_div($current_lang, $current_locale, false); ?>
                                            <script type="text/javascript">
                                                jQuery(document).ready(function() {
                                                    jQuery.ajax({
                                                        url: ajaxurl,
                                                        type: 'post',
                                                        data: 'action=wp_install_language&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val() + '&scope=<?php echo $this->current_scope ?>',
                                                        cache: false,
                                                        beforeSend: function () {
                                                            
                                                        },
                                                        success: function (data) {
                                                            if (data == '1') {
                                                                jQuery('#wp_language_downloading').hide();
                                                                jQuery('#wp_language_download_complete').fadeIn('slow');
                                                                window.location = '<?php echo admin_url('options-general.php?page=wordpress_language&scope=' . $this->current_scope . '&download_complete=1'); ?>';
                                                            } else {
                                                                window.location = '<?php echo admin_url('options-general.php?page=wordpress_language&scope=' . $this->current_scope . '&no_translation_available=1'); ?>';
                                                            }
                                                        }
                                                    });
                                                    
                                                });
                                            
                                            </script>
                                            
                                            <?php
                                            
                                        }

                                    }
                                    
                                    if (!$installing_translations) {
                                        
                                        if (isset($wptranslations[$current_locale]['installed'])) {
                                            echo '<p>' . sprintf(__('Current translation is from %s', 'wordpress-language'), date("F j, Y @H:i", $wptranslations[$current_locale]['time'])) . '</p>';
                                            ?>
                                            <div id="wp_language_translation_state">
                                                <?php echo $this->current_translation_state($current_lang_code, $current_locale, $wptranslations); ?>
                                            </div>
                                            <?php
                                        }
                                    
                                        $langs = $this->get_languages(true);
                                
                                        $more_langs_on = isset($_GET['more_langs']) && $_GET['more_langs'] == 1;    
                                        ?>

                                        
                                        <a id="wp_lang_change_lang_button" href="#" <?php
                                             if($more_langs_on): ?> style="display:none"<?php endif; ?>><?php _e('Change language', 'wordpress-language'); ?></a>
                                        <div id="wp_lang_change_lang"<?php if(!$more_langs_on): ?> style="display:none"<?php endif; ?>>
                                        
                                        
                                        <br /><strong><?php echo __('Select a language', 'wordpress-language'); ?></strong>
                                    
                                        <div style="padding:10px;">
                                            <div class="wp_lang_thickbox" style="padding-bottom:10px">
                                                <table cellpadding="3">
                                                    <tr>
                                                    <?php
                                                        $count = 0;
                                                        foreach ($langs as $lang) {
                                                            if ($count != 0 && !($count % 3)) {
                                                                echo '</tr><tr>';
                                                            }
                                                            $link = '#TB_inline?height=255&width=750&inlineId=wp_lang_switch_popup&modal=true';
                                                            $link .= '&switch_to=' . $lang['default_locale'];
                                                            $link .= '&scope=' . $this->current_scope ;
                                                            
                                                            echo '<td>' . $this->get_flag($lang['code'])  . '<a href="' . $link . '">' . $this->get_lang_name_in_current_locale($lang['code']) . ' (' . $this->get_own_lang_name($lang['code']) . ')</a></td>';
                                                            $count++;
                                                        }
                                                    ?>
                                                    </tr>
                                                </table>
                                            </div>
                                            <a id="wp_lang_change_lang_cancel" href="#" class="button-secondary"><?php echo __('Cancel', 'wordpress-language'); ?></a>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                ?>
                                
                            </div>
                            <br clear="all" />
                        </div>
                        <div id="nav-menu-footer">
                            &nbsp;     
                        </div>                        
                    </div>
                </div>
            </div>
            
            <br />
            <div id="wp_language_wpml_promotion" style="clear: both;">
                <?php echo $this->wpml_promotion(); ?>
            </div>
            
            
        <?php
        
        
        
        
    }

    function current_translation_state($current_lang_code, $current_locale, $wptranslations, $ajax = false) {
    
        if ($wptranslations[$current_locale]['installed'] != $wptranslations[$current_locale]['available']) {
            
            echo '<p>' . __('Updated translation is available.', 'wordpress-language')  . '</p>';
            $nonce = wp_create_nonce('wp_lang_get_lang_info');
            $url = admin_url('options-general.php?page=wordpress_language&update_lang=1&scope='.$this->current_scope.'&_wpnonce=' . $nonce);
            echo ' <a href="' . $url . '" class="button-secondary">' . __('Update now', 'wordpress-language') . '</a><br clear="all" /><br />';
        } else {
            if ($ajax) {
                ?>
                    <p><strong><?php echo __('Your site\'s translation is up-to-date.', 'wordpress-language');?></strong> <img src="<?php echo WP_LANG_URL; ?>/res/img/checkmark-green.png"></p>
                <?php
            } else {
                ?>
                <div id="wordpress_language_update_to_date">
                    <p><?php echo __('Your site\'s translation is up-to-date.', 'wordpress-language'); ?> <a href="#" onclick="wp_lang_check_for_updates(); return false"><?php echo __('check for updates', 'wordpress-language'); ?></a></p>
                </div>
                <div id="wordpress_language_check_for_updates" style="display:none">
                    <p><strong><?php echo __('Checking for updates...', 'wordpress-language'); ?></strong> <img src="<?php echo WP_LANG_URL; ?>/res/img/ajax-loader-big.gif"></p>
                </div>
                <script type="text/javascript">
                    function wp_lang_check_for_updates() {
                        jQuery('#wordpress_language_update_to_date').hide();
                        jQuery('#wordpress_language_check_for_updates').show();
                        
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'post',
                            data: 'action=wp_language_check_for_updates&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val() + '&scope=<?php echo $this->current_scope ?>',
                            cache: false,
                            beforeSend: function () {
                                
                            },
                            success: function (data) {
                                jQuery('#wp_language_translation_state').html(data);
                            }
                        });
                        
                    }
                </script>
                <?php
            }
        }
    }

    function downloading_div() {    
        if($this->current_scope == 'front-end'){
            $current_locale = get_option('wp_language_locale_front');    
        }else{
            $current_locale = get_locale();
        }
        
        
        if($current_locale == 'en_US') return;
        
        $current_lang_code = $this->get_lang_code($current_locale);
        $current_lang = $this->get_own_lang_name($current_lang_code);
        ?>
            <div id="wp_language_downloading">
                <strong>
                    <?php echo sprintf(__('Downloading and installing %s translations', 'wordpress-language'), $current_lang . ' (' . $current_locale . ')'); ?>
                </strong>
                <img src="<?php echo WP_LANG_URL; ?>/res/img/ajax-loader-big.gif">
            </div>
        <?php
    }
    
    function download_complete_div($current_lang, $current_locale, $show) {
        ?>
        
        <div id="wp_language_download_complete" <?php if (!$show) {echo 'style="display:none"';} ?>>
            <strong>
                <?php echo sprintf(__('Translations for %s installed.', 'wordpress-language'), $current_lang . ' (' . $current_locale . ')'); ?>
            </strong>
            <img src="<?php echo WP_LANG_URL; ?>/res/img/checkmark-green.png">
        </div>
        <br />
        
        <?php
    }
        
    function no_translation_available_div($current_lang, $current_locale) {
        if ($current_locale != 'en_US') {
            ?>
            
            <div id="wp_language_no_translation_available">
                <img src="<?php echo WP_LANG_URL; ?>/res/img/alert.png">
                <strong>
                    <?php echo sprintf(__('There is no translation available for %s.', 'wordpress-language'), $current_lang . ' (' . $current_locale . ')'); ?>
                </strong>
            </div>
            <br />
            
            <?php
        }
    }
        
    function get_lang_code($find_locale) {
        return $this->languages->get_lang_code($find_locale);    
        
    }
    
    function get_flag($lang_code) {
        return '<img src="' . WP_LANG_URL . '/res/flags/' . $lang_code . '.png" width="18" height="12" alt="' . $lang_code . '" /> ';
    }
    
    function get_locale($lang_code) {
        return $this->languages->get_locale($lang_code);
    }
    
    function get_lang_name($lang_code) {
        return $this->languages->get_lang_name($lang_code);
    }
    
    function get_own_lang_name($lang_code) {
        return $this->languages->get_own_lang_name($lang_code);
    }
    
    function get_lang_name_in_current_locale($lang_code) {
        return $this->languages->get_lang_name_in_current_locale($lang_code);
    }
    
    function get_languages($sort = false) {
        $langs = $this->languages->get_languages();
        if ($sort) {
            uasort($langs, array($this, 'lang_sort'));
        }
        return $langs;
        
    }
    
    function lang_sort($a, $b) {
        return ($this->get_lang_name_in_current_locale($a['code']) < $this->get_lang_name_in_current_locale($b['code'])) ? -1 : 1;
    }
    
    function get_major_langs() {
        return $this->languages->get_major_langs();
    }
    
    function icl_sw_filters_gettext($translation, $text, $domain, $name = false){
    
        $has_translation = 0;

        $context = ($domain != 'default') ? 'theme ' . $domain : 'WordPress';
        
        if(empty($name)){
            $name = md5($text);
        } 
        $ret_translation = wp_trans_icl_t($context, $name, $text, $has_translation);
        if(false === $has_translation){
            $ret_translation = $translation;   
        }
        
        
        return $ret_translation;
    }
    
    function icl_sw_filters_gettext_with_context($translation, $text, $_gettext_context, $domain){
        // special check for language direction
        if ($text == 'ltr' && $_gettext_context == 'text direction' && $domain == 'default') {
            $current_language = $this->get_lang_code(get_locale());
            if (in_array($current_language, array('ar','he','fa'))) {
                return 'rtl';
            }
            
        }
        return $this->icl_sw_filters_gettext($translation, $text, $domain, $_gettext_context . ': ' . $text);
    }
    
    
    function render_lang_switch_popup() {
        echo '<div id="wp_lang_switch_popup" style="display:none;"><div id="wp_lang_switch_form" style="padding:20px;">';
        echo '<strong>' . __('Fetching language information ...', 'wordpress-language') . '</strong> <img src="' . WP_LANG_URL. '/res/img/ajax-loader-big.gif">';
        echo '</div>';
        wp_nonce_field('wp_lang_get_lang_info', 'wp_lang_get_lang_info');
        echo '</div>';
    }
    
    function render_lang_switch_js() {
        ?>
            <script type="text/javascript">
                var original_lang_switch_form = null;
                
                var wp_lang_switch_target = '<?php echo admin_url('options-general.php?page=wordpress_language'); ?>';
                
                var wp_lang_ajx_spinner = '<img src="<?php echo WP_LANG_URL; ?>/res/img/ajax-loader-big.gif">';

                function wp_lang_lang_switch() {
                    var locale = jQuery('input[name="wp_lang_locale\\[\\]"]:checked').val();
                    window.location = wp_lang_switch_target + '&switch_to=' + locale + '&scope=<?php echo $this->current_scope ?>' + '&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val();
                }
                
                function getUrlVars(href) {
                    var vars = {};
                    var parts = href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
                        vars[key] = value;
                    });
                    return vars;
                }            
                jQuery(document).ready(function() {
                    
                    <?php if (get_option('wp_language_show_switcher', 'on') != 'on') {
                        echo 'jQuery(\'#wp-admin-bar-WP_LANG_lang\').hide();';
                    }
                    ?>

                    original_lang_switch_form = jQuery('#wp_lang_switch_form').html();
                    
                    jQuery('.wp_lang_thickbox a').addClass('thickbox');
                    
                    jQuery('.wp_lang_thickbox a').click(function() {
                        
                        var lang = getUrlVars(jQuery(this).attr('href'))['switch_to'];
                        
                        // hide the language popup
                        var refresh = function(i, el){ // force the browser to refresh the tabbing index
                            var node = $(el), tab = node.attr('tabindex');
                            if ( tab )
                                node.attr('tabindex', '0').attr('tabindex', tab);
                        };
                        
                        var target = jQuery(this).parent().parent().parent().parent();
                        target.closest('.hover').removeClass('hover').children('.ab-item').focus();
                        target.siblings('.ab-sub-wrapper').find('.ab-item').each(refresh);
                        //

                        if (lang != 'undefined') {
                        
                            jQuery.ajax({
                                url: ajaxurl,
                                type: 'post',
                                data: 'action=wp_language_get_info&lang=' + lang + '&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val() + '&scope=<?php echo $this->current_scope ?>',
                                cache: false,
                                beforeSend: function () {
                                    
                                },
                                success: function (data) {
                                    jQuery('#wp_lang_switch_form').html(data);
                                }
                            });
                        }
                        
                    });

                });
                

            function wp_lang_show_hide_selector() {
                var state = jQuery('#wp_lang_show_hide_selector:checked').val();
                if (state == 'on') {
                    jQuery('#wp-admin-bar-WP_LANG_lang').show();
                } else {
                    jQuery('#wp-admin-bar-WP_LANG_lang').hide();
                }
                
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: 'action=wp_show_hide_language_selector&state=' + state + '&_wpnonce=' + jQuery('#wp_lang_get_lang_info').val(),
                    cache: false,
                    beforeSend: function () {
                        
                    },
                    success: function (data) {
                    }
                });
                
            }
            </script>    
        <?php
    }
    
    function wpml_promotion() {
        ?>
        <div style="margin:3em 1em 1em 0em; padding: 1em; border: 1pt solid #E0E0E0; background-color: #F4F4F4;">

        <p><h3><?php echo __('Did you know that a single WordPress site can have multiple languages?', 'wordpress-language'); ?></h3></p>

        <p><?php echo sprintf(__('The %sWPML%s plugin lets you run multilingual WordPress sites conveniently. A single site will include all languages.', 'wordpress-language'),
                                    '<a href="http://wpml.org/?utm_source=wplanguage&utm_medium=plugin&utm_term=WPML&utm_content=Admin%2Bscreen&utm_campaign=wplanguage" target="_blank">',
                                    '</a>'); ?></p>
        
        <p><?php echo __('Key features:', 'wordpress-language'); ?></p>
        <ul style="padding-left:20px;">
            <li><img src="<?php echo WP_LANG_URL; ?>/res/img/checkmark-green.png"><?php echo __('<strong>SEO friendly</strong> - languages will have their unique URLs, including different slugs for different translations. You can put languages in different domains or virtual directories.', 'wordpress-language'); ?></li>
            <li><img src="<?php echo WP_LANG_URL; ?>/res/img/checkmark-green.png"><?php echo __('<strong>Translation Management</strong> - easily add translations to content and receive complete reports about what needs updating.', 'wordpress-language'); ?></li>
            <li><img src="<?php echo WP_LANG_URL; ?>/res/img/checkmark-green.png"><?php echo __('<strong>Solid and compatible</strong> - with over 100,000 commercial multilingual sites, WPML is the de-facto standard for multilingual WordPress.', 'wordpress-language'); ?></li>
        </ul>
        <br /><p><a href="http://wpml.org/purchase/?utm_source=wplanguage&utm_medium=plugin&utm_term=Buy&utm_content=Admin%2Bscreen&utm_campaign=wplanguage" class="button-primary" target="_blank"><strong><?php echo __('Buy WPML', 'wordpress-language'); ?></strong></a>&nbsp;<a href="http://wpml.org/?utm_source=wplanguage&utm_medium=plugin&utm_term=Learn%2Bmore&utm_content=Admin%2Bscreen&utm_campaign=wplanguage" class="button" target="_blank"><strong><?php echo __('Learn more', 'wordpress-language'); ?></strong></a></p>
        </div>
        <?php
    }
    
    function ajax_get_lang_info() {
		$nonce = $_POST['_wpnonce'];
                
		if (wp_verify_nonce( $nonce, 'wp_lang_get_lang_info') ) {
            
            
            $ST_MO_Downloader = new ST_MO_Downloader();
            $ST_MO_Downloader->load_xml();
            $lang_code = $this->get_lang_code($_POST['lang']);
            $locales = $ST_MO_Downloader->get_locales($lang_code);
            
            // Hardcoded default texts language
            if($lang_code == 'en'){
                $locales['en_US'] = true;
            }

            
            if (sizeof($locales) > 1 ) {
                echo sprintf(__('We found several alternatives for %s translation. Choose which one you want to use:', 'wordpress-language'), $this->get_lang_name_in_current_locale($lang_code));
                
                $default_locale = $this->languages->get_locale($lang_code);                
                ?>
                    <br />
                    <ul style="padding:10px">
                        <?php
                            foreach($locales as $locale => $data) {
                                $checked = $locale == $default_locale ? ' checked="checked"' : '';
                                
                                echo '<li><label><input type="radio" name="wp_lang_locale[]" value="' . $locale . '"' . $checked .' > ' . $locale . '</label>';
                            }
                        
                        ?>
                    </ul>
                <?php
                
                $click = ' onclick="wp_lang_lang_switch(); return false"';
                $link = "#";
            } else {
                echo sprintf(__('Are you sure you want to switch the language to %s?', 'wordpress-language'), $this->get_lang_name_in_current_locale($lang_code));
                echo '<br /><br />';
                $link = admin_url('options-general.php?page=wordpress_language&switch_to=' . $this->get_locale($lang_code) . '&_wpnonce=' . $nonce . '&scope=' . $_POST['scope']);
                $click = '';
            }
            
            ?>
            
            <a class="button-primary" href="<?php echo $link; ?>" <?php echo $click; ?> style="color:#FFFFFF"><?php echo __('Switch language', 'wordpress-language'); ?></a>
            <a class="button-secondary" href="#" onclick="tb_remove();jQuery('#wp_lang_switch_form').html(original_lang_switch_form);return false;"><?php echo __('Cancel', 'wordpress-language'); ?></a>
            
            
            <?php
        }
        die();
    }

    function ajax_install_language() {
        
		$nonce = $_POST['_wpnonce'];
		if (wp_verify_nonce( $nonce, 'wp_lang_get_lang_info') ) {
            $ST_MO_Downloader = new ST_MO_Downloader();
            $wptranslations = $ST_MO_Downloader->get_option('translations');
            
            $ST_MO_Downloader->load_xml();
            $ST_MO_Downloader->get_translation_files();
            
            if(isset($_POST['scope']) && $_POST['scope']=='front-end'){
                $current_locale = get_option('wp_language_locale_front');
            }else{
                $current_locale = get_locale();    
            }
            
            if($current_locale == 'en_US') {echo '1'; exit;}
            
            $current_lang_code = $this->get_lang_code($current_locale);
            $translations = $ST_MO_Downloader->get_translations($current_locale);
            
            if ($translations !== false) {
                if (isset($translations['new'])) {
                    $ST_MO_Downloader->save_translations($translations['new'], $current_lang_code, $wptranslations[$current_locale]['available']);
                }
                if (isset($translations['updated'])) {
                    $ST_MO_Downloader->save_translations($translations['updated'], $current_lang_code, $wptranslations[$current_locale]['available']);
                }
                
                echo '1';
            } else {
                echo '0';
            }
            
        }
        die();
    }
    
    function ajax_check_for_updates() {
		$nonce = $_POST['_wpnonce'];
		if (wp_verify_nonce( $nonce, 'wp_lang_get_lang_info') ) {
            $ST_MO_Downloader = new ST_MO_Downloader();
            $ST_MO_Downloader->updates_check();
            $wptranslations = $ST_MO_Downloader->get_option('translations');
        
            if($_POST['scope']=='front-end'){
                $current_locale = get_option('wp_language_locale_front');
            }else{
                $current_locale = get_locale();    
            }
            
            $current_lang_code = $this->get_lang_code($current_locale);
            
            $this->current_translation_state($current_lang_code, $current_locale, $wptranslations, true);
            $contents = ob_get_contents();
        
        }
        
        die();
            
    }
    
    function ajax_show_hide_language_selector() {
		$nonce = $_POST['_wpnonce'];
		if (wp_verify_nonce( $nonce, 'wp_lang_get_lang_info') ) {
            if ($_POST['state'] == 'on') {
                update_option('wp_language_show_switcher', 'on');
            } else {
                update_option('wp_language_show_switcher', 'off');
            }
        }
        
        die();
    }
    
    function switch_language($lang_code) {
        $ST_MO_Downloader = new ST_MO_Downloader();
        $ST_MO_Downloader->load_xml();
        $default_locale = $this->languages->get_locale($lang_code);
        update_option('wp_language_locale', $default_locale);
            
    }
        
    function switch_locale($locale) {
        update_option('wp_language_locale', $locale);
        
    }
    
    function ajax_wp_languages_distinct_languages(){
        if(isset($_POST['value'])){                       
            $this->settings['different_languages'] = intval($_POST['value']);
        }
        update_option('wp_languages_options', $this->settings);
        
        if(!get_option('wp_language_locale_front')){
            update_option('wp_language_locale_front', get_locale());
        }
        
        $resp = array();
        
        echo json_encode($resp);
        exit;
    }
    
}


function wp_trans_icl_t($context, $name, $original_value=false, &$has_translation=null, $dont_auto_register = false){
    global $wpdb, $WordPress_language;
    
    if (!isset($WordPress_language)) {
        if(isset($has_translation)) $has_translation = false;
        return $original_value;
    }
    
    $current_language = $WordPress_language->get_lang_code(get_locale());
    $default_language = 'en';
    
    if($current_language == $default_language && $original_value){
        
        $ret_val = $original_value;
        if(isset($has_translation)) $has_translation = false;
        
    }else{
        $result = wp_trans_cache_lookup($context, $name);
        
        $is_string_change = 
            $result !== false && (
                $result['translated'] && $result['original'] != $original_value ||
                !$result['translated'] && $result['value'] != $original_value
            );
        
        if($result === false || is_array($result) && !$result['translated'] && $original_value){        
            $ret_val = $original_value;    
            if(isset($has_translation)) $has_translation = false;
        }else{
            $ret_val = $result['value'];    
            if(isset($has_translation)) $has_translation = true;
        }
        
    }
    return $ret_val;
}

function wp_trans_cache_lookup($context, $name){
    global $wpdb, $WordPress_language;
    static $icl_st_cache;
    
    $ret_value = false;
        
    if(!isset($icl_st_cache[$context])){  //CACHE MISS (context)    
        
        $icl_st_cache[$context] = array();
                       
        $current_language = $WordPress_language->get_lang_code(get_locale());
        
        // workaround for multi-site setups - part i
        global $switched, $switched_stack;        
        if(isset($switched) && $switched){
            $prev_blog_id = $wpdb->blogid;
            $wpdb->set_blog_id($switched_stack[0]);
        }
        
        // THE QUERY        
        $res = $wpdb->get_results($wpdb->prepare("
            SELECT s.name, s.value, t.value AS translation_value, t.status
            FROM  {$wpdb->prefix}icl_strings s
            LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
            WHERE s.context = %s
                AND (t.language = %s OR t.language IS NULL)
            ", $context, $current_language), ARRAY_A);        
        // workaround for multi-site setups - part ii
        if(isset($switched) && $switched){
            $wpdb->set_blog_id($prev_blog_id);
        }   
        
        // SAVE QUERY RESULTS
        if($res){
            foreach($res as $row){                
                if($row['status'] != ICL_STRING_TRANSLATION_COMPLETE || empty($row['translation_value'])){
                    $icl_st_cache[$context][$row['name']]['translated'] = false;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['value'];
                }else{
                    $icl_st_cache[$context][$row['name']]['translated'] = true;
                    $icl_st_cache[$context][$row['name']]['value'] = $row['translation_value'];
                    $icl_st_cache[$context][$row['name']]['original'] = $row['value'];
                }
            }
        }
        
    }
        
    if(isset($icl_st_cache[$context][$name])){   
        $ret_value = $icl_st_cache[$context][$name];                             
    }    
        
    return $ret_value;    
}
