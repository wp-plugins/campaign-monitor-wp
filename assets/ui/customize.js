$(window).bind('load', function(){

    // Hide non-plugin stuff
    var plugin = getParameterByName('plugin', window.location.href);
    if (plugin == 'easy-opt-in') {
        // Hide non plugin items
        jQuery('.accordion-section').each(function(){
            var item = jQuery(this);
            if (item.attr('id').substring(0, 29) != 'accordion-section-easy_opt_in') {
                item.hide();
            }
        });
    } else {
        // Hide plugin items
        jQuery('.accordion-section').each(function(){
            var item = jQuery(this);
            if (item.attr('id').substring(0, 29) == 'accordion-section-easy_opt_in') {
                item.hide();
            }
        });
    }

    // URL params parser
    function getParameterByName(name, href)
    {
        name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]"+name+"=([^&#]*)";
        var regex = new RegExp( regexS );
        var results = regex.exec( href );
        if( results == null ) {
            return "";
        } else {
            return decodeURIComponent(results[1].replace(/\+/g, " "));
        }
    }
    
    // Fix back url
    jQuery('#customize-header-actions a.back').attr('href', back_url);
});