<?php

if (!defined('ICL_STRING_TRANSLATION_NOT_TRANSLATED')) {
    define('ICL_STRING_TRANSLATION_NOT_TRANSLATED', 0);
    define('ICL_STRING_TRANSLATION_COMPLETE', 1);
    define('ICL_STRING_TRANSLATION_NEEDS_UPDATE', 2);
    define('ICL_STRING_TRANSLATION_PARTIAL', 3);
    define('ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR', 11);
}

class ST_MO_Downloader{
    const   LOCALES_XML_FILE = 'http://icanlocalze-static.icanlocalize.com/wp-locales.xml.gz';
    
    const   CONTEXT = 'WordPress';
    private $settings;
    private $xml;
    private $translation_files = array();
    
    
    function __construct(){
        global $wp_version;
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);
        
        $this->settings = get_option('icl_adl_settings');
        
        if(empty($this->settings['wp_version']) || version_compare($wp_version, $this->settings['wp_version'], '>')){
            $this->updates_check(array('trigger' => 'wp-update'));
        }
        
        add_action('wp_ajax_icl_adm_updates_check', array($this, 'show_updates'));
        add_action('wp_ajax_icl_adm_save_preferences', array($this, 'save_preferences'));
        
            
    }
    
    function updates_check($args = array()){
        global $wp_version, $WordPress_language;
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version);
        
        $defaults = array(
            'trigger' => 'manual'
        );
        extract($defaults);
        extract($args, EXTR_OVERWRITE);
        
        $this->load_xml();        
        $this->get_translation_files();

        $active_languages = $WordPress_language->get_languages();
        
        $updates = array();
        
        foreach($active_languages as $code => $language){
            
            $this->set_translation($code, $updates);
            
            $locales = $this->get_locales($code);
            foreach ($locales as $locale => $data) {
                $this->set_translation($locale, $updates);
            }
            
        }
        
        $this->settings['wp_version'] = $wpversion;
        
        $this->settings['last_time_xml_check'] = time();
        $this->settings['last_time_xml_check_trigger'] = $trigger;
        $this->save_settings();
        
        return $updates;
        
    }

    function set_translation($code_or_locale, &$updates) {
        if(isset($this->translation_files[$code_or_locale]['core'])){
            $int = preg_match('@tags/([^/]+)/@', $this->translation_files[$code_or_locale]['core'], $matches);   
            if($int){
                $version = $matches[1];                        
                if(empty($this->settings['translations'][$code_or_locale]['installed']) 
                        || version_compare($this->settings['translations'][$code_or_locale]['installed'], $version, '<')){
                    $updates['languages'][$code_or_locale] = $version;    
                }
                $this->settings['translations'][$code_or_locale]['available'] = $version;                            
            }else{
                $int = preg_match('@/trunk/@', $this->translation_files[$code_or_locale]['core']);   
                if($int){
                    $this->settings['translations'][$code_or_locale]['available'] = 'trunk';                            
                }                        
            } 
        }
    }
    
    function show_updates(){
        global $sitepress;
        
        $html = '';
        
        try{
            $updates = $this->updates_check();
            
            if(!empty($updates)){
                $html .= '<table>';
            
            
                foreach($updates['languages'] as $language => $version){
                    $l = $sitepress->get_language_details($language);

                    $html .= '<tr>';
                    $html .= '<td>' . sprintf(__("Updated %s translation is available for WordPress %s.", 'wpml-string-translation'), 
                        '<strong>' . $l['display_name'] . '</strong>' , '<strong>' . $version . '</strong>') . '</td>';
                    $html .= '<td align="right">';
                    $html .= '<a href="' . admin_url('admin.php?page=' . WPML_ST_FOLDER . '/menu/string-translation.php&amp;download_mo=' . $language . '&amp;version=' . $version) . '" class="button-secondary">' .  __('Review changes and update', 'wpml-string-translation') . '</a>'; 
                    $html .= '</td>';
                    $html .= '<tr>';
                    $html .= '</tr>';
                }

            
                $html .= '</table>';
            }else{
                $html .= __('No newer versions found.', 'wpml-string-translation');    
            }
            
        }catch(Exception $error){
            $html .= '<span style="color:#f00" >' . $error->getMessage() . '</span>';    
        }
        
        echo json_encode(array('html' => $html));
        exit;
        
    }
    
    function save_preferences(){
        global $sitepress;
        
        $iclsettings['st']['auto_download_mo'] = @intval($_POST['auto_download_mo']);
        $iclsettings['hide_upgrade_notice'] = implode('.', array_slice(explode('.', ICL_SITEPRESS_VERSION), 0, 3));
        $sitepress->save_settings($iclsettings);
        
        echo json_encode(array('enabled' => $iclsettings['st']['auto_download_mo']));
        
        exit;
    }
    
    function save_settings(){
        update_option('icl_adl_settings', $this->settings);
    }
    
    function get_option($name){
        return isset($this->settings[$name]) ? $this->settings[$name] : null;
    }
    
    function load_xml($reload = false){
        if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
        $client = new WP_Http();
        $response = $client->request(self::LOCALES_XML_FILE, array('timeout'=>15, 'decompress'=>false));
        
        if(is_wp_error($response)){
            throw new Exception(__('Failed downloading the language information file. Please go back and try a little later.', 'wpml-string-translation'));     
        }else{
            if($response['response']['code'] == 200){
                $this->xml = new SimpleXMLElement(wp_trans_gzdecode($response['body']));
                //$this->xml = new SimpleXMLElement($response['body']);
            }
        }
    }
    
    function get_locales($lang_code) {
        $locales = array();
        $other_locales = $this->xml->xpath($lang_code);
        if(is_array($other_locales) && !empty($other_locales)){
            foreach($other_locales[0] as $key => $locale) {
                if ($key != 'versions') {
                    $locale = $lang_code . '_' . $key;
                    $locales[$locale] = $this->get_mo_file_urls($locale);
                }
            }
        }
        
        if (empty($locales)) {
            // only a single locale.
            $locales[$lang_code] = $this->get_mo_file_urls($lang_code);
        }
        
        return $locales;
    }
    
    function get_mo_file_urls($locale){
        global $wp_version;        
        
        $wpversion = preg_replace('#-(.+)$#', '', $wp_version)   ;
        
        if(false !== strpos($locale, '_')){
            $exp = explode('_', $locale);    
            $lpath = $exp[0] . '/' . $exp[1]; 
        }else{
            $lpath = $locale;
        }

        $mo_files = array();
        
        
        $language_path = $this->xml->xpath($lpath . '/versions/version[@number="' . $wpversion . '"]');
        if(empty($language_path)){
            $language_path = $this->xml->xpath($lpath . '/versions/version[@number="trunk"]');
        }
        if(!empty($language_path)){
            $mo_files = (array)$language_path[0];                
            unset($mo_files['@attributes']);
        }elseif(empty($language_path)){
            $other_versions = $this->xml->xpath($lpath . '/versions/version');
            if(is_array($other_versions) && !empty($other_versions)){
                $most_recent = 0;
                foreach($other_versions as $v){
                    $tmpv = (string)$v->attributes()->number;
                    if(version_compare($tmpv , $most_recent, '>')){
                        $most_recent = $tmpv;   
                    }
                }
                if($most_recent > 0){
                    $most_recent_version = $this->xml->xpath($lpath . '/versions/version[@number="' . $most_recent . '"]');
                    $mo_files['core'] = (string)$most_recent_version[0]->core[0];
                }
                
            }
        }

        return $mo_files;
        
    }
    
    function is_locale_installed($locale) {
        return isset($this->settings['translations'][$locale]['installed']) &&
                ($this->settings['translations'][$locale]['installed'] == $this->settings['translations'][$locale]['available']);
    }
    
    function get_translation_files(){
        global $WordPress_language;
        
        $active_languages = $WordPress_language->get_languages();
        
        foreach($active_languages as $code => $language){
            $locales = $this->get_locales($code);
            foreach ($locales as $locale => $urls) {
                $this->translation_files[$locale] = $urls;
                
                if ($locale == $language['default_locale']) {
                    $this->translation_files[$code] = $urls;
                }
            }
        }
                
        return $this->translation_files;
        
    }
    
    function get_translations($language, $args = array()){
        global $wpdb;
        $translations = array();
        
        // defaults
        $defaults = array(
            'type'      => 'core'
        );
        
        extract($defaults);
        extract($args, EXTR_OVERWRITE);
        
        
        if(isset($this->translation_files[$language][$type])){
        
            if(!class_exists('WP_Http')) include_once ABSPATH . WPINC . '/class-http.php';
            $client = new WP_Http();
            $response = $client->request($this->translation_files[$language][$type], array('timeout'=>15));
            
            if(is_wp_error($response)){
                $err = __('Error getting the translation file. Please go back and try again.', 'wpml-string-translation');
                if(isset($response->errors['http_request_failed'][0])){
                    $err .= '<br />' . $response->errors['http_request_failed'][0];
                }
                echo '<div class="error"><p>' . $err . '</p></div>';
                return false;
                
            }        
            
            $mo = new MO();
            $pomo_reader = new POMO_StringReader($response['body']);
            $mo->import_from_reader( $pomo_reader );
            
            foreach($mo->entries as $key=>$v){
                
                $tpairs = array();
                $v->singular = str_replace("\n",'\n', $v->singular);
                $tpairs[] = array(
                    'string'        => $v->singular, 
                    'translation'   => $v->translations[0],
                    'name'          => !empty($v->context) ? $v->context . ': ' . $v->singular : md5($v->singular)
                );
                
                if($v->is_plural){
                    $v->plural = str_replace("\n",'\n', $v->plural);
                    $tpairs[] = array(
                        'string'        => $v->plural, 
                        'translation'   => !empty($v->translations[1]) ? $v->translations[1] : $v->translations[0],
                        'name'          => !empty($v->context) ? $v->context . ': ' . $v->plural : md5($v->singular)
                    );
                }
                
                foreach($tpairs as $pair){
                    $existing_translation = $wpdb->get_var($wpdb->prepare("
                        SELECT st.value 
                        FROM {$wpdb->prefix}icl_string_translations st
                        JOIN {$wpdb->prefix}icl_strings s ON st.string_id = s.id
                        WHERE s.context = %s AND s.name = %s AND st.language = %s 
                    ", self::CONTEXT, $pair['name'], $language));
                    
                    if(empty($existing_translation)){
                        $translations['new'][] = array(
                                                'string'        => $pair['string'],
                                                'translation'   => '',
                                                'new'           => $pair['translation'],
                                                'name'          => $pair['name']
                        );
                    }else{
                        
                        if(strcmp($existing_translation, $pair['translation']) !== 0){
                            $translations['updated'][] = array(
                                                'string'        => $pair['string'],
                                                'translation'   => $existing_translation,
                                                'new'           => $pair['translation'],
                                                'name'          => $pair['name']
                            );
                        }
                        
                    }
                } 
            }
        } else {
            return false;
        }
        
        return $translations;
    }
    
    function save_translations($data, $language, $version = false){
        
        if(false === $version){
            global $wp_version;        
            $version = preg_replace('#-(.+)$#', '', $wp_version)   ;            
        }    
        
        foreach($data as $key => $string){
            $string_id = wp_trans_register_string(self::CONTEXT, $string['name'], $string['string']);
            if($string_id){
                wp_trans_add_string_translation($string_id, $language, $string['new'], ICL_STRING_TRANSLATION_COMPLETE);
            }
        }    
        
        
        $this->settings['translations'][$language]['time'] = time();
        $this->settings['translations'][$language]['installed'] = $version;
        
        // set the other locales in this language to be uninstalled.
        $locales = $this->get_locales($language);
        $current_locale = get_locale();
        $this->settings['translations'][$current_locale]['installed'] = $version;
        $this->settings['translations'][$current_locale]['time'] = time();
        foreach ($locales as $locale => $data) {
            if ($locale != $current_locale) {
                if (isset($this->settings['translations'][$locale]['installed'])) {
                    unset($this->settings['translations'][$locale]['installed']);
                }
                
            }
        }
        $this->save_settings();
        
    }
    
    
}
  
  
/**
 * gzdecode implementation
 *
 * @see http://hu.php.net/manual/en/function.gzencode.php#44470
 * 
 * @param string $data
 * @param string $filename
 * @param string $error
 * @param int $maxlength
 * @return string
 */
function wp_trans_gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
    $len = strlen ( $data );
    if ($len < 18 || strcmp ( substr ( $data, 0, 2 ), "\x1f\x8b" )) {
        $error = "Not in GZIP format.";
        return null; // Not GZIP format (See RFC 1952)
    }
    $method = ord ( substr ( $data, 2, 1 ) ); // Compression method
    $flags = ord ( substr ( $data, 3, 1 ) ); // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    // NOTE: $mtime may be negative (PHP integer limitations)
    $mtime = unpack ( "V", substr ( $data, 4, 4 ) );
    $mtime = $mtime [1];
    $xfl = substr ( $data, 8, 1 );
    $os = substr ( $data, 8, 1 );
    $headerlen = 10;
    $extralen = 0;
    $extra = "";
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $extralen = unpack ( "v", substr ( $data, 8, 2 ) );
        $extralen = $extralen [1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false; // invalid
        }
        $extra = substr ( $data, 10, $extralen );
        $headerlen += 2 + $extralen;
    }
    $filenamelen = 0;
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr ( $data, $headerlen, $filenamelen );
        $headerlen += $filenamelen + 1;
    }
    $commentlen = 0;
    $comment = "";
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $commentlen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false; // Invalid header format
        }
        $comment = substr ( $data, $headerlen, $commentlen );
        $headerlen += $commentlen + 1;
    }
    $headercrc = "";
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $calccrc = crc32 ( substr ( $data, 0, $headerlen ) ) & 0xffff;
        $headercrc = unpack ( "v", substr ( $data, $headerlen, 2 ) );
        $headercrc = $headercrc [1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false; // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack ( "V", substr ( $data, - 8, 4 ) );
    $datacrc = sprintf ( '%u', $datacrc [1] & 0xFFFFFFFF );
    $isize = unpack ( "V", substr ( $data, - 4 ) );
    $isize = $isize [1];
    // decompression:
    $bodylen = $len - $headerlen - 8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr ( $data, $headerlen, $bodylen );
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
            case 8 :
                // Currently the only supported compression method:
                $data = gzinflate ( $body, $maxlength );
                break;
            default :
                $error = "Unknown compression method.";
                return false;
        }
    } // zero-byte body content is allowed
    // Verifiy CRC32
    $crc = sprintf ( "%u", crc32 ( $data ) );
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen ( $data );
    if (! $lenOK || ! $crcOK) {
        $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}
 
