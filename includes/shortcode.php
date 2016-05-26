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
		     
        //$this->gim_options = $this->get_options_array();
        
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
        wp_enqueue_script( 'gim-admin', GIM_PLUGIN_URI . '/js/shortcode.js', array( 'jquery' ), GIM_PLUGIN_VERSION, false );
        
        
		return '<div id="map" style="width: 500px; height: 500px;"></div>';
	}
    
    function add_gmaps_tag() {
        echo '<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAgY2rfnVxBbeYWik3doVmXKOykBClliCw&callback=initMap"
    async defer></script>';
    }
    
}

