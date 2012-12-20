<?php

class WP_languages {

    function __construct() {
        $this->lang_data = array();
        $this->locale_data = array();
        
        require_once(WP_LANG_PATH . '/inc/lang-data.php');
        
        foreach($langs_names as $key=>$val){
            if(strpos($key,'Norwegian Bokm')===0){ $key = 'Norwegian Bokmål'; $lang_codes[$key] = 'nb';} // exception for norwegian
            if(strpos($key,'Portuguese, Portugal')===0){ $key = 'Portuguese'; $lang_codes[$key] = 'pt';}
            if(strpos($key,'Portuguese, Brazil')===0){ continue;}
            $default_locale = isset($lang_locales[$lang_codes[$key]]) ? $lang_locales[$lang_codes[$key]] : $lang_codes[$key];
            
            $this->lang_data[$lang_codes[$key]] = array('english_name'=>$key, 'code'=>$lang_codes[$key], 'major'=>$val['major'], 'active'=>0, 'default_locale'=>$default_locale, 'locales' => array($default_locale));
            
            $this->locale_data[$default_locale] = $lang_codes[$key];
        }
        
        $this->lang_translations = array();
        
        foreach($langs_names as $lang=>$val){        
            if(strpos($lang,'Norwegian Bokm')===0){ $lang = 'Norwegian Bokmål'; $lang_codes[$lang] = 'nb';}
            if(strpos($lang,'Portuguese, Portugal')===0){ $lang = 'Portuguese'; $lang_codes[$lang] = 'pt';}
            if(strpos($lang,'Portuguese, Brazil')===0){ continue;}
            
            $this->lang_translations[$lang_codes[$lang]] = array();
            foreach($val['tr'] as $k=>$display){        
                if(strpos($k,'Norwegian Bokm')===0){ $k = 'Norwegian Bokmål';}
                if(strpos($k,'Portuguese, Portugal')===0){ $k = 'Portuguese';}
                if(strpos($k,'Portuguese, Brazil')===0){ continue;}
                if(!trim($display)){
                    $display = $lang;
                }
                
                $this->lang_translations[$lang_codes[$lang]][$lang_codes[$k]] = $display;
            }    
        }       
        
    }

    function get_lang_code($find_locale) {

        $lang_code = $find_locale;
        if(false !== strpos($find_locale, '_')){
            $exp = explode('_', $find_locale);
            $lang_code = $exp[0];
        }
        
        return $lang_code;
    }
    
    function get_locale($lang_code) {
        return $this->lang_data[$lang_code]['default_locale'];
    }

    function get_lang_name($lang_code) {
        return $this->lang_data[$lang_code]['english_name'];
    }
    
    function get_own_lang_name($lang_code) {
        if (isset($this->lang_translations[$lang_code][$lang_code])) {
            $lang_name = $this->lang_translations[$lang_code][$lang_code];
        } else {
            // we don't have a translation so use english name
            $lang_name = $this->get_lang_name();
        }
        
        return $lang_name;
    }
    
    function get_lang_name_in_current_locale($lang_code) {
        $current_lang_code = $this->get_lang_code(get_locale());
        
        if (isset($this->lang_translations[$lang_code][$current_lang_code])) {
            $lang_name = $this->lang_translations[$lang_code][$current_lang_code];
        } else {
            // we don't have a translation so try in it's own locale
            $lang_name = $this->get_own_lang_name($current_lang_code);
        }
        
        return $lang_name;
    }
    
    function get_languages() {
        return $this->lang_data;
    }
    
    function get_major_langs() {
        $langs = array();
        
        foreach($this->lang_data as $code => $data) {
            if ($data['major']) {
                $langs[$code] = $data;
            }
        }
        
        return $langs;
    }
    

}