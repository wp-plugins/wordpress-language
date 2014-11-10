<?php
function wp_language_activate(){
    
    //if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'error_scrape'){
    //    return;
    //}
    
    global $wpdb;
    global $EZSQL_ERROR;
    require_once(WP_LANG_PATH . '/inc/lang-data.php');
    //defines $langs_names

    $charset_collate = '';
    if ( method_exists($wpdb, 'has_cap') && $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset) )
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if ( ! empty($wpdb->collate) )
                    $charset_collate .= " COLLATE $wpdb->collate";
    }    
    
    try{
    
       /* general string translation */
        $table_name = $wpdb->prefix.'icl_strings';
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
            $sql = "
                CREATE TABLE `{$table_name}` (
                  `id` bigint(20) unsigned NOT NULL auto_increment,
                  `language` varchar(10) NOT NULL,
                  `context` varchar(160) NOT NULL,
                  `name` varchar(160) NOT NULL,
                  `value` text NOT NULL,
                  `status` TINYINT NOT NULL,
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `context_name` (`context`,`name`)
                ) ENGINE=MyISAM {$charset_collate}"; 
            $wpdb->query($sql);
            if($e = mysql_error()) throw new Exception($e);
        }
        
        $table_name = $wpdb->prefix.'icl_string_translations';
        if($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name){
            $sql = "
                CREATE TABLE `{$table_name}` (
                  `id` bigint(20) unsigned NOT NULL auto_increment,
                  `string_id` bigint(20) unsigned NOT NULL,
                  `language` varchar(10) NOT NULL,
                  `status` tinyint(4) NOT NULL,
                  `value` text NULL DEFAULT NULL,              
                  `translator_id` bigint(20) unsigned DEFAULT NULL, 
                  `translation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY  (`id`),
                  UNIQUE KEY `string_language` (`string_id`,`language`)
                ) ENGINE=MyISAM {$charset_collate}"; 
            $wpdb->query($sql);
            if($e = mysql_error()) throw new Exception($e);
        }
        update_option('wordpress-language-activated', true);
    } catch(Exception $e) {
        trigger_error($e->getMessage(),  E_USER_ERROR);
        exit;
    }
    

}