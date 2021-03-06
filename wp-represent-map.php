<?php
/*
Plugin Name: WP Represent Map
Plugin URI: https://github.com/andrebian/wp-represent-map
Description: Allow represent map pins management in wordpress. This plugin was inspired on original Represent Map (https://github.com/abenzer/represent-map)
Version: 0.3
Author: Andre Cardoso aka andrebian
Author URI: http://andrebian.com
Text Domain: wp-represent-map
License: Creative Commons (http://creativecommons.org/licenses/by-sa/2.0/)
*/

add_action('init', 'wp_represent_map_start_session', 1);
add_action('wp_logout', 'wp_represent_map_end_session');
add_action('wp_login', 'wp_represent_map_end_session');

function wp_represent_map_start_session()
{
    if (!session_id()) {
        session_start();
    }
}

function wp_represent_map_end_session()
{
    session_destroy();
}

add_action('init', 'do_output_buffer');
function do_output_buffer() {
        ob_start();
}

/*
 * Calling all required load files
 */
    
    // Options page
    require plugin_dir_path(__FILE__) . 'includes/admin/options.php';

    // Ajax call
    require plugin_dir_path(__FILE__) . 'includes/admin/ajax-actions.php';

    // Meta boxes
    require plugin_dir_path(__FILE__) . 'includes/admin/meta-boxes.php';

    // Including Shortcodes
    require plugin_dir_path(__FILE__) . 'includes/shortcodes/shortcode-represent-map.php';


    // Including path configurations
    require plugin_dir_path(__FILE__) . 'includes/general/creation-path-to-uploads.php';


    // including upload
    if ( is_admin() ) {
        require plugin_dir_path(__FILE__) . 'classes/Upload.php';
    }


/*
 * Adding a custom options page
 * 
 * Options will be improved yet, by the moment just the default city
 * and lat lng is able to configure
 */
add_action('admin_menu', 'wp_represent_map_options_page');
function wp_represent_map_options_page() 
{
    add_options_page( 'WP Represent Map', 'WP Represent Map', 7, __FILE__, 'manage_options_for_wp_represent_map');  
}


// Define the custom post type
function type_post_represent_map() {
        $labels = array(
                'name' => __( 'Map items', 'wp-represent-map' ),
                'singular_name' => __( 'Map item', 'wp-represent-map' ),
                'add_new' => __( 'Add new', 'wp-represent-map' ),
                'add_new_item' => __( 'New item', 'wp-represent-map' ),
                'edit_item' => __( 'Edit item', 'wp-represent-map' ),
                'new_item' => __( 'New item', 'wp-represent-map' ),
                'view_item' => __( 'View item', 'wp-represent-map' ),
                'search_items' => __( 'Search items', 'wp-represent-map' ),
                'not_found' => __( 'No registers found', 'wp-represent-map' ),
                'not_found_in_trash' => __( 'No register found in trash', 'wp-represent-map' ),
                'parent_item_colon' => '',
                'menu_name' => __('Map Items', 'wp-represent-map')
        );
        $args = array(
                'labels' => $labels,
                'public' => true,
                'public_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'rewrite' => true,
                'capability_type' => 'post',
                'has_archive' => true,
                'hierarchical' => false,
                'menu_position' => 5,
                'menu_icon' => plugin_dir_url(__FILE__) . '/assets/img/wp-represent-map.png',
                'register_meta_box_cb' => 'wp_represent_map_custom_boxes',
                'supports' => array( 'title' )
        );
        register_post_type( 'represent_map', $args );
        
        register_taxonomy(
            'represent_map_type', 'represent_map', array(
                'label' => __('Type', 'wp-represent-map'),
                'singular_label' => __('Type', 'wp-represent-map'),
                'rewrite' => true,
                'hierarchical' => true
                    )
        );

        flush_rewrite_rules();
}
add_action( 'init', 'type_post_represent_map' );


/**
 * Translation files 
 */
load_plugin_textdomain( 'wp-represent-map', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );


/**
 * Adding the meta boxes
 * 
 * All meta boxes will be called from here
 */
function wp_represent_map_custom_boxes()
{
    add_meta_box('meta_box_item_map_info', __('Item info', 'wp-represent-map'), 'meta_box_item_map_info', 'represent_map', 'side', 'default');
}
add_action('add_meta_boxes', 'wp_represent_map_custom_boxes');
add_action( 'save_post', 'meta_box_item_map_info_save' );

/**
 * Enqueue all necessary scripts and styles to Represent Map
 */
function wp_represent_map_scripts() 
{
	wp_enqueue_style( 'represent-map', plugins_url( 'assets/css/represent-map.css', __FILE__ ) );

	wp_enqueue_script( 'jquery', includes_url('js/jquery/jquery.js') );
	wp_enqueue_script( 'google.maps', 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&language=pt_BR' );
	wp_enqueue_script( 'typeahead', plugins_url( 'assets/js/bootstrap-typeahead.js', __FILE__ ) );
	wp_enqueue_script( 'label', plugins_url( 'assets/js/label.js', __FILE__ )  );
	
}
add_action( 'wp_enqueue_scripts', 'wp_represent_map_scripts' );


/**
 * Ajax call
 * 
 * All the actions performed by ajax will be called from here
 */
add_action( 'wp_ajax_get_lat_lng', 'get_lat_lng' );
add_action( 'wp_ajax_nopriv_get_lat_lng', 'get_lat_lng' );


$_SESSION['message'] = false;


/**
 * 
 * @param stdClass $post_categories
 * @return array
 */
function parse_types( $post_categories )
{
    $categories = array();
    foreach($post_categories as $pc) {
        $categories[] = $pc->term_id . '-' . $pc->slug;
    }
    
    return $categories;
}