<?php
/**
 * Plugin Name: Parts List Numbers
 * Description: Adds custom metabox and field to products for tying parts to parts diagram. Also adds option page for setting IDs of pages that will display the numbers.
 * Version: 0.1
 * Author: Chris Cline
 * Author URI: http://www.christiancline.com
 */

// Exit if this wasn't accessed via WordPress (aka via direct access)
if (!defined('ABSPATH')) exit;

class PartNumbersPlugin {
    public function __construct() {
        //add metabox and save data
        add_action('add_meta_boxes', array($this, 'add_box'));
        add_action('save_post', array($this, 'save'));
        //display parts number before title
        add_action('woocommerce_before_shop_loop_item_title', array($this, 'display_number'));
        // add options Page
        add_action( 'admin_menu', array( $this, 'add_page_id_options' ) );
		add_action( 'admin_init', array( $this, 'page_id_options_init' ) );
        // add stylesheet
        add_action('wp_enqueue_scripts', array($this,'enqueue'));
        //add parts number field to shortcode sort option
        add_filter('woocommerce_shortcode_products_query', array( $this, 'add_parts_number_to_shortcode' ), 10, 2);
    }
    // shortcode parts number sort option
    function add_parts_number_to_shortcode ($args, $atts) {
        //if is part for diagram 1
        if ($atts['orderby'] == "parts-number") {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_parts_number';
        }
        //if is part for diagram 2
        if ($atts['orderby'] == "parts-number-2") {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_parts_number_2';
        }
        //if is part for diagram 3
        if ($atts['orderby'] == "parts-number-3") {
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_parts_number_3';
        }
        return $args;
        return $atts;
    }
    // add metabox
    public function add_box() {
        //limit meta box to 'product' post types
        $post_type = 'product';
        add_meta_box('parts-number-metabox',
        'Parts List Numbers',
        array($this, 'meta_box_function'),
        $post_type,
        'normal',
        'low');
    }
    //render numbers on metaboxes template
    public function meta_box_function($post) {
        // Add an nonce field so we can check for it later.
        wp_nonce_field('parts_number_check', 'parts_number_check_value');
        $parts_number = get_post_meta($post->ID, '_parts_number', true);
        $parts_number2 = get_post_meta($post->ID, '_parts_number_2', true);
        $parts_number3 = get_post_meta($post->ID, '_parts_number_3', true);
        //metabox template
        include( plugin_dir_path( __FILE__ ) . 'templates/metaboxes.php');
    }
    public function save($post_id) {
        // Check if our nonce is set.
        if (!isset($_POST['parts_number_check_value']))
        return $post_id;

        $nonce = $_POST['parts_number_check_value'];
        // Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce, 'parts_number_check'))
        return $post_id;
        // If this is an autosave, our form has not been submitted,
        // so we don't want to do anything.
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
        $data = sanitize_text_field($_POST['parts_number']);
        $data2 = sanitize_text_field($_POST['parts_number_2']);
        $data3 = sanitize_text_field($_POST['parts_number_3']);

        // Update the meta field.
        update_post_meta($post_id, '_parts_number', $data);
        update_post_meta($post_id, '_parts_number_2', $data2);
        update_post_meta($post_id, '_parts_number_3', $data3);
    }
    //display the number in the product loop
    public function display_number(){

        //get the comma-separated page ids from our options page
        $page_ids_options = get_option( 'page_id_option_name' ); // Array of all options
        $page_ids_0 = $page_ids_options['page_ids_0']; //comma separated string of our page ids
        // convert to int. array
        $the_pages = $page_ids_0;
        $page_array = explode(',', $the_pages);
        //if pages are in the array display the parts numbers
        if (is_page($page_array)) {

    	global $post;
    	$part_number = get_post_meta($post->ID, '_parts_number', true);
        $part_number2 = get_post_meta($post->ID, '_parts_number_2', true);
        $part_number3 = get_post_meta($post->ID, '_parts_number_3', true);

            //only display if not empty
            if($part_number || $part_number2 || $part_number3 ){
                echo'
                <div class="part-no-wrap">
                <ul class="part-no">'.
                    '<li class="diagram-1">'. $part_number .'</li>
                    <li class="diagram-2">'. $part_number2 .'</li>
                    <li class="diagram-3">'. $part_number3 .'</li>
                 </ul><span>on diagram</span></div>';
            }
        }
    }
    // build our options page
    public function add_page_id_options() {
		add_menu_page(
			'Parts list Page IDs', //page_title
			'Parts List Page IDs', //menu_title
			'manage_options', //capability
			'parts-list-page-ids', //menu_slug
			array( $this, 'create_admin_page' ), //function
			'dashicons-admin-generic', //icon_url
			100 //position
		);
	}
	public function create_admin_page() {
		$this->page_ids_options = get_option( 'page_id_option_name' ); ?>

		<div class="wrap">
			<h2>Parts List Page IDs</h2>
			<p>Add comma-separated page ids for the pages you want to display the products with parts numbers on.</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'page_id_option_group' );
					do_settings_sections( 'page-ids-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function page_id_options_init() {
		register_setting(
			'page_id_option_group', // option_group
			'page_id_option_name', // option_name
			array( $this, 'sanitize' ) // sanitize_callback
		);
		add_settings_section(
			'page_ids_setting_section', // id
			'', // title
			array( $this, 'page_ids_setting_section_info' ), // callback
			'page-ids-admin' // page
		);
		add_settings_field(
			'page_ids_0', // id
			'Comma-separated page IDs:', // title
			array( $this, 'page_ids_0_callback' ), // callback
			'page-ids-admin', // page
			'page_ids_setting_section' // section
		);
	}
	public function sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['page_ids_0'] ) ) {
			$sanitary_values['page_ids_0'] = sanitize_text_field( $input['page_ids_0'] );
		}

		return $sanitary_values;
	}
	public function page_ids_setting_section_info() {
        echo 'EXAMPLE: 123,456,1780 etc.';
    }
	public function page_ids_0_callback() {
		printf(
			'<input class="regular-text" type="text" name="page_id_option_name[page_ids_0]" id="page_ids_0" value="%s">',
			isset( $this->page_ids_options['page_ids_0'] ) ? esc_attr( $this->page_ids_options['page_ids_0']) : ''
		);
	}
    // enqueue function for nc numbers css - if needed
   public function enqueue() {

       wp_enqueue_style('part-numbers', plugins_url('css/part-numbers.css', __FILE__), NULL, '1.0');
   }
}
// Let's do this thing!
$partNumPlug = new  PartNumbersPlugin();
