<?php
class GIM_Shortcode {
    var $gim_options;

    function __construct() {
        // Don't allow more than one instance of the class
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'GIM' ),
				get_class( $this ) )
			);
		}
		     
        $this->gim_options = $this->get_options_array();
        
        add_shortcode( 'google-image-map', array( $this , 'display_map' ) );
        
        if( !is_admin() ) {
            add_action( 'wp_footer', array( $this , 'add_gmaps_tag' ), 100 );
        }
    }
    
    /* 
     *  Working with options
     */
    public static function get_options_array() {
		return get_option( 'gim_options' ) ? get_option( 'gim_options' ) : array();
	}
    
    /* 
     *  Display map
     */
    public function display_map() {
        $map_uri = GIM_UPLOADS_URI . '/lomnickastezka'; // add as parameter for shortcode
        $options = $this->gim_options;
        $developer_mode = $options['developer_mode'] ? true : false;
        $marker_redirect = $options['markers_onclick_redirect'] ? true : false;
        
        $markers = $options["markers"];
        foreach($markers as $k => $marker) {
            $image_link = $markers[$k]["img_link"];
            $image_link = explode(".", $image_link, 2);
            $image_link[0] .= "-" . GIM_IMAGE_WIDTH . "x" . GIM_IMAGE_HEIGHT . ".";
            $markers[$k]["img_link"] = implode($image_link);
        }
        
        wp_enqueue_script( 'gim-shortcode', GIM_PLUGIN_URI . '/js/shortcode.js', array( 'jquery' ), GIM_PLUGIN_VERSION, false );
        wp_localize_script( 'gim-shortcode', 'gimSettings', array(
			'upload_uri' => $map_uri,
            'tile_size' => 256,
            'markers' => $markers,
            'map_name' => "Map name",
            'developer_mode' => $developer_mode,
            'marker_redirect' => $marker_redirect
		) );
        
		return '<div id="map" style="width: 500px; height: 500px;"></div>';
	}
    
    function add_gmaps_tag() {
        $options = $this->gim_options;
        if( !empty($options) ) {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key='. $options["google_key"] .'&callback=initMap"
    async defer></script>';
        }
    }
    
}

