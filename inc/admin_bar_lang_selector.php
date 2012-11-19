<?php

function admin_bar_render_lang_selector() {
    if(is_admin()){
    
        global $wpdb;
        global $wp_admin_bar;
        global $WordPress_language;
    
        $current_locale = get_locale();
        $current_lang_code = $WordPress_language->get_lang_code($current_locale);
        $current_lang_name = $WordPress_language->get_lang_name($current_lang_code);
        
        $lang_name_in_own_lang = $WordPress_language->get_own_lang_name($current_lang_code);
    
        $parent = 'WP_LANG_lang';
        $wp_admin_bar->add_menu( array(
            'parent' => false,
            'id' => $parent,
            'title' => '<img src="' . WP_LANG_URL . '/res/flags/' . $current_lang_code . '.png" /> ' . $lang_name_in_own_lang,
            'href'  => admin_url('options-general.php?page=wordpress_language'),
            'meta'  => array(
                'title' => $lang_name_in_own_lang,
                )
        ));
    
        $langs = $WordPress_language->get_major_langs();
        
        foreach ($langs as $details) {
            $lang_name_in_own_lang = $WordPress_language->get_own_lang_name($details['code']);
            $lang_name_in_current_locale = $WordPress_language->get_lang_name_in_current_locale($details['code']);
            
            
            $link = '#TB_inline?height=255&width=750&inlineId=wp_lang_switch_popup&modal=true';
            $link .= '&switch_to=' . $details['default_locale'];
            
            $wp_admin_bar->add_menu( array(
                'parent' => $parent,
                'id' => 'WP_LANG_lang_child_' . $details['code'],
                'title' => '<img src="' . WP_LANG_URL . '/res/flags/' . $details['code'] . '.png" /> ' . $lang_name_in_own_lang . ' (' . $lang_name_in_current_locale . ')',
                //'href'  => admin_url('options-general.php?page=wordpress_language&switch_to=' . $details->default_locale ),
                'href' => $link,
                'class' => 'thickbox',
                'meta'  => array(
                    'title' => $lang_name_in_own_lang,
                    'class' => 'wp_lang_thickbox',
                    )
            ));
            
        }
        
        $wp_admin_bar->add_menu( array(
            'parent' => $parent,
            'id' => 'WP_LANG_lang_child_more_langs',
            'title' => '<img style="margin-top:4px;" align="left" src="' . WP_LANG_URL . '/res/img/more-languages.png" />&nbsp;' . __('More languages...', 'wordpress-language'),
            'href'  => admin_url('options-general.php?page=wordpress_language&more_langs=1' ),
            'meta'  => array(
                'title' => __('More languages...', 'wordpress-language')
                )
        ));

        $current_locale = get_option('wp_language_locale_front');
        if(empty($current_locale)){
            $current_locale = get_locale();
        }
        
        $current_lang_code = $WordPress_language->get_lang_code($current_locale);
        $current_lang = $WordPress_language->get_own_lang_name($current_lang_code);
        
        $ua = false === strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';
                
        $wp_admin_bar->add_menu( array(
            'parent' => $parent,
            'id' => 'WP_LANG_change_front_page_language',
            'title' => '<img style="margin-top:4px;" align="left" src="' . WP_LANG_URL . '/res/img/public-language.png" />&nbsp;' . sprintf(__('Change language for public pages (currently %s %s - %s)', 'wordpress-language'), $WordPress_language->get_flag($current_lang_code), $current_lang, $current_lang_code) . $ua,
            'href'  => admin_url('options-general.php?page=wordpress_language&more_langs=1&scope=front-end' ),
            'meta'  => empty($WordPress_language->settings['different_languages']) ? array('class' => 'hidden') : null
        ));
        
        $wp_admin_bar->add_menu( array(
            'parent' => $parent,
            'id' => 'WP_LANG_lang_options',
            'title' => '<img style="margin-top:4px;" align="left" src="' . WP_LANG_URL . '/res/img/language-options.png" />&nbsp;' . __('Language options', 'wordpress-language'),
            'href'  => admin_url('options-general.php?page=wordpress_language' ),
            'meta'  => array(
                'title' => __('Language options', 'wordpress-language')
                )
        ));
        
        
        
        
    }    
}
add_action( 'wp_before_admin_bar_render', 'admin_bar_render_lang_selector' );

