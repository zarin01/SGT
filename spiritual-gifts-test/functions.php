<?php

/**

 * @author Divi Space

 * @copyright 2017

 */

if (!defined('ABSPATH')) die();



function ds_ct_enqueue_parent() { wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' ); }

function ds_ct_loadjs() {

	wp_enqueue_script( 'ds-script', '/wp-content/themes/spiritual-gifts-test/ds-script.js', array( 'jquery' ), 1.2, true );

	wp_localize_script( 'ds-script', 'ajax',
		array( 'url' => admin_url( 'admin-ajax.php' ), 'myaccount' => '/my-account/' ) );
}


add_action( 'wp_enqueue_scripts', 'ds_ct_enqueue_parent' );

add_action( 'wp_enqueue_scripts', 'ds_ct_loadjs' );



include('login-editor.php');

function et_pb_extend_supported_post_types( $post_types ) {
	$new_post_types = array(
'sfwd-assignment',
'sfwd-courses',
'sfwd-lessons',
'sfwd-quiz',
'sfwd-topic',
'sfwd-certificates',
'memberpressproduct',
'memberpressgroup'
);

return array_merge( $post_types, $new_post_types );
}
add_filter( 'et_builder_post_types', 'et_pb_extend_supported_post_types' );

// rename the "Have a Coupon?" message on the checkout page
function woocommerce_rename_coupon_message_on_checkout() {
	return 'Have a Promo Code?' . ' <a href="#" class="showcoupon">' . __( 'Click here to enter your code', 'woocommerce' ) . '</a>';
}
add_filter( 'woocommerce_checkout_coupon_message', 'woocommerce_rename_coupon_message_on_checkout' );
// rename the coupon field on the checkout page
function woocommerce_rename_coupon_field_on_checkout( $translated_text, $text, $text_domain ) {
	// bail if not modifying frontend woocommerce text
	if ( is_admin() || 'woocommerce' !== $text_domain ) {
		return $translated_text;
	}
	if ( 'Coupon code' === $text ) {
		$translated_text = 'Coupon';

	} elseif ( 'Apply Coupon' === $text ) {
		$translated_text = 'Apply Promo Code';
	}
	return $translated_text;
}
add_filter( 'gettext', 'woocommerce_rename_coupon_field_on_checkout', 10, 3 );

function reset_pass_url() { $siteURL = get_option('siteurl'); return "{$siteURL}/wp-login.php?action=lostpassword"; } add_filter( 'lostpassword_url', 'reset_pass_url', 11, 0 );

// Add custom user roles
$result = add_role( 'spiritualgiftstest', __( 'Spiritual Gifts Test' ), array('read' => true,) );
$result = add_role( 'personalitytest', __( 'Personality Test' ), array('read' => true,) );
$result = add_role( 'churchadmin', __( 'Church Admin' ), array('read' => true,) );

// Add role class to body
function add_role_to_body($classes) {
	global $current_user;
	$user_role = array_shift($current_user->roles);

    if(is_user_logged_in()){
        $user_roles = get_userdata(get_current_user_id())->roles;

        if ($user_roles && is_array($user_roles) && count($user_roles) > 0) {
            $user_role = reset($user_roles);
        }
    }

	if(is_array($classes))
		$classes[] = 'role-'. $user_role;
	else
		$classes .= ' role-'. $user_role;
		
	return $classes;
}
add_filter('body_class','add_role_to_body');
add_filter('admin_body_class', 'add_role_to_body');

add_action('after_setup_theme', 'remove_admin_bar');

function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
		show_admin_bar(false);
	}
}

add_action('gform_after_submission_4', 'set_post_password', 10, 2);
function set_post_password($entry, $form) {
	if(check_not_empty($entry[12],1)) {
		$post = get_post($entry['post_id']);
		$post->post_password = $entry[12];
		wp_update_post($post);
	}
}
function check_not_empty($s, $include_whitespace = false){
	if ($include_whitespace) {
		// make it so strings containing white space are treated as empty too
		$s = trim($s);
	}
	return (isset($s) && strlen($s)); // var is set and not an empty string ''
}


function create_posttype() {
	register_post_type( 'churches',
		array(
			'labels' => array(
				'name' => __( 'Churches' ),
				'singular_name' => __( 'Church' )
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'churches'),
		)
	);
}
add_action( 'init', 'create_posttype' );

