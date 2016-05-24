(function($){
	$( document ).ready( function() {
        
        /*
         * Display proper inputs by checkboxes
         */
        // Can be one function loaded with on 2 handles?
        $(".map_image_enable input[name=image_enable]").ready(function() {
            // This is wrong - check this
            if($(this).attr(":checked")) { 
                $(".map_image_link").show();
            } else { 
                $(".map_town_location").show();
            }
        }).click(function() {
            // This is wrong - check this
            if($(this).attr(":checked")) { 
                $(".map_image_link").show();
            } else { 
                $(".map_town_location").show();
            }
        });
        
        /* 
         * Save complete form via ajax
         */
        $( '.save_gim_options' ).click( function() {
            var form = $( '#gim_options' ).serialize();
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_save_settings',
                    options : form,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    alert("saved - chnage this to non-modular box with a message");
                }
            });
            return false;
        });
        
        /* 
         * Save new marker
         */
        $( 'button[name=save_new_marker]' ).click( function() {
            var form = $( '#gim_options' ).serialize();
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_save_new_marker',
                    options : form,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    alert("saved - chnage this to non-modular box with a message");
                }
            });
            return false;
        });
        
        /*
         * Remove marker
         */
        $(".markers_table button").click(function() {
            var id = $( this ).val();
            var data = ["marker_id" => id];
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_remove_marker',
                    options : data.serializeArray(),
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    alert("saved - chnage this to non-modular box with a message");
                }
            });
            return false;
        });
        
    });
        
})(jQuery)