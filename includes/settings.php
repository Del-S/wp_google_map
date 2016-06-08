<?php
// TODO: complete map tiles upload
// - Marker pick for each map tiles
// - Display map tiles names (in upload dir)
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
        add_action( 'wp_ajax_gim_update_marker', array( $this, 'ajax_update_marker' ) );
        add_action( 'wp_ajax_gim_remove_marker', array( $this, 'ajax_remove_marker' ) );
    }
    
    /* 
     *  Menu changes
     */    
    function admin_menu() {
		$menu_page = add_submenu_page( 'options-general.php', __( 'Google Image Map', 'GIM' ), __( 'Google Image Map', 'GIM' ), 'manage_options', 'gim_options', array( $this, 'options_page' ) );
		add_action( "admin_print_scripts-{$menu_page}", array( $this, 'load_admin_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
	}
    
    function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="options-general.php?page=gim_options">%1$s</a>', __( 'Settings', 'GIM' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}
    
    function load_admin_js() {
        // Load upload an thickbox script
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        
		wp_enqueue_script( 'gim-admin', GIM_PLUGIN_URI . '/js/admin.js', array( 'jquery' ), GIM_PLUGIN_VERSION, true );
		//wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ), GIM_PLUGIN_VERSION, true );

		wp_localize_script( 'gim-admin', 'gimSettings', array(
			'gim_nonce' => wp_create_nonce( 'gim_nonce' ),
			'ajaxurl'   => admin_url( 'admin-ajax.php', $this->protocol ),
		) );
	}
    
    function load_admin_css() {
        // Load thickbox CSS
        wp_enqueue_style('thickbox');
	}
    
    /* 
     *  Working with options
     */
    public static function get_options_array() {
		return get_option( 'gim_options' ) ? get_option( 'gim_options' ) : array();
	}
    
    public static function get_settings_map() {
        $settings_map = array();
        $settings_map['google_key'] = 'input';
        $settings_map['image_enable'] = 'checkbox';
        $settings_map['image_link'] = 'input';
        $settings_map['town_location'] = 'input';
        $settings_map['markers_enable'] = 'checkbox';
        $settings_map['markers_link_only'] = 'checkbox';
        $settings_map['markers_onclick_redirect'] = 'checkbox';
        $settings_map['developer_mode'] = 'checkbox';
        return $settings_map;
    }
    
    public static function get_marker_map() {
        $marker_map = array();
        $marker_map['name'] = 'input';
        $marker_map['link'] = 'input';
        $marker_map['lat'] = 'input';
        $marker_map['long'] = 'input';
        $marker_map['img_link'] = 'input';
        return $marker_map;
    }
    
    function ajax_save_settings( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$options = $_POST['options'];
        $marker_ids = $_POST['marker_ids'];
		$message = $this->change_options( $options, $marker_ids );
        die( $message );
	}
    
    function ajax_save_new_marker( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$options = $_POST['options'];
        $markers_count = $_POST['markers_count'];
		$message = $this->add_marker( $options, $markers_count );
        die( $message );
	}
        
    function ajax_update_marker( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$options = $_POST['options'];
        $id = $_POST['marker_id'];
		$message = $this->update_marker( $id, $options );
        die( $message );
	}
    
    function ajax_remove_marker( $options = array() ) {
		wp_verify_nonce( $_POST['gim_nonce'], 'gim_nonce' );
		$id = $_POST['marker_id'];
		$message = $this->remove_marker( $id );
        die( $message );
	}
    
    function change_options( $options, $marker_ids ) {
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
        $options_temp["markers"] = $this->change_markers($output, $marker_ids);
        
        $this->update_option($options_temp, true);
        $message = ""; // todo
        return(var_dump($options_temp));
    }
    
    function change_markers( $options, $marker_ids ) {
        $gim_marker_map = $this->gim_marker_map;
        
        $current_markers = array();
        foreach( $marker_ids as $k => $id ) {
            if ( isset( $gim_marker_map ) ) {
                foreach ( $gim_marker_map as $name => $type ) {
                    $array_name = "marker_".$name."_".$id;
                    switch( $type ) {
                        case 'input':
                            $current_markers[$k][ $name ] = isset( $options[ $array_name ] )
                                        ? sanitize_text_field( $options[ $array_name ] )
                                        : '';
                        break;
                    }
                }
            }
        }
        
        return $current_markers;
    }
    
    function add_marker( $new_options, $markers_count ) {
        $gim_marker_map = $this->gim_marker_map;
        $curr_options = $this->gim_options;
        if(!empty($curr_options["markers"])) {
            $id_keys = array_keys($curr_options["markers"]);
            $m_id = max($id_keys) + 1;
        } else { 
            $m_id = 0; 
        }

        $message = '';
        if ( !is_array( $options ) ) {
			$processed_array = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $new_options );
			parse_str( $processed_array, $output );
		} else {
			$output = $new_options;
		}
        
        if ( isset( $gim_marker_map ) ) {
			foreach ( $gim_marker_map as $name => $type ) {
                $array_name = "new_".$name;
                switch( $type ) {
                    case 'input':
                        $options_temp["markers"][$m_id][ $name ] = isset( $output[ $array_name ] )
                                    ? sanitize_text_field( $output[ $array_name ] )
                                    : '';
                    break;
                }
            }
        }        
        $options_temp["markers"] = array_merge($curr_options["markers"], $options_temp["markers"]);
    
        $this->update_option($options_temp, true);
        
        if ($markers_count % 2 == 0) { $alt = "alternate"; }
        $row = '<tr class="row_'.$m_id.' '.$alt.'">
            <td>Marker #'.$m_id.'</td>
            <td><input type="text" name="marker_name_'.$m_id.'" value="'. $options_temp["markers"][$m_id]['name'] .'" /></td>
            <td><input type="text" name="marker_link_'.$m_id.'" value="'. $options_temp["markers"][$m_id]['link'] .'" placeholder="#" /></td>
            <td><input type="text" name="marker_lat_'.$m_id.'" value="'. $options_temp["markers"][$m_id]['lat'] .'" /></td>
            <td><input type="text" name="marker_long_'.$m_id.'" value="'. $options_temp["markers"][$m_id]['long'] .'" /></td>
            <td>
                <img src="'. $options_temp["markers"][$m_id]['img_link'] .'" class="image" alt="Marker image" title="Marker image" width="30" height="30"/>
                <input type="hidden" name="marker_img_link_'.$m_id.'" class="hidden" value="'. $options_temp["markers"][$m_id]['img_link'] .'" />
                <button name="upload_marker_image" class="button" value="'.$m_id.'">'.__('Upload marker image','GIM').'</button>
            </td>
            <td>
                <button name="update" class="button" value="'.$m_id.'">'.__('Update','GIM').'</button>
                <button name="remove" class="button" value="'.$m_id.'">'.__('Remove','GIM').'</button>
            </td>
          </tr>';
        
        return($row);
    }
    
    function remove_marker( $id ) {
        $options = $this->gim_options;
        $message = '';
        
        if( (!empty($options["markers"])) && ( (!empty($id)) || $id == 0 ) ) {
            unset($options["markers"][$id]);
            $this->update_option($options, false);
            
            $message = "removed " . $id; // todo
            return($message);
        } else { 
            $message = "err"; // todo
            return($message);
        }
    }
    
    function update_marker( $id, $updated_options ) {
        $options = $this->gim_options;
        $gim_marker_map = $this->gim_marker_map;
        $message = '';
        
        if ( !is_array( $updated_options ) ) {
			$processed_array = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $updated_options );
			parse_str( $processed_array, $output );
		} else {
			$output = $updated_options;
		}
        
        if( is_array($options["markers"][$id]) && !empty($options["markers"][$id]) && isset( $gim_marker_map ) ) {
            foreach ( $gim_marker_map as $name => $type ) {
                $array_name = "marker_".$name."_".$id;
                switch( $type ) {
                    case 'input':
                        $options["markers"][$id][ $name ] = isset( $output[ $array_name ] )
                                    ? sanitize_text_field( $output[ $array_name ] )
                                    : '';
                    break;
                }
            }            
            $this->update_option($options, true);
            
            $message = "updated " .$id; // todo
            return(var_dump($options));
        } else {
            $message = "err"; // todo
            return($message);
        }
    }
    
    function update_option( $update_array, $merge ) {
		$gim_options = $this->gim_options;
        if($merge) {
            $updated_options = array_merge( $gim_options, $update_array );
        } else {
            $updated_options = $update_array;
        }
		update_option( 'gim_options', $updated_options );
	}
    
    /* 
     *  Display options page
     */
    function options_page() {
        $options = $this->gim_options;
        $map_google_key = $options['google_key'] ? $options['google_key'] : "";
        
        $map_image_enabled = $options['image_enable'] ? "checked" : "";
        $map_image_link = $options['image_link'] ? $options['image_link'] : "";
        $map_town_location = $options['town_location'] ? $options['town_location'] : "";
        
        $map_markers_enable = $options['markers_enable'] ? "checked" : "";
        $map_markers_link_only = $options['markers_link_only'] ? "checked" : "";
        $map_markers_onclick_redirect = $options['markers_onclick_redirect'] ? "checked" : "";
        
        $developer_mode = $options['developer_mode'] ? "checked" : "";
        
        
        $page = '<div id="gim_wrapper">
            <h2>Google Image Map settings</h2>
            
            <div class="image_link" style="float: right">
                <form method="post" enctype="multipart/form-data" id="upload_map_tiles" class="wp-upload-form" action="#">
                    <input type="file" accept="application/zip" id="mapzip" name="mapzip">
                    <button id="submit_upload_map_tiles" class="button" disabled="">'. __('Upload map tiles','GIM') .'</button>	
                </form>
                <img src="'. $map_image_link .'" class="image" alt="Image Map" title="Image Map" width="400" height="300"/>
                <input type="hidden" class="hidden" name="image_link" value="'. $map_image_link .'"  />
            </div>
            
            <form method="post" action="#" id="gim_options">
                <div class="map_settings">
                    <div class="map_google_key">
                        <label for="google_key">'. __('Google API key','GIM') .'</label>
                        <input type="text" name="google_key" value="'. $map_google_key .'"  />
                    </div>
                    <div class="map_image_enable">
                        <label for="image_enable">'. __('Use Image in map','GIM') .'</label>
                        <input type="checkbox" name="image_enable" '. $map_image_enabled .'  />
                    </div>
                    <div class="map_town_location hidden">
                        <label for="town_location">'. __('Location in map','GIM') .'</label>
                        <input type="text" name="town_location" value="'. $map_town_location .'"  />
                    </div>
                    <div class="map_markers_enable">
                        <label for="markers_enable">'. __('Enable markers','GIM') .'</label>
                        <input type="checkbox" name="markers_enable" '. $map_markers_enable .'  />
                    </div>
                    <div class="map_markers_link_only">
                        <label for="markers_link_only">'. __('Markers as link only','GIM') .'</label>
                        <input type="checkbox" name="markers_link_only" '. $map_markers_link_only .'  />
                    </div>
                    <div class="map_markers_onclick_redirect">
                        <label for="markers_onclick_redirect">'. __('Redirect on marker click','GIM') .'</label>
                        <input type="checkbox" name="markers_onclick_redirect" '. $map_markers_onclick_redirect .'  />
                    </div>
                </div>
                <div class="map_markers">
                <table class="markers_table wp-list-table widefat">
                    <thead>
                    <tr>
                        <th>'. __('#ID','GIM') . '</th>
                        <th>'. __('Name','GIM') .'</th>
                        <th>'. __('Link','GIM') .'</th>
                        <th>'. __('Latitude','GIM') .'</th>
                        <th>'. __('Longtitude','GIM') .'</th>
                        <th>'. __('Image','GIM') .'</th>
                        <th>'. __('Actions','GIM') .'</th>
                    </tr>
                    </thead>
                    <tbody>';
        
                    $markers = $options['markers'] ? $options['markers'] : array();
                    $alt_counter = 0;
                    foreach($markers as $id => $marker) {
                        $alt = "";
                        if(!(bool)($alt_counter & 1)) { $alt = 'alternate'; }
                        $page .= '<tr class="row_'.$id.' '.$alt.'">
                                <td>Marker #'.$id.'</td>
                                <td><input type="text" name="marker_name_'.$id.'" value="'. $marker['name'] .'" /></td>
                                <td><input type="text" name="marker_link_'.$id.'" value="'. $marker['link'] .'" placeholder="#" /></td>
                                <td><input type="text" name="marker_lat_'.$id.'" value="'. $marker['lat'] .'" /></td>
                                <td><input type="text" name="marker_long_'.$id.'" value="'. $marker['long'] .'" /></td>
                                <td>
                                    <img src="'. $marker['img_link'] .'" class="image" alt="Marker image" title="Marker image" width="30" height="30"/>
                                    <input type="hidden" name="marker_img_link_'.$id.'" class="hidden" value="'. $marker['img_link'] .'" />
                                    <button name="upload_marker_image" class="button" value="'.$id.'">'.__('Upload marker image','GIM').'</button>
                                </td>
                                <td>
                                    <button name="update" class="button" value="'.$id.'">'.__('Update','GIM').'</button>
                                    <button name="remove" class="button" value="'.$id.'">'.__('Remove','GIM').'</button>
                                </td>
                              </tr>';
                        $alt_counter++;
                    }        
                    $page .= '</tbody>
                    <tfoot>
                    <tr class="new">
                        <td>'. __('Add marker','GIM') .'</td>
                        <td><input type="text" name="new_name"/></td>
                        <td><input type="text" name="new_link" value="" placeholder="#" /></td>
                        <td><input type="text" name="new_lat" value="" /></td>
                        <td><input type="text" name="new_long" value="" /></td>
                        <td>
                            <img src="" class="image" alt="Marker image" title="Marker image" width="30" height="30"/>
                            <input type="hidden" name="new_img_link" class="hidden" value="" />
                            <button name="upload_marker_image" class="button" value="new">'.__('Upload marker image','GIM').'</button>
                        </td>
                        <td><button name="save_new_marker" class="button">'. __('Add','GIM') .' </button></td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="map_developer_mode">
                <label for="developer_mode">'. __('Developer mode','GIM') .'</label>
                <input type="checkbox" name="developer_mode" '. $developer_mode .'  />
            </div>
            
            <input type="submit" name="save_gim_options" class="save_gim_options button button-primary button-large" value="'. __('Save settings','GIM') .'" />
            </form>
        </div>';
        
        echo $page;
    } 
}