function wp_trans_register_string($context, $name, $value){    
    global $wpdb, $WordPress_language;
    
    $language = 'en'; // only register English strings.
    
    $res = $wpdb->get_row("SELECT id, value, status, language FROM {$wpdb->prefix}icl_strings WHERE context='".$wpdb->escape($context)."' AND name='".$wpdb->escape($name)."'");
    if($res){
        $string_id = $res->id;
        $update_string = array();
        if($value != $res->value){
            $update_string['value'] = $value;
        }
        if($language != $res->language){
            $update_string['language'] = $language;
        }
        if(!empty($update_string)){
            $wpdb->update($wpdb->prefix.'icl_strings', $update_string, array('id'=>$string_id));
            $wpdb->update($wpdb->prefix.'icl_string_translations', array('status'=>ICL_STRING_TRANSLATION_NEEDS_UPDATE), array('string_id'=>$string_id));
        }        
    }else{
        if(!empty($value) && is_scalar($value) && trim($value)){
            $string = array(
                'language' => $language,
                'context' => $context,
                'name' => $name,
                'value' => $value,
                'status' => ICL_STRING_TRANSLATION_NOT_TRANSLATED,
            );
            $wpdb->insert($wpdb->prefix.'icl_strings', $string);
            $string_id = $wpdb->insert_id;
        }else{
            $string_id = 0;
        }
    } 

    return $string_id; 
}


