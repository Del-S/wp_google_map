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
            var markers = $('.markers_table input[name^="marker_long_"]');
            
            var markerIDs = [];
            if(markers.length > 0) {
                for (i = 0; i < markers.length; i++) { 
                    var marker_id = markers[i].name;
                    marker_id = marker_id.split("marker_long_").pop();
                    markerIDs.push(marker_id);
                }
            }
            
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_save_settings',
                    options : form,
                    marker_ids : markerIDs,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    console.log(data);
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
            var markers_count = $('.markers_table tr[class^="row_"]').length;
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_save_new_marker',
                    markers_count: markers_count,
                    options : form,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    $('.markers_table tbody').append(data);
                    $('.markers_table tr.new input').val("");
                    $('.markers_table tr.new img').attr("src", "");
                    // Rebind events for new buttons
                    $(".markers_table button[name=update]").bind("click", update_marker);
                    $(".markers_table button[name=remove]").bind("click", remove_marker);
                }
            });
            return false;
        });
        
        /*
         * Update marker
         */
        function update_marker() {
            var id = $( this ).val();
            var form = $( '#gim_options' ).serialize();
            $.ajax({
                type: 'POST',
                url: gimSettings.ajaxurl,
                data: {
                    action : 'gim_update_marker',
                    options : form,
                    marker_id : id,
                    gim_nonce : gimSettings.gim_nonce
                },
                success: function( data ){
                    console.log(data);
                }
            });
            return false;
        }
        $(".markers_table button[name=update]").bind("click", update_marker); // Bind click event to update marker
        
        /*
         * Remove marker
         */
        function remove_marker() {
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
                    $('.markers_table tr.row_'+id).fadeOut("slow", function() {
                        $(this).remove();
                    });
                }
            });
            return false;
        }
        $(".markers_table button[name=remove]").bind("click", remove_marker); // Bind click event to remove marker
        
        /*
         * Image file upload
         
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
        }*/
        
        /* 
         *  Marker image upload
         */
        var rowID = ''; /*setup the var*/
        $('button[name=upload_marker_image]').live('click',function() {
            rowID = $(this).val();
            tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');

            return false;
        });

        window.send_to_editor = function(html) {
            var imgurl = $(html).attr('src');
            if( imgurl != null && imgurl != "" ) {
                var input = '';
                if(rowID == "new") {
                    input = $('input[name=new_img_link]');
                } else {
                    input = $('input[name=marker_img_link_'+ rowID +']');
                }
                input.attr('value',imgurl);
                input.prev('img').attr('src',imgurl);
            } else {
                alert('Error with inserting into settings.');
            }
            rowID = '';
            tb_remove();
        }

    });
        
})(jQuery)