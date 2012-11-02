jQuery(document).ready(function(){
    
    jQuery('input[name=different_languages]').change(function(){
        new_val = jQuery(this).val();
        jQuery('input[name=different_languages]').attr('disabled', 'disabled');
        
        if(new_val != 1){
            jQuery('#post-body-content').html(wp_lang_ajx_spinner);
            jQuery(".nav-tabs-nav").fadeOut();
        }
        
        jQuery.ajax({
            dataType: 'json',
            url: ajaxurl,
            type: 'post',
            data: {action:'wp_languages_distinct_languages', value: new_val},
            success: function(resp){
                if(new_val == 1){
                    jQuery(".nav-tabs-nav").fadeIn();
                    jQuery('input[name=different_languages]').removeAttr('disabled');
                }else{
                    location.href = location.href.replace(/&scope=front-end/, '');
                }    
                
            }
            
        })
        
    })
    
});