function wp_trans_add_string_translation($string_id, $language, $value = null, $status = false, $translator_id = null){
    global $wpdb;
    
    $res = $wpdb->get_row("SELECT id, value, status FROM {$wpdb->prefix}icl_string_translations WHERE string_id='".$wpdb->escape($string_id)."' AND language='".$wpdb->escape($language)."'");
    
    // the same string should not be sent two times to translation
    if(isset($res->status) && $res->status == ICL_STRING_TRANSLATION_WAITING_FOR_TRANSLATOR && is_null($value)){
        return false;        
    }
    
    if($res){
        $st_id = $res->id;
        $st_update = array();
        if(!is_null($value) && $value != $res->value){  // null $value is for sending to translation. don't override existing.
            $st_update['value'] = $value;
        }
        if($status){
            $st_update['status'] = $status;
        }elseif($status === ICL_STRING_TRANSLATION_NOT_TRANSLATED){
            $st_update['status'] = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        } 
        
        if(!empty($st_update)){
            if(!is_null($translator_id)){
                $st_update['translator_id'] = get_current_user_id();
            }
            $st_update['translation_date'] = current_time("mysql");
            $wpdb->update($wpdb->prefix.'icl_string_translations', $st_update, array('id'=>$st_id));
        }        
    }else{
        if(!$status){
            $status = ICL_STRING_TRANSLATION_NOT_TRANSLATED;
        }
        $st = array(
            'string_id' => $string_id,
            'language'  => $language,
            'status'    => $status
        );
        if(!is_null($value)){
            $st['value'] = $value;
        }
        if(!is_null($translator_id)){
            $st_update['translator_id'] = get_current_user_id();
        }        
        $wpdb->insert($wpdb->prefix.'icl_string_translations', $st);
        $st_id = $wpdb->insert_id;
    }    

    return $st_id;
}

  
?>
