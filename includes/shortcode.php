<?php
class GIM_Shortcode {
    var $gim_options;
    var $gim_map_dirs;
    
    function __construct() {
        // Don't allow more than one instance of the class
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'GIM' ),
				get_class( $this ) )
			);
		}
		     
        $this->gim_options = $this->get_options_array();
        foreach(glob(GIM_UPLOADS_DIR . '/*', GLOB_ONLYDIR) as $dir) {
            $dirname[] = basename($dir);
        }
        $this->gim_map_dirs = $dirname;
        
        add_shortcode( 'google-image-map', array( $this , 'display_map' ) );
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
    public function display_map( $atts ) {
        $maps = $this->gim_map_dirs;
        $default_map = "";
        if(is_array($maps) && !empty($maps)) {
            $default_map = $maps[0];
        }
        $atts = shortcode_atts(
		array(
			'map' => $default_map,
		), $atts, 'google-image-map' );
        
        add_action( 'wp_footer', array( $this , 'add_gmaps_tag' ), 100 );
        
        $map_uri = GIM_UPLOADS_URI . '/'. $atts["map"];
        $options = $this->gim_options;
        if(is_admin()) {
            $developer_mode = $options['developer_mode'] ? true : false;
        }
        $marker_redirect = $options['markers_onclick_redirect'] ? true : false;
        
        if (version_compare(phpversion(), '5.3.0', '>=')) {
            $hostname = gethostname();
        } else {
            $hostname = php_uname('n');
        }
        
        $home_url = get_home_url();
        $markers = $options["markers"];
        if( is_array($markers) && !empty($markers) ) {
            foreach($markers as $k => $marker) {
                if($marker["map"] != $atts["map"]) {
                    unset($markers[$k]);
                    continue;
                }

                $image_link = $markers[$k]["img_link"];
                $image_link = explode(".", $image_link);
                $key_image = count($image_link) - 2;
                $image_link[$key_image] .= "-" . GIM_IMAGE_WIDTH . "x" . GIM_IMAGE_HEIGHT;
                $markers[$k]["img_link"] = implode('.', $image_link);

                $url = $markers[$k]["link"];
                if(!empty(trim($url))) {
                    if( (strpos( $url, $home_url ) === false) && ( (strpos( $url, $hostname ) === false) && !filter_var($url, FILTER_VALIDATE_URL) )) {
                        $markers[$k]["link"] = $home_url . $url;
                    }
                } else {
                    $markers[$k]["link"] = trim($url);
                }
            }
        } else {
            $markers = array();
        }
        
        wp_enqueue_script( 'gim-shortcode', GIM_PLUGIN_URI . '/js/shortcode.js', array( 'jquery' ), GIM_PLUGIN_VERSION, false );
        wp_localize_script( 'gim-shortcode', 'gimSettingsFront', array(
			'upload_uri' => $map_uri,
            'tile_size' => 256,
            'markers' => $markers,
            'map_name' => "Map name",
            'developer_mode' => $developer_mode,
            'marker_redirect' => $marker_redirect
		) );
        
		return '<div id="map" style="width: 800px; height: 450px;"></div>';
	}
    
    function add_gmaps_tag() {
        $options = $this->gim_options;
        if( !empty($options["google_key"]) ) {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key='. $options["google_key"] .'&callback=initMap"
    async defer></script>';
        }
    }  
}

