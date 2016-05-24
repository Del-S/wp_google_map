(function($){
	$( document ).ready( function() {
        
        /*
         * Display proper inputs by checkboxes
         */
        $(".map_image_enable input[name=image_enable]").click(function() {
            show_map_input(this);
        });
        
        function show_map_input(el) {
            if($(el).is(":checked")) { 
                $(".map_town_location").hide();
            } else { 
                $(".map_town_location").show();
            }
        }
        show_map_input($(".map_image_enable input[name=image_enable]"));
        
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
                    console.log(data);
                    //alert("saved - chnage this to non-modular box with a message");
                }
            });
            return false;
        });
        
        /*
         * Remove marker
         */
        $(".markers_table button[name=remove]").click(function() {
            var id = $( this ).val();
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_remove_marker',
                    marker_id : id,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    console.log(data);
                }
            });
            return false;
        });
        
        /*
         * Image file upload
         */
        $('button[name=upload_image]').live('click',function() {
            imgID = jQuery(this).prev('img.image');
            inputID = jQuery(this).prev('input[name=image_link]');
            tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');

            return false;
        });

        window.send_to_editor = function(html) {
            var imgurl = $(html).attr('src');
            if( imgurl != null ) {
                inputID.val(imgurl);
                imgID.attr('src',imgurl);
            } else {
                alert('Error with inserting into settings.');
            }
            tb_remove();
        }

    });
        
})(jQuery)