<?php
/**
 * Plugin Name: Numbers Control
 * Description: Allows central control of statistics numbers that change often on pages to be controled/updated globally in a CPT format
 * Version: 0.1
 * Author: Chris Cline
 * Author URI: http://www.christiancline.com
 */

// Exit if this wasn't accessed via WordPress (aka via direct access)
if (!defined('ABSPATH')) exit;

class NcNumbersPlugin {
    public function __construct() {

        //add metabox and save data
        add_action('add_meta_boxes', array($this, 'add_box'));
        add_action('save_post', array($this, 'save'));

        // Add CSS - if needed
        add_action('wp_enqueue_scripts', array($this,'enqueue'));

        //register the post type
        add_action('init', array($this,'registration'));

        //add the shortcode
        add_shortcode('nc_number', array($this,'shortcode'));
    }

    public function registration() {
        register_post_type(
            'nc-number',
            array(
                'labels' => array(
                    'name' => _x('NC Number', 'post type general name'),
                    'singular_name' => _x('NC Number', 'post type singular name'),
                    'add_new' => _x('Add NC Number', 'nc number'),
                    'add_new_item' => __('Add New NC Number'),
                    'edit_item' => __('Edit Number'),
                    'new_item' => __('New NC Number'),
                    'all_items' => __('All NC Numbers'),
                    'view_item' => __('View NC Number'),
                    'search_items' => __('Search NC Numbers'),
                    'not_found' =>  __('No NC Numbers'),
                    'not_found_in_trash' => __('No NC Numbers found in Trash'),
                    'parent_item_colon' => '',
                    'menu_name' => 'NC Numbers'
                ),
                'show_ui' => true,
                'show_in_menu' => true,
                'query_var' => false,
                'rewrite' => true,
                'publicly_queryable' => false,
                'exclude_from_search' => true,
                'capability_type' => 'post',
                'has_archive' => false,
                'hierarchical' => false,
                'menu_position' => 10,
                'supports' => array('title'),
                'public' => true,
            )
        );
    }

    // add metabox
    public function add_box() {

    $post_type = 'nc-number';
        //limit meta box to certain post types
        add_meta_box('nc-number-metabox',
        'NC Number',
        array($this, 'meta_box_function'),
        $post_type,
        'normal',
        'high');
    }

    //render number on metaboxes template
    public function meta_box_function($post) {
        // Add an nonce field so we can check for it later.
        wp_nonce_field('nc_number_check', 'nc_number_check_value');

        $myValue = get_post_meta($post -> ID, '_nc_number', true);
        $myId = get_the_ID();

        //metabox template
        include( plugin_dir_path( __FILE__ ) . 'templates/metaboxes.php');

    }

    public function save($post_id) {

        // Check if our nonce is set.
        if (!isset($_POST['nc_number_check_value']))
        return $post_id;

        $nonce = $_POST['nc_number_check_value'];

        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'nc_number_check'))
        return $post_id;

        // If this is an autosave, our form has not been submitted,
        //     so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;

        // Check the user's permissions.
        if ('page' == $_POST['post_type']) {

        if (!current_user_can('edit_page', $post_id))
        return $post_id;

        } else {

        if (!current_user_can('edit_post', $post_id))
        return $post_id;
        }

        /* OK, its safe for us to save the data now. */

        // Sanitize the user input.
        $data = sanitize_text_field($_POST['nc_number']);

        // Update the meta field.
        update_post_meta($post_id, '_nc_number', $data);
    }

    // shortcode function
    public function shortcode($atts) {

        //class for number span
        $myClass = isset($atts['class'])  ? $atts['class']  : '';
        //post ID
        $myId = isset($atts['id'])  ?  sanitize_text_field($atts['id'])  : $atts['id'] = '1';

    	//query args
    	$args = array(
    		 'post_type' => 'nc-number',
    		 'p' => $atts['id'],
    		 'posts_per_page' => '1',
    	 );

         // The Query
         $the_query = new WP_Query( $args );

         // The Loop get rid of twig
         if ( $the_query->have_posts() ) {

         	while ( $the_query->have_posts() ) {

                $output = '';
         		$the_query->the_post();

         	   return $output . '<span class="' . $myClass . '">' . get_post_meta( get_the_ID(), '_nc_number', true ) . '</span>';
         	}

         	/* Restore original Post Data */
         	wp_reset_postdata();
         }

    }

    // enqueue function for nc numbers css - if needed
    public function enqueue() {
        wp_enqueue_style('nc-numbers', plugins_url('css/nc-numbers.css', __FILE__), null, '1.0');
    }

}
// Let's do this thing!
$ncNumPlug = new  NcNumbersPlugin();