function create_posttype_sgt_results() {
	register_post_type( 'sgtresults',
		array(
			'labels' => array(
				'name' => __( 'SGT Results' ),
				'singular_name' => __( 'SGT Result' )
			),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'sgtresults'),
		)
	);
}
add_action( 'init', 'create_posttype_sgt_results' );

function create_posttype_combined_results() {
	register_post_type( 'combinedresults',
		array(
			'labels' => array(
				'name' => __( 'Combined Results' ),
				'singular_name' => __( 'Combined Result' )
			),
			'taxonomies' => array('tag', 'post_tag'),
			'public' => true,
			'has_archive' => true,
			'rewrite' => array('slug' => 'combinedresults'),
		)
	);
}
add_action( 'init', 'create_posttype_combined_results' );


add_filter( 'gform_validation_message', 'change_message', 10, 2 );
function change_message( $message, $form ) {
	return "<div class='validation_error'>Oops, it looks like you missed something.</div>";
}

add_filter( 'gform_field_validation', 'change_fieldmessage', 10, 4 );
function change_fieldmessage( $result, $value, $form, $field ) {
	$result['message'] = 'You missed this one';
	return $result;
}

//add_action('init', 'test_sgt');
function test_sgt() {
	global $wpdb;
	if(is_page(3702)) {

		echo '<pre>';
		print_r($wpdb);
		die();
	}
}

/** Disable Ajax Call from WooCommerce */
add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments', 11);
function dequeue_woocommerce_cart_fragments() { if (is_front_page()) wp_dequeue_script('wc-cart-fragments'); }

// Disable Gravity Forms Notifications on User Registration Forms
if ( ! function_exists( 'gf_new_user_notification' ) ) {
	function gf_new_user_notification( $user_id, $plaintext_pass = '', $notify = '' ) {
		return;
	}
}

// Gravity Forms Custom Merge Tag

