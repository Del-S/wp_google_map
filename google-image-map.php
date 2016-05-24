<?php
/*
Plugin Name: Google Image Map
Plugin URI: #
Description: Using Google API to display image map with markers.
Author: David Sucharda
Version: 0.1
Author URI: http://idefixx.cz/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/  

// Time
// 30 min create plugin
// 1 h admin
// 30 min admin)
// 45 min saving data via ajax


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define( 'GIM_PLUGIN_DIR', trailingslashit( dirname(__FILE__) ) );
define( 'GIM_PLUGIN_URI', plugins_url('', __FILE__) );
define( 'GIM_PLUGIN_VERSION', 0.1 );

class Google_Image_Map {
    var $menu_page;
    var $settings;
    private static $_this;

	function __construct() {
		// Don't allow more than one instance of the class
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.', 'GIM' ),
				get_class( $this ) )
			);
		}

		self::$_this = $this;
        
        require_once( GIM_PLUGIN_DIR . 'includes/settings.php' );
        $this->settings = new GIM_Settings();
    
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        
        add_action( 'plugins_loaded', array( $this, 'add_localization' ) );
        
        register_activation_hook( __FILE__, array( $this, 'activate_plugin' ) );
    }
    
    /**
	 * Returns an instance of the object
	 *
	 * @return object
	 */
	static function get_this() {
		return self::$_this;
	}
    
    function admin_init() {
    }
    
    /**
	 * Adds plugin localization
	 * Domain: GIM
	 *
	 * @return void
	 */
	function add_localization() {
		load_plugin_textdomain( 'GIM', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}
    
    function activate_plugin() {
        // create db, flush routes, etc.
	}

    
    
    /**
	 * Extracts attributes from the shortcode and pass them to display_widget function.
	 */
	function display_shortcode( $atts ) {
		$atts = shortcode_atts( array(
			'col_number'       => '',
			'counts_num'       => 0,
			'network_names'    => false,
		), $atts );

		return $this->display_widget( 'shortcode', $atts );
	}
}

new Google_Image_Map();