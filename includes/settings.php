<?php
class GIM_Settings {
    var $gim_options;
    var $gim_settings_map;
    var $gim_marker_map;
    
    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        
        $plugin_file = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_{$plugin_file}", array( $this, 'add_settings_link' ) );
        
        $this->gim_options = $this->get_options_array();
        $this->gim_settings_map = $this->get_settings_map();
        $this->gim_marker_map = $this->get_marker_map();
        
		add_action( 'wp_ajax_gim_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_gim_save_new_marker', array( $this, 'ajax_save_new_marker' ) );
        add_action( 'wp_ajax_gim_remove_marker', array( $this, 'ajax_remove_marker' ) );
    }
    
    /* 
     *  Menu changes
     */    
    function admin_menu() {
		$menu_page = add_submenu_page( 'options-general.php', __( 'Google Image Map', 'GIM' ), __( 'Google Image Map', 'GIM' ), 'manage_options', 'gim_options', array( $this, 'options_page' ) );
		add_action( "admin_print_scripts-{$menu_page}", array( $this, 'load_admin_js' ) );
		//add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
	}
    
    function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="options-general.php?page=gim_options">%1$s</a>', __( 'Settings', 'GIM' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}
    
    function load_admin_js() {
		wp_enqueue_script( 'gim-admin', GIM_PLUGIN_URI . '/js/admin.js', array( 'jquery' ), GIM_PLUGIN_VERSION, true );
		//wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ), $this->plugin_version, true );

		wp_localize_script( 'gim-admin', 'gimSettings', array(
			'gim_nonce' => wp_create_nonce( 'gim_nonce' ),
			'ajaxurl'       => admin_url( 'admin-ajax.php', $this->protocol ),
		) );
	}
    
    /* 
     *  Working with options
     */
    public static function get_options_array() {
		return get_option( 'gim_options' ) ? get_option( 'gim_options' ) : array();
	}
    
    public static function get_settings_map() {
        $settings_map = array();
        $settings_map['image_enable'] = 'checkbox';
        $settings_map['image_link'] = 'input';
        $settings_map['town_location'] = 'input';
        $settings_map['markers_enable'] = 'checkbox';
        $settings_map['markers_link_only'] = 'checkbox';
        return $settings_map;
    }
    
    public static function get_marker_map() {
        $marker_map = array();
        $marker_map['new_name'] = 'input';
        $marker_map['new_description'] = 'input';
        $marker_map['new_link'] = 'input';
        $marker_map['new_long'] = 'input';
        $marker_map['new_lat'] = 'input';
        return $marker_map;
    }
    
    function ajax_save_settings( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$options = $_POST['options'];
		$message = $this->change_options( $options );
        die( $message );
	}
    
    function ajax_save_new_marker( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$options = $_POST['options'];
		$message = $this->add_marker( $options );
        die( $message );
	}
    
    function ajax_remove_marker( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$id = $_POST['marker_id'];
		$message = $this->remove_marker( $id );
        die( $message );
	}
    
    function change_options( $options ) {
        $gim_settings_map = $this->gim_settings_map;
        
        $message = '';
        if ( !is_array( $options ) ) {
			$processed_array = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $options );
			parse_str( $processed_array, $output );
		} else {
			$output = $options;
		}
        
        if ( isset( $gim_settings_map ) ) {
			foreach ( $gim_settings_map as $name => $type ) {
                switch( $type ) {
                    case 'input':
                        $options_temp[ $name ] = isset( $output[ $name ] )
                                    ? sanitize_text_field( $output[ $name ] )
                                    : '';
                    break;

                    case 'checkbox':
                        $options_temp[ $name ] = $output[ $name ] == "on" || $output[ $name ] == true ? true : false;
                    break;
                }
            }
        }
        
        $this->update_option($options_temp);
        return($message); // Todo message
    }
    
    function add_marker( $options ) {
        $gim_marker_map = $this->gim_marker_map;
        $options = $this->options;
        // todo fix max of ids in array
        if( ($options["markers"] != null) && (!empty($options["markers"])) ) {
            $id_keys = array_keys($options["markers"]);
            $m_id = max($id_keys) + 1;
        } else { 
            $m_id = 0; 
        }

        $message = '';
        if ( !is_array( $options ) ) {
			$processed_array = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $options );
			parse_str( $processed_array, $output );
		} else {
			$output = $options;
		}
        
        if ( isset( $gim_marker_map ) ) {
			foreach ( $gim_marker_map as $name => $type ) {
                $name = str_replace("new_","",$name);
                switch( $type ) {
                    case 'input':
                        $options_temp["markers"][$m_id][ $name ] = isset( $output[ $name ] )
                                    ? sanitize_text_field( $output[ $name ] )
                                    : '';
                    break;

                    case 'checkbox':
                        $options_temp["markers"][$m_id][ $name ] = $output[ $name ] == "on" || $output[ $name ] == true ? true : false;
                    break;
                }
            }
        }
        
        $this->update_option($options_temp);
        $message = ""; // todo
        return($message);
    }
    
    function remove_marker( $id ) {
        $options = $this->options;
        $message = '';
        
        if( ($options["markers"] != null) && (!empty($options["markers"])) ) {
            // todo remove from array by marker id
        } else { 
            // Todo error message
        }
    }
    
    function update_option( $update_array ) {
		$gim_options = $this->gim_options;
		$updated_options = array_merge( $gim_options, $update_array );
		update_option( 'gim_options', $updated_options );
	}
    
    /* 
     *  Display options page
     */
    function options_page() {
        $options = $this->gim_options;
        $map_image_enabled = $options['image_enable'] ? "checked" : "";
        $map_image_link = $options['image_link'] ? $options['image_link'] : "";
        $map_town_location = $options['town_location'] ? $options['town_location'] : "";
        
        $map_markers_enable = $options['markers_enable'] ? "checked" : "";
        $map_markers_link_only = $options['markers_link_only'] ? "checked" : "";
        
        $page = '<div id="gim_wrapper">
            <h2>Google Image Map settings</h2>
            <form action="" method="POST" id="gim_options">
                <div class="map_settings">
                    <div class="map_image_enable">
                        <label for="image_enable">'. __('Use Image in map','GIM') .'</label>
                        <input type="checkbox" name="image_enable" '. $map_image_enabled .'  />
                    </div>
                    <div class="map_image_link hidden">
                        <label for="map_image">'. __('Map image','GIM') .'</label>
                        <input type="text" name="image_link" value="'. $map_image_link .'"  />
                    </div>
                    <div class="map_town_location hidden">
                        <label for="map_location">'. __('Location in map','GIM') .'</label>
                        <input type="text" name="town_location" value="'. $map_town_location .'"  />
                    </div>
                    
                    <div class="map_markers_enable">
                        <label for="map_markers_enable">'. __('Enable markers','GIM') .'</label>
                        <input type="checkbox" name="markers_enable" '. $map_markers_enable .'  />
                    </div>
                    <div class="map_markers_link_only">
                        <label for="map_markers_link_only">'. __('Markers as link only','GIM') .'</label>
                        <input type="checkbox" name="markers_link_only" '. $map_markers_link_only .'  />
                    </div>
                </div>
                <div class="map_markers">
                <table class="markers_table wp-list-table widefat">
                    <thead>
                    <tr>
                        <th>'. __('#ID','GIM') . '</th>
                        <th>'. __('Name','GIM') .'</th>
                        <th>'. __('Description','GIM') .'</th>
                        <th>'. __('Link','GIM') .'</th>
                        <th>'. __('Longtitude','GIM') .'</th>
                        <th>'. __('Latitude','GIM') .'</th>
                    </tr>
                    </thead>
                    <tbody>';
        
                    $markers = $options['markers'] ? $options['markers'] : array();
                    $alt_counter = 0;
        print_r($markers);
                    foreach($markers as $id => $marker) {
                        $alt = "";
                        if(!(bool)($alt_counter & 1)) { $alt = 'alternate'; }
                        $page .= '<tr class="row_'.$id.' '.$alt.'">
                                <td>Marker #'.$id.'</td>
                                <td><input type="text" name="marker_'.$id.'_name" value="'. $marker['name'] .'" /></td>
                                <td><input type="text" name="marker_'.$id.'_description" value="'. $marker['description'] .'" /></td>
                                <td><input type="text" name="marker_'.$id.'_link" value="'. $marker['link'] .'" /></td>
                                <td><input type="text" name="marker_'.$id.'_long" value="'. $marker['long'] .'" /></td>
                                <td><input type="text" name="marker_'.$id.'_lat" value="'. $marker['lat'] .'" /></td>
                                <td><button name="remove" value="'.$id.'">'.__('Remove','GIM').'</button></td>
                              </tr>';
                        $alt_counter++;
                    }        
                    $page .= '</tbody>
                    <tfoot>
                    <tr class="new">
                        <th>'. __('Add marker','GIM') .'</th>
                        <th><input type="text" name="new_name"/></th>
                        <th><input type="text" name="new_description" value="'. $marker['description'] .'" /></th>
                        <th><input type="text" name="new_link" value="'. $marker['link'] .'" /></th>
                        <th><input type="text" name="new_long" value="'. $marker['long'] .'" /></th>
                        <th><input type="text" name="new_lat" value="'. $marker['lat'] .'" /></th>
                        <th><button name="save_new_marker" class="button">'. __('Add','GIM') .' </button></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            
            <input type="submit" name="save_gim_options" class="save_gim_options button button-primary button-large" value="'. __('Save settings','GIM') .'" />
            </form>
        </div>';
        
        echo $page;
    } 
}