add_filter( 'gform_replace_merge_tags', 'replace_results_summary', 10, 7 );
function replace_results_summary( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {

	$custom_merge_tag = '{results_summary}';

	if ( strpos( $text, $custom_merge_tag ) === false ) {
		return $text;
	}

	$download_link = gform_get_meta( $entry['id'], 'gfmergedoc_download_link' );
	$text = str_replace( $custom_merge_tag, $download_link, $text );

	return $text;
}

// Email users automatically when they take a test
add_filter( 'gform_notification_6', 'email_user_results_auto', 10, 3 );
add_filter( 'gform_notification_7', 'email_user_results_auto', 10, 3 );
function email_user_results_auto($notification, $form, $entry){

	if($notification['name'] != "Email User")
		return $notification;
    if(is_numeric($_REQUEST['userid']))
        $user = new SGTChurchMember($_REQUEST['userid']);
    else
	    $user = new SGTChurchMember($entry['created_by']);

	if($user->has_quiz('sgt'))
		$notification['message'] .= $user->the_quiz('sgt')->email_summary();
	if($user->has_quiz('personality'))
		$notification['message'] .= $user->the_quiz('personality')->email_summary();

	$notification['message'] = str_replace('{user:display_name}', $user->data->display_name, $notification['message']);
    $notification['message'] = str_replace('{user:user_email}', $user->data->user_email, $notification['message']);

	return $notification;
}

// Add Records to Gravity Forms Message Field
add_filter( 'gform_notification_25', 'add_results_to_notification', 10, 3 );

function add_results_to_notification($notification, $form, $entry){

	if($notification['name'] != "Send Email")
		return $notification;
    if(is_numeric($_REQUEST['userid']))
        $user = new SGTChurchMember($_REQUEST['userid']);
    else
	    $user = new SGTChurchMember($entry['created_by']);

	if($user->has_quiz('sgt'))
		$notification['message'] .= $user->the_quiz('sgt')->email_summary();
	if($user->has_quiz('personality'))
		$notification['message'] .= $user->the_quiz('personality')->email_summary();

	$email = $entry['1'];

	$notification['message'] = str_replace('{user:display_name}', $user->data->display_name, $notification['message']);
    $notification['message'] = str_replace('{user:user_email}', $user->data->user_email, $notification['message']);
	$notification['to']     = $email;

	error_log("yeh boi: " . $email);

	return $notification;
}

// Add Records to Gravity Forms Message Field
add_filter( 'gform_notification_7', 'send_sgt_results_to_admin', 10, 3 );
add_filter( 'gform_notification_9', 'send_sgt_results_to_admin', 10, 3 );

function send_sgt_results_to_admin($notification, $form, $entry){

	if($notification['name'] != "Church Admin Notification")
		return $notification;

	$user = new SGTChurchMember($entry['created_by']);
	if(!$user->get_church_code()) {
		GFCommon::log_debug('Email Admin - No Church Found:' . print_r($entry, 1));
		return NULL;
	}

	$admins = get_users(array('meta_key' => 'church_admin_code', 'meta_value' => $user->get_church_code()));

	if(!$admins)
		return NULL;

	$notification['message']= $user->user_firstname.' '.$user->user_lastname.'<br>';
	$notification['message'] .= $user->user_email.'<br>';

	$notification['message'] .= $user->the_quiz('sgt')->email_summary();
	$notification['to']     = $admins[0]->data->user_email;

	GFCommon::log_debug('Email User - Notification Sent:' . print_r($notification, 1));

	return $notification;
}

add_filter( 'gform_notification_6', 'send_personality_results_to_admin', 10, 3 );

function send_personality_results_to_admin($notification, $form, $entry){

	if($notification['name'] != "Church Admin Notification")
		return $notification;

	$user = new SGTChurchMember($entry['created_by']);
	if(!$user->get_church_code()) {
		GFCommon::log_debug('Email Admin - No Church Found:' . print_r($entry, 1));
		return NULL;
	}

	$admins = get_users(array('meta_key' => 'church_admin_code', 'meta_value' => $user->get_church_code()));

	if(!$admins)
		return NULL;

	$notification['message']= $user->user_firstname.' '.$user->user_lastname.'<br>';
	$notification['message'] .= $user->user_email.'<br>';

	$notification['message'] .= $user->the_quiz('personality')->email_summary();
	$notification['to']     = $admins[0]->data->user_email;

	GFCommon::log_debug('Email User - Notification Sent:' . print_r($notification, 1));

	return $notification;
}

function add_google_analytics(){
	?>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-10088477-1"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'UA-10088477-1');
	</script>
	<?php
}
add_action('wp_head','add_google_analytics');

add_action( 'load-users.php', function(){
    // Make sure the "action" is "delete".
    if ( 'delete' !== filter_input( INPUT_GET, 'action' ) ) {
        return;
    }

    add_filter( 'wp_dropdown_users_args', function( $query_args, $args ){
        if ( 'reassign_user' === $args['name'] ) {
            $query_args['role'] = 'administrator';
        }

        return $query_args;
    }, 10, 2 );
} );

//Over-ride Paypal Hash verification
add_filter( 'gform_paypal_hash_matches', '__return_true' );

add_filter( 'wpe_heartbeat_allowed_pages', function( $pages ) {
    global $pagenow;
    $pages[] =  $pagenow;
    return $pages;
});

function enqueue_embed_js() {
	if (is_page_template("embed.php")) {
		wp_enqueue_script("embed", get_stylesheet_directory_uri() . "/js/embed.js", array('jquery'));
		wp_localize_script('embed', 'the_ajax_script', array('ajaxurl' =>admin_url('admin-ajax.php')));
	}
}
add_action("wp_enqueue_scripts", "enqueue_embed_js");

remove_action( 'login_init', 'send_frame_options_header', 10, 0 );
remove_action( 'admin_init', 'send_frame_options_header', 10, 0 );

function IsNullOrEmptyString($str){
    return (!isset($str) || trim($str) === '');
}

// An ajax call that gets the selected products after adding a new product
function iframe_new_user_form_submit() {        
	$billing_first_name = 	sanitize_text_field($_REQUEST['billing_first_name']);
	$billing_last_name =	sanitize_text_field($_REQUEST['billing_last_name']);
	$billing_country = 		sanitize_text_field($_REQUEST['billing_country']);
	$billing_address_1 = 	sanitize_text_field($_REQUEST['billing_address_1']);
	$billing_address_2 = 	sanitize_text_field($_REQUEST['billing_address_2']);
	$billing_city = 		sanitize_text_field($_REQUEST['billing_city']);
	$billing_state = 		sanitize_text_field($_REQUEST['billing_state']);
	$billing_postcode = 	sanitize_text_field($_REQUEST['billing_postcode']);
	$billing_phone = 		sanitize_text_field($_REQUEST['billing_phone']);
	$billing_email = 		sanitize_text_field($_REQUEST['billing_email']);
	$account_username = 	sanitize_text_field($_REQUEST['account_username']);
	$account_password = 	sanitize_text_field($_REQUEST['account_password']);
	$accepted_terms = 		$_REQUEST['accepted_terms'];
	
	$has_error = false;
	$error_message = "";

	if (IsNullOrEmptyString($billing_first_name) || IsNullOrEmptyString($billing_last_name) || 
		IsNullOrEmptyString($billing_country) || IsNullOrEmptyString($billing_address_1) || 
		IsNullOrEmptyString($billing_city) || IsNullOrEmptyString($billing_state) || 
		IsNullOrEmptyString($billing_postcode) || IsNullOrEmptyString($billing_phone) || 
		IsNullOrEmptyString($billing_email) || IsNullOrEmptyString($account_username) || 
		IsNullOrEmptyString($account_password) || !$accepted_terms) {

		$has_error = true;
		$error_message = "Please fill out all required fields to continue. -Server";
	}

	if (email_exists($billing_email) != false) {
		$has_error = true;
		$error_message = "This email already has an account registered under it.";
	}

	if (username_exists($account_username) != false) {
		$has_error = true;
		$error_message = "This username already exists, please choose another one.";
	}

	if (!$has_error) {

		$new_user_data = array(
			'user_login' => $account_username,
			'user_pass'  => $account_password,
			'user_email' => $billing_email,
			'role'       => 'subscriber'
		);

		$user_id = wp_insert_user( $new_user_data );

		update_user_meta($user_id, 'billing_first_name', $billing_first_name);
		update_user_meta($user_id, 'billing_last_name', $billing_last_name);
		update_user_meta($user_id, 'billing_address_1', $billing_address_1);
		update_user_meta($user_id, 'billing_address_2', $billing_address_2);
		update_user_meta($user_id, 'billing_city', $billing_city);
		update_user_meta($user_id, 'billing_state', $billing_state);
		update_user_meta($user_id, 'billing_postcode', $billing_postcode);
		update_user_meta($user_id, 'billing_country', $billing_country);
		update_user_meta($user_id, 'billing_email', $billing_email);
		update_user_meta($user_id, 'billing_phone', $billing_phone);
		update_user_meta($user_id, 'first_name', $billing_first_name);
		update_user_meta($user_id, 'last_name', $billing_last_name);
		update_user_meta($user_id, 'nickname', $account_username);
		update_user_meta($user_id, 'msgtp1_capabilities', 'a:2:{s:18:"spiritualgiftstest";b:1;s:28:"unassignedspiritualgiftstest";b:1;}');

		global $wpdb;
		$wpdb->insert($wpdb->prefix . "wc_customer_lookup", array(
			'user_id' => $user_id,
			'username' => $account_username,
			'first_name' => $billing_first_name,
			'last_name' => $billing_last_name,
			'email' => $billing_email,
			'date_last_active' => gmdate('Y-m-d h:i:s', time()),
			'date_registered' => gmdate('Y-m-d h:i:s', time()),
			'country' => $billing_country,
			'postcode' => $billing_postcode,
			'city' => $billing_city,
			'state' => $billing_state,
		));

		// Set the global user object
		$current_user = get_user_by( 'id', $user_id );

		// set the WP login cookie
		$secure_cookie = is_ssl() ? true : false;
		wp_set_auth_cookie( $user_id, true, $secure_cookie );

		$data = (object) [
			"refresh" => true,
		];

		wp_send_json_success($data);
	}else{
		$data = (object) [
			"error" => $error_message,
		];

		wp_send_json_error($data);
	}
}

// AJAX call to add product function when user is logged in
add_action ('wp_ajax_iframe_new_user_form_submit', 'iframe_new_user_form_submit' );
// AJAX call to add product function when user is not logged in
add_action ('wp_ajax_nopriv_iframe_new_user_form_submit', 'iframe_new_user_form_submit' );