<?php
// TODO : fix up admin google maps script loading for real server (1st load shortcode and then load script)
// - complete marker filtering
// - shortcode should allow change dimensions (attrs)
// - connect use image map, enable markers (php localize and if in js)
// Feature: shortcode could allow rewrite of all global settings
class GIM_Settings {
    var $gim_ajax;
    var $gim_options;
    var $gim_map_dirs;
    
    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        $plugin_file = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_{$plugin_file}", array( $this, 'add_settings_link' ) );
        
        require_once( GIM_PLUGIN_DIR . 'includes/ajax_calls.php' );
        $this->gim_ajax = new GIM_Ajax();
        $this->gim_options = $this->get_options_array();
        
        foreach(glob(GIM_UPLOADS_DIR . '/*', GLOB_ONLYDIR) as $dir) {
            $dirname[] = basename($dir);
        }
        $this->gim_map_dirs = $dirname;
    }
    
    /* 
     *  Menu changes
     */    
    function admin_menu() {
		$menu_page = add_submenu_page( 'options-general.php', __( 'Google Image Map', 'GIM' ), __( 'Google Image Map', 'GIM' ), 'manage_options', GIM_OPTIONS_PAGE, array( $this, 'options_page' ) );
		add_action( "admin_print_scripts-{$menu_page}", array( $this, 'load_admin_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
	}
    
    function add_settings_link( $links ) {
		$settings_link = sprintf( '<a href="options-general.php?page=gim_options">%1$s</a>', __( 'Settings', 'GIM' ) );
		array_unshift( $links, $settings_link );
		return $links;
	}
    
    function load_admin_js() {
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        
		wp_enqueue_script( 'gim-admin', GIM_PLUGIN_URI . '/js/admin.js', array( 'jquery' ), GIM_PLUGIN_VERSION, false );
		//wp_enqueue_script( 'jquery-ui-sortable', array( 'jquery' ), GIM_PLUGIN_VERSION, true );

		wp_localize_script( 'gim-admin', 'gimSettings', array(
			'gim_nonce' => wp_create_nonce( 'gim_nonce' ),
			'ajaxurl'   => admin_url( 'admin-ajax.php', $this->protocol ),
		) );
        
        add_action( 'admin_footer', array( $this , 'add_gmaps_tag' ), 101 );
	}
    
    function load_admin_css() {
        wp_enqueue_style('thickbox');
	}
    
    public static function get_options_array() {
		return get_option( 'gim_options' ) ? get_option( 'gim_options' ) : array();
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
        
        $gim_map_preview_select = $this->display_maps( $options['map_preview_dir'], -1 );
        $gim_map_preview = do_shortcode('[google-image-map map="'. $options['map_preview_dir'] .'"]');
        
        $markers_display_count = $options['markers_display_count'];
        
        $developer_mode = $options['developer_mode'] ? "checked" : "";
        
        $page = '<div id="gim_wrapper">
            <h2>Google Image Map settings</h2>
            
            <form method="post" enctype="multipart/form-data" id="upload_map_tiles" class="wp-upload-form" action="#">
                <input type="file" accept="application/zip" id="mapzip" name="mapzip">
                <button id="submit_upload_map_tiles" class="button" disabled="">'. __('Upload map tiles','GIM') .'</button>	
            </form>
            
            <form method="post" action="#" id="gim_options">
                <div class="map_settings" style="float: left;">
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
                
                <div class="map_preview" style="float: right;">
                    '. $gim_map_preview_select .'
                    <input type="checkbox" name="developer_mode" '. $developer_mode .'  />
                    <label for="developer_mode">'. __('Developer mode','GIM') .'</label>
                    '. $gim_map_preview .'
                </div>
                
                <div class="map_markers clearfix" style="clear: both;">
                    <label for="markers_display_count">'. __('Number of markers on page: ','GIM') .'</label>
                    <select name="markers_display_count">
                        <option value="10"'. (($markers_display_count == 10) ? 'selected' : '') .'>10</option>
                        <option value="25"'. (($markers_display_count == 25) ? 'selected' : '') .'>25</option>
                        <option value="50"'. (($markers_display_count == 50) ? 'selected' : '') .'>50</option>
                        <option value="100"'. (($markers_display_count == 100) ? 'selected' : '') .'>100</option>
                        <option value="200"'. (($markers_display_count == 200) ? 'selected' : '') .'>200</option>
                    </select>
                    <table class="markers_table wp-list-table widefat">
                        <thead>
                        <tr>
                            <th>'. __('#ID','GIM') . '</th>
                            <th>'. __('Name','GIM') .'</th>
                            <th>'. __('Link','GIM') .'</th>
                            <th>'. __('Map','GIM') .'</th>
                            <th>'. __('Latitude','GIM') .'</th>
                            <th>'. __('Longtitude','GIM') .'</th>
                            <th>'. __('Image','GIM') .'</th>
                            <th>'. __('Actions','GIM') .'</th>
                        </tr>
                        </thead>
                        <tbody>';

                        $markers = $options['markers'] ? $options['markers'] : array();
                        $count_markers = count($markers);
                        $alt_counter = 0;
                        $start = 0;
                        $limit = $start + $markers_display_count;
                        if(is_array($markers) && $count_markers > 0) {
                            for($i = $start; $i < $limit; $i++) {
                                $marker = $markers[$i];
                                if(is_array($marker) && !empty($marker) ) {
                                    $marker_map = $this->display_maps( $marker['map'], $i );

                                    $alt = "";
                                    if(!(bool)($alt_counter & 1)) { $alt = 'alternate'; }
                                    $page .= '<tr class="row_'.$i.' '.$alt.'">
                                            <td>Marker #'.$i.'</td>
                                            <td><input type="text" name="marker_name_'.$i.'" value="'. $marker['name'] .'" /></td>
                                            <td><input type="text" name="marker_link_'.$i.'" value="'. $marker['link'] .'" placeholder="#" /></td>
                                            <td>'. $marker_map .'</td>
                                            <td><input type="text" name="marker_lat_'.$i.'" value="'. $marker['lat'] .'" /></td>
                                            <td><input type="text" name="marker_long_'.$i.'" value="'. $marker['long'] .'" /></td>
                                            <td>
                                                <img src="'. $marker['img_link'] .'" class="image" alt="Marker image" title="Marker image" width="30" height="30"/>
                                                <input type="hidden" name="marker_img_link_'.$i.'" class="hidden" value="'. $marker['img_link'] .'" />
                                                <button name="upload_marker_image" class="button" value="'.$i.'">'.__('Upload marker image','GIM').'</button>
                                            </td>
                                            <td>
                                                <button name="update" class="button" value="'.$i.'">'.__('Update','GIM').'</button>
                                                <button name="remove" class="button" value="'.$i.'">'.__('Remove','GIM').'</button>
                                            </td>
                                          </tr>';
                                    $alt_counter++;
                                }
                            }   
                        }

                        $new_marker_map = $this->display_maps( "", "new" );
                        $page .= '</tbody>
                        <tfoot>
                        <tr class="new">
                            <td>'. __('Add marker','GIM') .'</td>
                            <td><input type="text" name="new_name"/></td>
                            <td><input type="text" name="new_link" value="" placeholder="#" /></td>
                            <td>'. $new_marker_map .'</td>
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
                    <button name="previous_markers" class="button" disabled>'. __('Prev','GIM') .' </button>
                    <button name="next_markers" class="button">'. __('Next','GIM') .' </button>
                </div>
            
            <input type="submit" name="save_gim_options" class="save_gim_options button button-primary button-large" value="'. __('Save settings','GIM') .'" />
            </form>
        </div>';
        
        echo $page;
    } 
    
    function display_maps( $current_map, $current_marker ) {
        $map_dirs = $this->gim_map_dirs;
        if(is_array($map_dirs) && !empty($map_dirs)) {
            $name = "map_preview_dir";
            if($current_marker === "new") {
                $name = "new_map";
            } else if($current_marker >= 0) {
                $name = "marker_map_" . $current_marker;
            }
            
            $selects .= '<select name="'. $name .'" id="'. $name .'">';
            foreach($map_dirs as $k => $dir) {
                $current = ($dir == $current_map) ? " selected" : "";
                $selects .= '<option value="'. $dir .'"'. $current .'>'. $dir .'</value>';
            }
            $selects .= '</select>';
        }
        return $selects;
    }
    
    function add_gmaps_tag() {
        $options = $this->gim_options;
        if( !empty($options["google_key"]) ) {
            echo '<script src="https://maps.googleapis.com/maps/api/js?key='. $options["google_key"] .'&callback=initMap"
    async defer></script>';
        }
    }    
}