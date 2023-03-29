<?php
if(!defined("LEGACYLIMIT")){
    define("LEGACYLIMIT", 100);
}
class SGTQuizSettings extends GravityFormsPersonalityQuizAddon {

	protected $_version = "1.0.0";
	protected $_min_gravityforms_version = "2.0";
	protected $_slug = "gf-personality-quiz-results";
	protected $_path = "gravity-forms-personality-quiz-results/class-gravity-forms-personality-quiz-results.php";
	protected $_full_path = __FILE__;
	protected $_title = "Spiritual Gift Test Customizations";
	protected $_short_title = "SGT Customizations";
	//SERVER
	protected $form_type = array(6 =>'personality', 7 => 'sgt_a', 9 => 'sgt_y', 4 => 'register', 14 => 'addon');
	//Robert's Local
	//protected $form_type = array(2 =>'personality', 3 => 'sgt_a', 4 => 'sgt_y', 7 => 'register', 14 => 'addon');
	private static $_instance;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new SGTQuizSettings();
		}
		return self::$_instance;
	}

	public function init(){
		parent::init();

		add_action( 'user_register', array( __CLASS__, 'auto_login_new_user' ) );
		add_filter( 'gform_notification', array($this, 'after_submission'), 5, 3 );
		add_action( 'gform_after_submission_17', array($this, 'add_manual_entry'), 10, 2 );
		add_action( 'gform_user_registered', array($this, 'assign_church_code'), 1 ,4 );
		add_action( 'gform_user_updated', array($this, 'assign_church_code'), 1 ,4 );

		add_action( 'gform_post_payment_status', array($this, 'church_admin_payment_notification'), 100, 8 );
		add_action( 'woocommerce_order_status_completed', array($this, 'purchase_order_status_completed'), 10, 1);
		add_action( 'woocommerce_subscription_payment_complete', array($this, 'church_admin_subscription_payment_complete'), 100);
		add_action( 'gform_post_add_subscription_payment', array($this, 'church_admin_payment_notification'), 100, 2); // Replacing
		add_action( 'gform_user_registered', array($this, 'assign_created_code_to_user'), 10, 4);
		add_action( 'gform_post_payment_status', array($this, 'church_discount_payment_notification'), 101, 8 ); // Replacing
		add_filter( 'gform_replace_merge_tags', array($this, 'replace_church_admin_email'), 10, 7 );
		add_filter( 'gform_field_content_4_21', array($this, 'remove_coupon_disable'), 10, 5 );
		add_filter( 'gform_field_content_18_21', array($this, 'remove_coupon_disable'), 10, 5 );
		add_filter( 'gform_field_value_hidden_church_id', array($this, 'hidden_church_id') );


		add_action( 'show_user_profile', array( $this, 'church_admin_code' ), 9 );
		add_action( 'edit_user_profile', array( $this, 'church_admin_code' ), 9 );
		add_action( 'edit_user_profile_update', array( $this, 'update_church_admin_code' ) );
		add_action( 'personal_options_update',  array( $this, 'update_church_admin_code' ) );

		add_action( 'wp_ajax_sgt_generate_admin_code', array( $this, 'generate_church_code_ajax' ) );
		add_action( 'wp_ajax_sgt_generate_discount_code', array( $this, 'generate_discount_code_ajax' ) );

		add_action( 'wp_ajax_sgt_disconnect_user', array( $this, 'disconnect_user' ) );
		add_action( 'wp_ajax_sgt_delete_manual_entry', array( $this, 'delete_manual_entry' ) );
		add_action( 'wp_ajax_sgt_delete_results', array( $this, 'delete_results' ) );

		add_filter( 'gform_field_value_sgt_simple', array( $this, 'simple_sign_up') );

		add_action('rest_api_init', array(__CLASS__, 'register_rest_route'));
	}

	function activation() {
		$this->sgt_rewrite_endpoint();
		flush_rewrite_rules();
	}

	static function sgt_rewrite_endpoint() {
		add_rewrite_endpoint( 'church-id', EP_PERMALINK | EP_PAGES );
	}

	public static function add_query_vars($vars){
		$vars[] = 'church-id';
		return $vars;
	}

    public static function auto_login_new_user( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );
		//wp_redirect( home_url() );
		//exit;
	}

	function remove_coupon_disable($field_content, $field, $value, $entry_id, $form_id) {

		$field_content = str_replace('DisableApplyButton', 'EnableApplyButton', $field_content);
		$field_content = str_replace("disabled='disabled'", '', $field_content);

		return $field_content;
	}

	//Moved from gform_after_submission hook to gform_notification filter so quiz data is available for notification
	public function after_submission( $notifcation, $form, $entry) {
		//$form_settings = $this->get_form_settings($form);
		$quiz_results = array();

		if ($form['gf-personality-quiz']['quiz_type'] === "Numeric (multiple categories)") {
			foreach ($this->get_numeric_quiz_categories($form) as $category) {
				$quiz_results[$category] = gform_get_meta($entry['id'], 'personality_quiz_result['.$category.']');
			}
			$quiz_results['date_created'] = rgar( $entry, 'date_created');
			update_user_meta(get_current_user_id(),'quiz_results_'.$this->form_type[$entry['form_id']], $quiz_results);
			update_user_meta(get_current_user_id(),'quiz_time_'.$this->form_type[$entry['form_id']], strtotime($quiz_results['date_created']));
		}

		return $notifcation;
	}

	public function add_manual_entry($entry, $form) {
		if($form['id'] != 17)
			return;

		$user_id = get_current_user_id();

		if($user_id  == 0)
			return;

		$church_manual_entries = get_user_meta($user_id, 'church_manual_entries', true);

		if(!is_array($church_manual_entries))
			$church_manual_entries = array();

		$new_entry = array();

		$new_entry['first_name']    = rgar( $entry, '2.3' );
		$new_entry['last_name']     = rgar( $entry, '2.6' );
		$new_entry['email']         = rgar( $entry, '40');
		$new_entry['date_created']  = rgar($entry, '3');
		$new_entry['age_range']     = rgar( $entry, '23' );
		$new_entry['gender']        = rgar( $entry, '24' );
		$new_entry['sgt']['administration'] = rgar( $entry, '1' );
		$new_entry['sgt']['apostleship']   = rgar( $entry, '38' );
		$new_entry['sgt']['discernment']   = rgar( $entry, '37' );
		$new_entry['sgt']['evangelism']    = rgar( $entry, '36' );
		$new_entry['sgt']['exhortation']   = rgar( $entry, '35' );
		$new_entry['sgt']['faith']         = rgar( $entry, '34' );
		$new_entry['sgt']['giving']        = rgar( $entry, '39' );
		$new_entry['sgt']['knowledge']     = rgar( $entry, '32' );
		$new_entry['sgt']['leadership']    = rgar( $entry, '31' );
		$new_entry['sgt']['mercy']         = rgar( $entry, '30' );
		$new_entry['sgt']['pastoring']     = rgar( $entry, '29' );
		$new_entry['sgt']['prophecy']      = rgar( $entry, '28' );
		$new_entry['sgt']['serving']       = rgar( $entry, '27' );
		$new_entry['sgt']['teaching']      = rgar( $entry, '26' );
		$new_entry['sgt']['wisdom']        = rgar( $entry, '25' );

		$church_manual_entries[] = $new_entry;

		update_user_meta($user_id, 'church_manual_entries', $church_manual_entries);

	}

	public function assign_church_code($user_id, $feed, $entry, $pass ) {
		$this->log_debug(__METHOD__ . ' Looking for church code to assign');
		$this->log_debug(print_r($feed, 1));
		$this->log_debug(print_r($entry, 1));
		foreach($feed['meta']['userMeta'] as $meta) {
			if($meta['custom_key'] == 'church_code') {
				if($entry[$meta['value']] && !empty($entry[$meta['value']])) {
					$first_coupon = explode(",", $entry[$meta['value']])[0];
					if($admins = get_users(array('meta_key' => 'church_discount_code', 'meta_value' => $first_coupon))) {
						$this->log_debug( sprintf( __METHOD__ . ' Assigning Church Code: %s %s to user %d', $meta['custom_key'], $first_coupon, $user_id ) );
						update_user_meta( $user_id, $meta['custom_key'], get_user_meta($admins[0]->ID, 'church_admin_code', true));
					} else {
						update_user_meta( $user_id, $meta['custom_key'], $first_coupon );
					}
					//return;
				}
			} elseif ($meta['key'] == 'church_code') {
				if($entry[$meta['value']] && !empty($entry[$meta['value']])) {
					$first_coupon = explode(",", $entry[$meta['value']])[0];
					if($admins = get_users(array('meta_key' => 'church_discount_code', 'meta_value' => $first_coupon))) {
						$this->log_debug( sprintf( __METHOD__ . ' Assigning Church Code: %s %s to user %d', $meta['custom_key'], $first_coupon, $user_id ) );
						update_user_meta( $user_id, $meta['key'], get_user_meta($admins[0]->ID, 'church_admin_code', true));
					} else {
						$this->log_debug(sprintf(__METHOD__ . ' Assigning Church Code: %s %s to user %d', $meta['custom_key'], $first_coupon, $user_id));
						update_user_meta( $user_id, $meta['key'], $first_coupon );
						//return;
					}
				}
			}
		}
	}

	
	public function assign_created_code_to_user($user_id, $feed, $entry, $pass) {
		if($code = get_option('gfpqr_'.$entry['id'].'_church_code')) {
			update_user_meta($user_id, 'church_admin_code', $code);
			update_user_meta($user_id, 'church_admin_primary', 1);
			delete_option('gfpqr_'.$entry['id'].'_church_code');
			$this->add_note($entry['id'], sprintf('Church admin code %s assigned to user id: %s', $code, $user_id));

		}

		if($discount = get_option('gfpqr_'.$entry['id'].'_discount_code')) {
			update_user_meta($user_id, 'church_discount_code', $discount);
			delete_option('gfpqr_'.$entry['id'].'_discount_code');
			$this->add_note($entry['id'], sprintf('Church discount code %s assigned to user id: %s', $discount, $user_id));
		}
	}

	public function generate_church_code($usage = 0, $length = 6, $code = '') {
		$GFCoupons = GFCoupons::get_instance();

		$feed['form_id']          = 0;
		$feed['is_active']        = 1;
		$feed['meta']['gravityForm'] =  0;
		$feed['meta']['couponAmountType']   = 'flat';
		$feed['meta']['couponAmount']   = '0';
		$feed['meta']['usageLimit'] = $usage;
		$feed['meta']['isStackable'] = 0;
		$feed['meta']['usageCount'] = 0;


		$this->log_debug( __METHOD__ . ' Generating Church Code');
		$is_duplicate_coupon_code = 1;
		while($is_duplicate_coupon_code) {
			//print_r($code);
			if($code) {
				$feed['meta']['couponCode'] = $code;
				$is_duplicate_coupon_code   = $GFCoupons->is_duplicate_coupon( $feed, $GFCoupons->get_feeds() );

				if($is_duplicate_coupon_code)
					return false;
			} else {
				$feed['meta']['couponCode'] = $this->random_generator( $length );
				$is_duplicate_coupon_code   = $GFCoupons->is_duplicate_coupon( $feed, $GFCoupons->get_feeds() );
			}
		}

		$this->log_debug( __METHOD__ . ' Church code: '. $feed['meta']['couponCode']);
		$GFCoupons->insert_feed( $feed['form_id'], $feed['is_active'], $feed['meta'] );

		return $feed['meta']['couponCode'];

	}

	public function generate_church_code_ajax() {
		echo $this->generate_church_code();
		die();
	}

	public function generate_church_discount_code($usage = 0, $length = 6) {
		$GFCoupons = GFCoupons::get_instance();

		$feed['form_id']          = 0;
		$feed['is_active']        = 1;
		$feed['meta']['gravityForm'] =  0;
		$feed['meta']['couponAmountType']   = 'percentage';
		$feed['meta']['couponAmount']   = '100';
		$feed['meta']['usageLimit'] = $usage;
		$feed['meta']['isStackable'] = 0;
		$feed['meta']['usageCount'] = 0;


		$this->log_debug( __METHOD__ . ' Generating Church Code');
		$is_duplicate_coupon_code = 1;
		while($is_duplicate_coupon_code) {
			$feed['meta']['couponCode'] = $this->random_generator( $length );
			$is_duplicate_coupon_code   = $GFCoupons->is_duplicate_coupon( $feed, $GFCoupons->get_feeds() );
		}

		$this->log_debug( __METHOD__ . ' Church code: '. $feed['meta']['couponCode']);
		$GFCoupons->insert_feed( $feed['form_id'], $feed['is_active'], $feed['meta'] );

		return $feed['meta']['couponCode'];

	}

	public function generate_discount_code_ajax() {
		echo $this->generate_church_discount_code();
		die();
	}

	public function generate_church_coupon_for_user($user_id, $feed, $entry) {

		$usage = array_pop( explode( '-', $entry[23] ) );

		$this->log_debug( __METHOD__ . ' User Creation delayed.');
		update_user_meta($user_id, 'church_admin_code', $this->generate_church_code($usage));
	}

	public function add_coupon_usage($coupon, $increase) {
		$GFCoupons = GFCoupons::get_instance();
		$feed = $GFCoupons->get_config(0, $coupon);

		$meta               = $feed['meta'];
		$starting_count     = empty( $meta['usageLimit'] ) ? 0 : $meta['usageLimit'];
		$meta['usageLimit'] = $starting_count + $increase;

		$GFCoupons->update_feed_meta( $feed['id'], $meta );
		$this->log_debug( __METHOD__ . "(): Updating amount from {$starting_count} to {$meta['usageLimit']}." );

	}

	static function random_generator($length = 6){
		return strtoupper(substr(md5(mt_rand()), -$length));
	}

	function assign_user_codes($user_id, $church_code, $discount_code) {

		if ($church_code != "" && get_user_meta($user_id, 'church_admin_code', true) != "") {
			update_user_meta($user_id, 'church_admin_code', $church_code);
			update_user_meta($user_id, 'church_admin_primary', 1);
		}

		if ($church_code != "" && get_user_meta($user_id, 'church_discount_code', true) != "") {
			update_user_meta($user_id, 'church_discount_code', $discount_code);
		}
	}

	function purchase_order_status_completed($order_id) {

		$order = wc_get_order($order_id);
		
		$personality_test_product_id      = 15044;
		$spiritual_gifts_test_product_id = 15006;
		$spiritual_gifts_test_upgrade_product_id = 15077;

		if( $user_id = $order->get_user_id() ) {
			// Get the WP_User Object
			$wp_user = new WP_User( $user_id );

			foreach ( $order->get_items() as $item ) {

				if ( $personality_test_product_id == $item->get_product_id() && $order->get_user_id() > 0 ) {
					$wp_user->set_role( 'personalitytest' );

					$error_message .= PHP_EOL . "personalitytest truef: ";

					if(count($order->get_coupon_codes()) >= 1 && $admins = get_users(array('meta_key' => 'church_discount_code', 'meta_value' => $order->get_coupon_codes()[0]))) {
						update_user_meta( $user_id, 'church_code', get_user_meta($admins[0]->ID, 'church_admin_code', true));
						get_user_by( 'ID', $user_id)->set_role('personalitytest');
					}

				}else if ( $spiritual_gifts_test_product_id == $item->get_product_id() && $order->get_user_id() > 0 ) {
					$wp_user->set_role( 'spiritualgiftstest' );
				}else if ($spiritual_gifts_test_upgrade_product_id == $item->get_product_id() && $order->get_user_id() > 0 ) {
					$wp_user->set_role( 'personalitytest' );
				}
			}
		}
	}

	function church_admin_subscription_payment_complete($subscription) {
		// Error message Logger

		// error message to be logged 
		$error_message = PHP_EOL . " NEW SUB 3:   " . json_encode($subscription->get_items()); 
		
		$items = $subscription->get_items();
		$error_message .= PHP_EOL . "USER STUFFS " . json_encode(get_user_by( 'ID', $subscription->get_user_id()));
		foreach ( $items as $item ) {
			$error_message .= PHP_EOL . "AWW YEAH! " . $item->get_product_id() . " | " . $item->get_variation_id() . " | " . $item->get_name() . " | " . $item->get_product();
			$error_message .= PHP_EOL . "Cool: " . $item->get_product()->attributes . " | " . $item->get_product()->attributes["pa_credits"];
		}

		// path of the log file where errors need to be logged 
		$log_file = "./my-errors.log"; 


		$user = get_user_by( 'ID', $subscription->get_user_id());

		$amount;

		$items = $subscription->get_items();

		foreach ( $items as $item ) {
			$product = $item->get_product();
			$product_split_list = explode("-", $product->slug);
			array_pop($product_split_list);
			$product_name = implode("-", $product_split_list);

			if ($product_name == "church-admin-registration") {
				$amount = intval(explode("x", $item->get_product()->attributes["pa_credits"])[0]);
				$error_message .= PHP_EOL . "retrieved amount: " . $amount;
				break;
			}
		}

		$error_message .= PHP_EOL . "AMMOUNTS:  " . $amount;

		

		if($coupon = get_user_meta($user->ID, 'church_discount_code', true)) {
			$this->log_debug( __METHOD__ . ' Starting to add to usage limit.');
			$this->add_coupon_usage($coupon, $amount);
			$this->add_note($subscription->get_id(), sprintf('Adding %d uses to the %s church discount code.', 10, $coupon));
			$error_message .= PHP_EOL . " COUPN:  " . $coupon;
			get_user_by( 'ID', $user->ID)->set_role('churchadmin');

			$coupon_post = $posts = $wpdb->get_results('SELECT * FROM msgtp1_posts WHERE post_title LIKE "' . $coupon .'"')[0];
			update_post_meta( $coupon_post->ID, 'usage_limit', $amount );
		} else {
			$code = $this->generate_church_code();
			$discount = $this->generate_church_discount_code($amount);
			update_user_meta($user->ID, 'church_admin_code', $code);
			update_user_meta($user->ID, 'church_discount_code', $discount);
			update_user_meta($user->ID, 'church_admin_primary', 1);
			$this->add_note($subscription->get_id(), sprintf('Generated church code %s and church discount code %s with %d for user: %s', $code, $discount, $amount, $user->user_login));
			$error_message .= PHP_EOL . " ADMIN STUFFS:  " . $code . " | " . $discount;
			get_user_by( 'ID', $user->ID)->set_role('churchadmin');

			/**
			* Create a coupon programatically
			*/
			$coupon_code = $discount; // Code
			$percent = '100'; // Amount
			$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product

			$coupon = array(
			'post_title' => $coupon_code,
			'post_content' => 'Church Admin coupon code for church ' . $code,
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type' => 'shop_coupon');

			$new_coupon_id = wp_insert_post( $coupon );

			// Add meta
			update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
			update_post_meta( $new_coupon_id, 'coupon_amount', $percent );
			update_post_meta( $new_coupon_id, 'individual_use', 'no' );
			update_post_meta( $new_coupon_id, 'product_ids', '15044,15077' );
			update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
			update_post_meta( $new_coupon_id, 'usage_limit', $amount );
			update_post_meta( $new_coupon_id, 'expiry_date', '' );
			update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
			update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

		}

		// logging error message to given log file 
		error_log($error_message, 3, $log_file);
	}

	//function church_admin_payment_notification( $feed, $entry, $status,  $transaction_id, $subscriber_id, $amount, $pending_reason, $reason ) {
	function church_admin_payment_notification($entry, $action) {

		global $wpdb;

		if($entry['form_id'] != (4 || 16))
			return;

		if($entry['form_id'] == 4 && $entry[22] != 'Church Admin')
			return;


		if($entry['form_id'] == 4) {
			$user = get_user_by( 'email', $entry[4] );
		} elseif ($entry['form_id'] == 16) {
			$user = get_user_by( 'ID', $entry['created_by'] );
		} else {
			return;
		}

		$this->log_debug( __METHOD__ . ' Entry Details:'.print_r( $entry, 1 ));

		$this->log_debug( __METHOD__ . ' User Details:'.print_r( $user, 1 ));


		if(!$user) {
			$code = $this->generate_church_code();
			add_option('gfpqr_'.$entry['id'].'_church_code', $code);
			$discount = $this->generate_church_discount_code(10);
			add_option('gfpqr_'.$entry['id'].'_discount_code', $discount);
			$this->add_note($entry['id'], sprintf('User not found for email %s. Church code "%s" and discount code "%s" created.', $entry['4'], $code, $discount));
			return;
		}

		if($coupon = get_user_meta($user->ID, 'church_discount_code', true)) {
			$this->log_debug( __METHOD__ . ' Starting to add to usage limit.');
			$this->add_coupon_usage($coupon, 10);
			$this->add_note($entry['id'], sprintf('Adding %d uses to the %s church discount code.', 10, $coupon));

			$coupon_post = $wpdb->get_results('SELECT * FROM msgtp1_posts WHERE post_title LIKE "' . $coupon .'"')[0];
			update_post_meta( $coupon_post->ID, 'usage_limit', $amount );
		} else {
			$code = $this->generate_church_code();
			$discount = $this->generate_church_discount_code(10);
			update_user_meta($user->ID, 'church_admin_code', $code);
			update_user_meta($user->ID, 'church_discount_code', $discount);
			update_user_meta($user->ID, 'church_admin_primary', 1);
			$this->add_note($entry['id'], sprintf('Generated church code %s and church discount code %s with %d for user: %s', $code, $discount, 10, $user->user_login));
		
			/**
			* Create a coupon programatically
			*/
			$coupon_code = $discount; // Code
			$percent = '100'; // Amount
			$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product

			$coupon = array(
			'post_title' => $coupon_code,
			'post_content' => 'Church Admin coupon code for church ' . $code,
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type' => 'shop_coupon');

			$new_coupon_id = wp_insert_post( $coupon );

			// Add meta
			update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
			update_post_meta( $new_coupon_id, 'coupon_amount', $percent );
			update_post_meta( $new_coupon_id, 'individual_use', 'no' );
			update_post_meta( $new_coupon_id, 'product_ids', '15044,15077' );
			update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
			update_post_meta( $new_coupon_id, 'usage_limit', 10 );
			update_post_meta( $new_coupon_id, 'expiry_date', '' );
			update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
			update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
		}
	}

	function church_discount_payment_notification( $feed, $entry, $status,  $transaction_id, $subscriber_id, $amount, $pending_reason, $reason ) {

		if($entry['form_id'] != (14))
			return;

		if(empty($status))
			return;

		$user = get_user_by( 'ID', $entry['created_by'] );


		$this->log_debug( __METHOD__ . ' Entry Details:'.print_r( $entry, 1 ));

		$this->log_debug( __METHOD__ . ' User Details:'.print_r( $user, 1 ));

		$this->log_debug( __METHOD__ . ' Status:'. $status);

		if ( $status == 'Completed' ) {
			$usage = array_pop( explode( '-', $entry[23] ) );

			$this->log_debug( __METHOD__ . ' Usage Limit Increase:' . $usage );

			if ( ! is_numeric( $usage ) ) {
				return;
			}

			if ( $coupon = get_user_meta( $user->ID, 'church_discount_code', true ) ) {
				$this->log_debug( __METHOD__ . ' Starting to add to usage limit.' );
				$this->add_coupon_usage( $coupon, $usage );
				$this->add_note($entry['id'], sprintf('Adding %d uses to the %s church discount code.', $usage, $coupon));
			} else {
				$code = $this->generate_church_discount_code( $usage );
				update_user_meta( $user->ID, 'church_discount_code', $code );
				$this->add_note($entry['id'], sprintf('User did not have church discount code assigned. Generated code %s with %d uses for user', $code, $usage));
			}
		}

	}

	function replace_church_admin_email($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
		$custom_merge_tag = '{church_admin_email}';
		if (strpos($text, $custom_merge_tag) === false) {
			return $text;
		}

		if(!isset($entry[21]) || empty($entry[21]))
			return $text;

		$admins = get_users(array('meta_key' => 'church_admin_code', 'meta_value' => $entry[21]));

		if(empty($admins))
			return $text;

		$emails = '';
		foreach($admins as $admin) {
			$emails .= $admin->data->user_email.', ';
		}

		$text = str_replace($custom_merge_tag, $emails, $text);
		return $text;
	}

	static function verify_subscription($church_code) {

		$users = get_users(array(
			'meta_query' => array(
				array(
					'key' => 'church_admin_code',
					'value' => strtoupper($church_code),
					'compare' => '='
				),
				array(
					'key' => 'church_admin_primary',
					'value' => '1',
					'compare' => '='
				)
			)
		));
//print_r($users);
		if(!$users)
			return apply_filters('sgt_verify_subscription_not_found', false, $church_code);

		$verified = false;
		foreach($users as $user) {
			if ( user_can( $user, 'churchadmin' ) || user_can( $user, 'administrator') ) {
				$verified = true;
			}
		}
		return $verified;
		return apply_filters( 'sgt_verify_subscription', $verified, $church_code, $users );

	}

	function get_csv() {
		$fileName = 'results-csv.csv';

		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename={$fileName}");
		header("Expires: 0");
		header("Pragma: public");

		$fh = @fopen( 'php://output', 'w' );

		$data = $this->get_csv_data();
		$headerDisplayed = false;

		foreach ( $data as $d ) {
			// Add a header row if it hasn't been added yet
			if ( !$headerDisplayed ) {
				// Use the keys from $data as the titles
				fputcsv($fh, self::get_csv_keys());
				$headerDisplayed = true;
			}

			// Put the data into the stream
			fputcsv($fh, $d);
		}
		// Close the file
		fclose($fh);
		// Make sure nothing else is sent, our file is done
		exit;
	}

	function get_csv_data() {
		global $wp_query;
		$admin_id = get_current_user_id();
		if($admin_id == 0 || !$church_code = get_user_meta($admin_id, 'church_admin_code', true) ) {
			$wp_query->set_404();
			status_header( 404 );
			die();
		}
		$data = array();
		$church_members = get_users(array('meta_query' => array('relation' => 'OR',
																array('key' => 'church_code', 'value' => $church_code),
																array('key' => 'church_admin_code', 'value' => $church_code))));

		foreach($church_members as $member) {
			$data[] = $this->format_member_data($member);
		}

		//Manual entries
		$manual = get_user_meta($admin_id, 'church_manual_entries', true);

		if(is_array($manual)) {
			foreach($manual as $entry) {
				$data[] = $this->format_manual_data($entry);
			}
		}

		//Legacy Entries
		if(class_exists('SGTLegacy')) {
			$data = SGTLegacy::maybe_add_csv_data($data);
		}

		return $this->filter_csv_data($data);

	}

	function format_member_data($member) {


		$gift_averages = $this->get_gift_averages();
		$personality_sets = $this->get_personality_sets();

		$quiz_data = get_user_meta($member->ID, 'quiz_results_sgt_a', true);
		if(empty($quiz_data))
			$quiz_data = get_user_meta($member->ID, 'quiz_results_sgt_y', true);
		if($quiz_data) {
			$quiz_data = array_change_key_case( $quiz_data );
			$gift_ranking = array();
			foreach ($quiz_data as $gift => $score) {

				$gift_ranking[$gift] = $score/$gift_averages[$gift];
			}

			arsort($gift_ranking);

			$gift_keys = array_keys($gift_ranking);
		}

		$personality_score = get_user_meta($member->ID, 'quiz_results_personality', true);
		if($personality_score) {
			array_change_key_case( $personality_score );

			foreach ( $personality_sets as $dkey => $domain ) {
				foreach ( $domain['facets'] as $key => $facets_info ) {
					$personality_sets[ $dkey ]['score'] += $personality_sets[ $dkey ]['facets'][ $key ]['score'] = $personality_score[ $key ];
				}
				//uasort($personality_sets[$dkey]['facets'], array('GravityFormsPersonalityQuizResults', 'personality_sorter'));
			}
			//uasort($personality_sets, array('GravityFormsPersonalityQuizResults', 'personality_sorter'));
		}

		$member_data = array();
		foreach(self::get_csv_keys() as $key => $title) {
			switch($key) {
				case 'first_name':
					$member_data[$key] = $member->user_firstname;
					break;
				case 'last_name':
					$member_data[$key] = $member->user_lastname;
					break;
				case 'email':
					$member_data[$key] = $member->user_email;
					break;
				case 'date_crated':
					$member_data[$key] = date( 'Y-m-d',strtotime($quiz_data['date_created']));
					break;

				//Spiritual Gifts Scores
				case substr($key,0,2) == 's_':
					if(isset($quiz_data[substr($key,2)]))
						$member_data[substr($key,2)] = $quiz_data[substr($key,2)];
					else
						$member_data[$key] = '';
					break;

				//Personality Scores
				case substr($key,0,2) == 'p_':
					if(is_numeric($personality_sets[substr($key,2)]['score']))
						$member_data[$key] = sprintf('%s%%', round($personality_sets[substr($key,2)]['score'] / 120 * 100));
					else
						$member_data[$key] = '';
					break;

				default:
					$member_data[$key] = get_user_meta($member->ID, $key, true);

			}
		}

		return $member_data;
	}

	function format_manual_data($data) {

		$quiz_data = $data['sgt'];

		$gift_averages = $this->get_gift_averages();

		$gift_ranking = array();
		foreach ($quiz_data as $gift => $score) {

			$gift_ranking[$gift] = $score/$gift_averages[$gift];
		}
		arsort($gift_ranking);
		$gift_keys = array_keys($gift_ranking);

		$member_data = array();
		foreach(self::get_csv_keys() as $key => $title) {
			switch($key) {
				case 'first_name':
				case 'last_name':
				case 'gender':
				case 'age_range':
				case 'email':
					$member_data[$key] = $data[$key];
					break;

				case 'date_crated':
					date( 'Y-m-d',strtotime($data['date_created']));
					break;

				//Spiritual Gifts Scores
				case substr($key,0,2) == 's_':
					$member_data[substr($key,2)] = $quiz_data[substr($key,2)];
					break;
				//Personality Scores
				case substr($key,0,2) == 'p_':
					$member_data[substr($key,2)] = '';
					break;

				default:
					$member_data[$key] = '';
			}
		}

		return $member_data;
	}

	function filter_csv_data($data) {
		$filter = $_GET['filter'];

		if(!$filter)
			return $data;

		foreach ($data as $i => $member) {
			if('name' == $filter) {
				if(strpos(strtolower($member['First Name'].' '.$member['Last Name']), strtolower($_GET['mname'])) === false)
					unset($data[$i]);
			}

			if('date' == $filter){
				$time = strtotime($member['Entry Date']);
				if($time < $_GET['from'] || $time > $_GET['to'])
					unset($data[$i]);
			}

			if('age-range' == $filter) {
				if($member['Age Range'] != urldecode($_GET['age-range']))
					unset($data[$i]);
			}

			if('gender' == $filter) {
				if($member['Gender'] != $_GET['gender'])
					unset($data[$i]);
			}

			if('top-gift' == $filter) {
				if(strtolower($member['Gift 1']) != strtolower($_GET['top-gift']))
					unset($data[$i]);
			}

			if('top-three' == $filter) {
				if(!in_array($_GET['top-three'], array_map('strtolower', $member)))
					unset($data[$i]);
			}

			if('top-personality' == $filter) {
				$values['extraversion'] = str_replace('%', '', $member['Extraversion']);
				$values['agreeableness'] = str_replace('%', '', $member['Agreeableness']);
				$values['conscientiousness'] = str_replace('%', '', $member['Conscientiousness']);
				$values['neuroticism'] = str_replace('%', '', $member['Neuroticism']);
				$values['opennesstoexperience'] = str_replace('%', '', $member['Openness To Experience']);
				arsort($values);

				if(key($values) != $_GET[$filter] || $values[key($values)] == 0)
					unset($data[$i]);

			}

		}

		return $data;
	}

	function hidden_church_id($value) {
		if(get_query_var('church-id'))
			return get_query_var('church-id');
		if($_GET['uid'])
			return $_GET['uid'];
	}

	function church_admin_code( $user ) {
		$admin_code = get_user_meta($user->ID, 'church_admin_code', true);
		$primary = get_user_meta($user->ID, 'church_admin_primary', true);
		$discount = get_user_meta($user->ID, 'church_discount_code', true);
		?>

		<div class="visible-only-for-admin">
			<h3>Church Admin Link Info</h3>
			<table class="form-table" >
				<tr>
					<th><label for="church_admin_code">Admin Code</label></th>
					<td>
						<?php if ( current_user_can( 'administrator' ) ) : ?>

							<input type="text" name="church_admin_code" value="<?php echo $admin_code ?>" class="regular-text" />
							<?php if (!$admin_code) {
								echo '<button class="generate-church-admin-code">Generate Code</button>';
							}
							?>
						<?php else : ?>

							<?php echo $admin_code ?>

						<?php endif ?>
					</td>
				</tr>
				<tr>
					<th><label for="church_admin_primary">Primary Admin</label></th>
					<td>
						<?php if ( current_user_can( 'administrator') ): ?>
							<input type="checkbox" name="church_admin_primary" value="1" <?php checked('1', $primary); ?> />
						<?php else : ?>
							<?php if($primary)
								echo "YES";
						endif
						?>
					</td>
				</tr><tr>
					<th><label for="church_discount_code">Discount Code</label></th>
					<td>
						<?php if ( current_user_can( 'administrator' ) ) : ?>

							<input type="text" name="church_discount_code" value="<?php echo $discount ?>" class="regular-text" />
							<?php if (!$discount) {
								echo '<button class="generate-discount-code">Generate Code</button>';
							} ?>
						<?php else : ?>

							<?php echo $discount ?>

						<?php endif ?>
					</td>
				</tr>
			</table>
		</div>
		<script type="text/javascript" >
			jQuery('.generate-church-admin-code').click(function($) {
				var data = {
					'action': 'sgt_generate_admin_code'
				};

				jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
					jQuery('input[name=church_admin_code]').val(response);
				});
				return false;
			});
			jQuery('.generate-discount-code').click(function($) {
				var data = {
					'action': 'sgt_generate_discount_code'
				};

				jQuery.post('<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function(response) {
					jQuery('input[name=church_discount_code]').val(response);
				});
				return false;
			});
		</script>
		<?php
	}

	function update_church_admin_code( $user_id ) {
		if ( isset( $_POST['church_admin_code'] ) && current_user_can( 'administrator' ) )
			update_user_meta($user_id, 'church_admin_code', sanitize_text_field( wp_unslash( $_POST['church_admin_code'] ) ) );

		if ( isset( $_POST['church_admin_primary'] ) && current_user_can( 'administrator' ) )
			update_user_meta($user_id, 'church_admin_primary', sanitize_text_field( wp_unslash( $_POST['church_admin_primary'] ) ) );
		else
			delete_user_meta($user_id, 'church_admin_primary', '1');

		if ( isset( $_POST['church_discount_code'] ) && current_user_can( 'administrator' ) )
			update_user_meta($user_id, 'church_discount_code', sanitize_text_field( wp_unslash( $_POST['church_discount_code'] ) ) );
	}

	static function register_rest_route() {
		register_rest_route( 'sgt/v2', '/church/(?P<id>\w+)', array(
			'methods' => 'GET',
			'callback' => array(__CLASS__, 'api_response'),
		) );
	}

	function api_response(WP_REST_Request $request) {
		global $wpdb;
		$church = SGTChurch::get_church($request->get_param('id'));

		if($church->error()) {
			return array('error'=>'Church Not Found');
		}

		if($church->get_secret() != $request->get_param('secret')) {
			return array('error'=>'An error occurred. Please check your settings.');
		}

		$results = SGTLegacy::get_api_results($request);
		$count = count($results['test_form_data']);

		//Page-Limit

		$subquery = $wpdb->prepare(preg_replace( "/\s+/", " ", 'SELECT um.user_id AS ID,
	(select user_login from msgtp1_users where ID = um.user_id) AS user_login,
	(select user_email from msgtp1_users where ID = um.user_id) AS user_email,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "first_name" limit 1) as first_name,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "last_name" limit 1) as last_name,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "quiz_time_sgt_a" limit 1) as quiz_time_sgt_a,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "quiz_results_sgt_a" limit 1) as quiz_results_sgt_a,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "quiz_time_sgt_y" limit 1) as quiz_time_sgt_y,
    (select meta_value from msgtp1_usermeta where user_id = um.user_id and meta_key = "quiz_results_sgt_y" limit 1) as quiz_results_sgt_y
FROM msgtp1_usermeta um WHERE ((um.meta_key = "church_code" OR um.meta_key = "church_admin_code") AND um.meta_value = %s)'), $request->get_param('id'));

		$query_string = 'SELECT * FROM ('.$subquery.') AS s WHERE 1=1 AND (s.quiz_results_sgt_a != "" OR s.quiz_results_sgt_y != "") ';

		if (isset($_GET['user_id']) && $_GET['user_id'] !== "") {
			$query_string .= $wpdb->prepare('AND s.ID = %s ', $_GET['user_id']);
		}
		if (isset($_GET['name']) && $_GET['name'] !== "") {
			$query_string .= $wpdb->prepare('AND (s.first_name LIKE "%%%1$s%%" OR s.last_name LIKE "%%%1$s%%" OR concat_ws(" ", s.first_name, s.last_name) LIKE "%%%1$s%%" )', $_GET['name']);
		}
		if (isset($_GET['email']) && $_GET['email'] !== "") {
			$query_string .= $wpdb->prepare('AND s.user_email = %s ', $_GET['email']);
		}
		if (isset($_GET['fromDate']) && $_GET['fromDate'] !== "" && isset($_GET['toDate']) && $_GET['toDate'] !== "") {
			$query_string .= $wpdb->prepare('AND s.quiz_time_sgt_a >= %d AND s.quiz_time_sgt_a <= %d ', strtotime($_GET['fromDate']), strtotime($_GET['toDate']));
		}

		$sql_results = $wpdb->get_results($query_string);

		foreach ($sql_results as $result) {
			$user_results = array();
			$result->quiz_results_sgt_a = unserialize($result->quiz_results_sgt_a);
			$result->quiz_results_sgt_y = unserialize($result->quiz_results_sgt_y);
			$user_results['id'] = $result->ID;
			$user_results['date_taken'] = $result->quiz_results_sgt_a['date_created'];
			$user_results['customer_id'] = $request->get_param('id');
			$user_results['first_name'] = $result->first_name;
			$user_results['last_name'] = $result->last_name;
			$user_results['email'] = $result->user_email;
			$user_results['phone'] = '';
			$user_results['state'] = '';
			$user_results['city'] = '';
			$user_results['position_title'] = '';
			$user_results['test_version'] = $result->quiz_results_sgt_a ? 'Adult' : 'Youth';

			$quiz_results = $result->quiz_results_sgt_a ? $result->quiz_results_sgt_a : $result->quiz_results_sgt_y;

			unset($quiz_results['date_created']);
			arsort($quiz_results);
			$i = 0;
			foreach($quiz_results as $cat => $score) {
				if($i <3 )
					$user_results['top_three_categories'][] = array( 'category_name' => ucwords($cat), 'score' => $score);

				$user_results['all_categories'][] = array( 'category_name' => ucwords($cat), 'score' => $score);
				$i++;
			}
			$results['test_form_data'][] = $user_results;
		}

		return $results;

	}

	static function get_gift_averages() {

		return array('administration'   =>  3.275,
		             'apostleship'      =>  2.271,
		             'discernment'      =>  3.228,
		             'evangelism'       =>  2.797,
		             'exhortation'      =>  3.161,
		             'faith'            =>  3.819,
		             'giving'           =>  2.716,
		             'knowledge'        =>  2.858,
		             'leadership'       =>  2.804,
		             'mercy'            =>  3.351,
		             'pastoring'        =>  3.302,
		             'prophecy'         =>  1.943,
		             'serving'          =>  2.933,
		             'teaching'         =>  3.037,
		             'wisdom'           =>  3.206);
	}

	static function get_gift_parameters() {

		return array('administration'   =>  array('high' => 3.774, 'low' => 2.776),
		             'apostleship'      =>  array('high' => 2.82, 'low' => 1.722),
		             'discernment'      =>  array('high' => 3.694, 'low' => 2.763),
		             'evangelism'       =>  array('high' => 3.327, 'low' => 2.268),
		             'exhortation'      =>  array('high' => 3.702, 'low' => 2.621),
		             'faith'            =>  array('high' => 4.314, 'low' => 3.325),
		             'giving'           =>  array('high' => 3.278, 'low' => 2.153),
		             'knowledge'        =>  array('high' => 3.409, 'low' => 2.308),
		             'leadership'       =>  array('high' => 3.339, 'low' => 2.269),
		             'mercy'            =>  array('high' => 3.882, 'low' => 2.82),
		             'pastoring'        =>  array('high' => 3.828, 'low' => 2.777),
		             'prophecy'         =>  array('high' => 2.553, 'low' => 1.334),
		             'serving'          =>  array('high' => 3.452, 'low' => 2.415),
		             'teaching'         =>  array('high' => 3.576, 'low' => 2.5),
		             'wisdom'           =>  array('high' => 3.71, 'low' => 2.702));
	}

	static function get_personality_sets() {
		return array(  'extraversion'=>  array('title' => 'Extraversion',
		                                       'slug' => 'extraversion',
		                                       'description' => '<p>Extraversion is marked by pronounced engagement with the outside world. Extraverts are sociable and enjoy being with people in both small and large groups. It is easy for them to make friends. They are optimistic, full of energy, and often feel good about life and themselves. They tend to be enthusiastic, action-oriented individuals who are very open to opportunities for excitement. In groups they are talkative, assertive, and often draw attention to themselves.</p>

																					          <p>Introverts lack the exuberance, energy, and activity levels of extraverts. They tend to be quiet, low-key, deliberate, and disengaged from the social world. Their lack of social involvement should not be interpreted as shyness or depression; the introvert simply needs less stimulation than an extravert and prefers to be alone. The independence and reserve of the introvert is sometimes mistaken as unfriendliness or arrogance. In reality, an introvert who scores high on the agreeableness dimension will not seek others out but will be quite pleasant when approached.</p>',
		                                       'score' => '',
		                                       'range' => array('high' => 3.7, 'low' => 2.799),
		                                       'high_statement' => 'Your score on Extraversion is high, indicating you are sociable, outgoing, energetic, and lively. You prefer to be around people much of the time.',
		                                       'average_statement' => 'Your score on Extraversion is average, indicating you are neither a subdued loner nor a jovial chatterbox. You enjoy time with others but also time alone.',
		                                       'low_statement' => 'Your score on Extraversion is low, indicating you are introverted, reserved, and quiet. You enjoy solitude and solitary activities. Your socializing tends to be restricted to a few close friends.',
		                                       'facets' => array('friendliness' => array('title' => 'Friendliness', 'score' => '', 'description' => 'Friendly people genuinely like other people and openly demonstrate positive feelings toward others. They make friends quickly and it is easy for them to form close, intimate relationships. Low scorers on Friendliness are not necessarily cold and hostile, but they do not reach out to others and are perceived as distant and reserved.'),
		                                                         'gregariousness' => array('title' => 'Gregariousness', 'score' => '', 'description' => 'Gregarious people find the company of others pleasantly stimulating and rewarding. They enjoy the excitement of crowds. Low scorers tend to feel overwhelmed by, and therefore actively avoid, large crowds. They do not necessarily dislike being with people sometimes, but their need for privacy and time to themselves is much greater than for individuals who score high on this scale.'),
		                                                         'assertiveness' => array('title' => 'Assertiveness', 'score' => '', 'description' => 'High scorers in Assertiveness like to speak out, take charge, and direct the activities of others. They tend to be leaders in groups. Low scorers tend not to talk much and let others control the activities of groups.'),
		                                                         'activity_level' => array('title' => 'Activity Level', 'score' => '', 'description' => 'Active individuals lead fast-paced, busy lives. They move about quickly, energetically, and vigorously, and they are involved in many activities. People who score low on this scale follow a slower and more leisurely, relaxed pace.'),
		                                                         'excitement_seeking' => array('title' => 'Excitement Seeking', 'score' => '', 'description' => 'High scorers on this scale are easily bored without high levels of stimulation. They love bright lights and hustle and bustle. They are likely to take risks and seek thrills. Low scorers are overwhelmed by noise and commotion and are averse to thrill-seeking.'),
		                                                         'cheerfulness' => array('title' => 'Cheerfulness', 'score' => '', 'description' => 'This scale measures positive mood and feelings, not negative emotions (which are a part of the Neuroticism domain). Persons who score high on this scale typically experience a range of positive feelings, including happiness, enthusiasm, optimism, and joy. Low scorers are not as prone to such energetic, high spirits.'))
		),
		               'agreeableness'     =>  array('title' => 'Agreeableness',
		                                             'slug' => 'agreeableness',
		                                             'description' => '<p>Agreeableness reflects individual differences in concern with cooperation and social harmony. Agreeable individuals value getting along with others. They are therefore considerate, friendly, generous, helpful, and willing to compromise their interests with others\'. Agreeable people also have an optimistic view of human nature. They believe people are generally honest, decent, and trustworthy.</p>

																	          <p>Disagreeable individuals place self-interest above getting along with others. They are generally unconcerned with the well-being of others, and therefore are unlikely to extend themselves for other people. Sometimes their skepticism about the motives of others causes them to be suspicious, unfriendly, and uncooperative.</p>

																	          <p>Agreeableness is obviously advantageous for attaining and maintaining popularity. Agreeable people are better liked than disagreeable people. On the other hand, agreeableness is not useful in situations that require tough or absolute objective decisions. Disagreeable people can make excellent scientists, critics, or soldiers.</p>',
		                                             'score' => '',
		                                             'range' => array('high' => 4.16, 'low' => 3.484),
		                                             'high_statement' => 'Your high level of Agreeableness indicates a strong interest in others\' needs and well-being. You are pleasant, sympathetic, and cooperative.',
		                                             'average_statement' => 'Your level of Agreeableness is average, indicating some concern with others\' Needs, but, generally, unwillingness to sacrifice yourself for others.',
		                                             'low_statement' => 'Your score on Agreeableness is low, indicating less concern with others\' needs Than with your own. People see you as tough, critical, and uncompromising.',
		                                             'facets' => array('trust' => array('title' => 'Trust', 'score' => '', 'description' => 'A person with high trust assumes that most people are fair, honest, and have good intentions. Persons low in trust see others as selfish, devious, and potentially dangerous.'),
		                                                               'morality' => array('title' => 'Moraltiy', 'score' => '', 'description' => 'High scorers on this scale see no need for pretense or manipulation when dealing with others and are therefore candid, frank, and sincere. Low scorers believe that a certain amount of deception in social relationships is necessary. People find it relatively easy to relate to the straightforward high-scorers on this scale. They generally find it more difficult to relate to the unstraightforward low-scorers on this scale. It should be made clear that low scorers are not necessarily unprincipled or immoral; they are simply more guarded and less willing to openly reveal the whole truth.'),
		                                                               'altruism' => array('title' => 'Altruism', 'score' => '', 'description' => 'Altruistic people find helping other people genuinely rewarding. Consequently, they are generally willing to assist those who are in need. Altruistic people find that doing things for others is a form of self-fulfillment rather than self-sacrifice. Low scorers on this scale do not particularly like helping those in need. Requests for help feel like an imposition rather than an opportunity for self-fulfillment.'),
		                                                               'cooperation' => array('title' => 'Cooperation', 'score' => '', 'description' => 'Individuals who score high on this scale dislike confrontation. They are perfectly willing to compromise or to deny their own needs in order to get along with others. Those who score low on this scale are more likely to intimidate others to get their way.'),
		                                                               'modesty' => array('title' => 'Modesty', 'score' => '', 'description' => 'High scorers on this scale do not like to claim that they are better than other people. In some cases, this attitude may derive from low self-confidence or self-esteem. Nonetheless, some people with high self-esteem find immodesty unseemly. Those who are willing to describe themselves as superior tend to be seen as disagreeably arrogant by other people.'),
		                                                               'sympathy' => array('title' => 'Sympathy', 'score' => '', 'description' => 'People who score high on this scale are tenderhearted and compassionate. They feel the pain of others vicariously and are easily moved to pity. Low scorers are not affected strongly by human suffering. They pride themselves on making objective judgments based on reason. They are more concerned with truth and impartial justice than with mercy.'))
		               ),
		               'conscientiousness' =>  array('title' => 'Conscientiousness',
		                                             'slug' => 'conscientiousness',
		                                             'description' => '<p>Conscientiousness concerns the way in which we control, regulate, and direct our impulses. Impulses are not inherently bad; occasionally time constraints require a snap decision, and acting on our first impulse can be an effective response. Also, in times of play rather than work, acting spontaneously and impulsively can be fun. Impulsive individuals can be seen by others as colorful, fun-to-be-with, and exciting.</p>

																	          <p>Nonetheless, acting on impulse can lead to trouble in a number of ways. Some impulses are antisocial. Uncontrolled antisocial acts not only harm other members of society, but also can result in retribution toward the perpetrator of such impulsive acts. Another problem with impulsive acts is that they often produce immediate rewards but undesirable, long-term consequences. Examples include excessive socializing that leads to being fired from one\'s job, hurling an insult that causes the breakup of an important relationship, or using pleasure-inducing drugs that eventually destroy one\'s health.</p>

																	          <p>Impulsive behavior, even when not seriously destructive, diminishes a person\'s effectiveness in significant ways. Acting impulsively disallows contemplating alternative courses of action, some of which would have been wiser than the impulsive choice. Impulsivity also sidetracks people during projects that require organized sequences of steps or stages. Accomplishments of an impulsive person are therefore typically small, scattered, and inconsistent.</p>

																	          <p>A hallmark of intelligence is thinking about future consequences before acting on an impulse. Intelligent activity involves contemplation of long-range goals, organizing and planning routes to these goals, and persisting toward one\'s goals in the face of short-lived impulses to the contrary. The idea that intelligence involves impulse control is nicely captured by the term prudence, an alternative label for the Conscientiousness domain. Prudent means both wise and cautious. Persons who score high on the Conscientiousness scale are, in fact, perceived by others as intelligent.</p>

																	          <p>The benefits of high conscientiousness are obvious. Conscientious individuals avoid trouble and achieve high levels of success through purposeful planning and persistence. They are also positively regarded by others as intelligent and reliable. On the negative side, they can be compulsive perfectionists and workaholics. Furthermore, extremely conscientious individuals might be regarded as stuffy and boring. Unconscientious people may be criticized for their unreliability, lack of ambition, and failure to stay within the lines, but they will experience many short-lived pleasures and they will never be called stuffy.</p>',
		                                             'score' => '',
		                                             'range' => array('high' => 4.083, 'low' => 3.371),
		                                             'high_statement' => 'Your score on Conscientiousness is high. This means you set clear goals and pursue them with determination. People regard you as reliable and hard-working.',
		                                             'average_statement' => 'Your score on Conscientiousness is average. This means you are reasonably reliable, organized, and self-controlled.',
		                                             'low_statement' => 'Your score on Conscientiousness is low, indicating you like to live for the moment and do what feels good now. Your work tends to be careless and disorganized.',
		                                             'facets' => array('self-efficacy' => array('title' => 'Self Efficacy', 'score' => '', 'description' => 'Self-Efficacy describes confidence in one\'s ability to accomplish things. High scorers believe they have the intelligence (common sense), drive, and self-control necessary for achieving success. Low scorers do not feel effective, and may have a sense that they are not in control of their lives.'),
		                                                               'orderliness' => array('title' => 'Orderliness', 'score' => '', 'description' => 'Persons with high scores on orderliness are well-organized. They like to live according to routines and schedules. They keep lists and make plans. Low scorers tend to be disorganized and scattered.'),
		                                                               'dutifulness' => array('title' => 'Dutifulness', 'score' => '', 'description' => 'This scale reflects the strength of a person\'s sense of duty and obligation. Those who score high on this scale have a strong sense of moral obligation. Low scorers find contracts, rules, and regulations overly confining. They are likely to be seen as unreliable or even irresponsible.'),
		                                                               'achievement-striving' => array('title' => 'Achievement Striving', 'score' => '', 'description' => 'Individuals who score high on this scale strive hard to achieve excellence. Their drive to be recognized as successful keeps them on track toward their lofty goals. They often have a strong sense of direction in life, but extremely high scores may be too single-minded and obsessed with their work. Low scorers are content to get by with a minimal amount of work, and might be seen by others as lazy.'),
		                                                               'self-discipline' => array('title' => 'Self Discipline', 'score' => '', 'description' => 'Self-discipline, what many people call “will-power,” refers to the ability to persist at difficult or unpleasant tasks until they are completed. People who possess high self-discipline are able to overcome reluctance to begin tasks and stay on track despite distractions. Those with low self-discipline procrastinate and show poor follow-through, often failing to complete tasks-even tasks they want very much to complete.'),
		                                                               'cautiousness' => array('title' => 'Cautiousness', 'score' => '', 'description' => 'Cautiousness describes the disposition to think through possibilities before acting. High scorers on the Cautiousness scale take their time when making decisions. Low scorers often say or do first thing that comes to mind without deliberating alternatives and the probable consequences of those alternatives.'))
		               ),
		               'neuroticism'       =>  array('title' => 'Neuroticism',
		                                             'slug' => 'neuroticism',
		                                             'description' => '<p>Neuroticism refers to the tendency to experience negative feelings. Those who score high on Neuroticism may experience primarily one specific negative feeling such as anxiety, anger, or depression, but are likely to experience several of these emotions. People high in neuroticism are emotionally reactive. They respond emotionally to events that would not affect most people, and their reactions tend to be more intense than normal. They are more likely to interpret ordinary situations as threatening, and minor frustrations as hopelessly difficult. Their negative emotional reactions tend to persist for unusually long periods of time, which means they are often in a bad mood. These problems in emotional regulation can diminish a neurotic person\'s ability to think clearly, make decisions, and cope effectively with stress.</p>

																	          <p>At the other end of the scale, individuals who score low in neuroticism are less easily upset and are less emotionally reactive. They tend to be calm, emotionally stable, and free from persistent negative feelings. Freedom from negative feelings does not necessarily mean that low scorers experience a lot of positive feelings; frequency of positive emotions is a component of the Extraversion domain.</p>',
		                                             'score' => '',
		                                             'range' => array('high' => 3.564, 'low' => 2.7),
		                                             'high_statement' => 'Your score on Neuroticism is high, indicating that you are easily upset, even by what most people consider the normal demands of living. People consider you to be sensitive and emotional.',
		                                             'average_statement' => 'Your score on Neuroticism is average, indicating that your level of emotional reactivity is typical of the general population. Stressful and frustrating situations are somewhat upsetting to you, but you are generally able to get over these feelings and cope with these situations.',
		                                             'low_statement' => 'Your score on Neuroticism is low, indicating that you are exceptionally calm, composed and unflappable. You do not react with intense emotions, even to situations that most people would describe as stressful.',
		                                             'facets' => array('anxiety' => array('title' => 'Anxiety', 'score' => '', 'description' => 'The "fight-or-flight" system of the brain of anxious individuals is too easily and too often engaged. Therefore, people who are high in anxiety often feel like something dangerous is about to happen. They may be afraid of specific situations or be just generally fearful. They feel tense, jittery, and nervous. Persons low in Anxiety are generally calm and fearless.'),
		                                                               'anger' => array('title' => 'Anger', 'score' => '', 'description' => 'Persons who score high in Anger feel enraged when things do not go their way. They are sensitive about being treated fairly and feel resentful and bitter when they feel they are being cheated. This scale measures the tendency to feel angry; whether or not the person expresses annoyance and hostility depends on the individual\'s level on Agreeableness. Low scorers do not get angry often or easily.'),
		                                                               'depression' => array('title' => 'Depression', 'score' => '', 'description' => 'This scale measures the tendency to feel sad, dejected, and discouraged. High scorers lack energy and have difficult initiating activities. Low scorers tend to be free from these depressive feelings.'),
		                                                               'self-consciousness' => array('title' => 'Self Conciousness', 'score' => '', 'description' => 'Self-conscious individuals are sensitive about what others think of them. Their concern about rejection and ridicule cause them to feel shy and uncomfortable around others. They are easily embarrassed and often feel ashamed. Their fears that others will criticize or make fun of them are exaggerated and unrealistic, but their awkwardness and discomfort may make these fears a self-fulfilling prophecy. Low scorers, in contrast, do not suffer from the mistaken impression that everyone is watching and judging them. They do not feel nervous in social situations.'),
		                                                               'immoderation' => array('title' => 'Immoderation', 'score' => '', 'description' => 'Immoderate individuals feel strong cravings and urges that they have difficulty resisting. They tend to be oriented toward short-term pleasures and rewards rather than long-term consequences. Low scorers do not experience strong, irresistible cravings and consequently do not find themselves tempted to overindulge.'),
		                                                               'vulnerability' => array('title' => 'Vulnerability', 'score' => '', 'description' => 'High scorers on Vulnerability experience panic, confusion, and helplessness when under pressure or stress. Low scorers feel more poised, confident, and clear-thinking when stressed.'))
		               ),
		               'opennesstoexperience'          =>  array('title' => 'Openness to Experience',
		                                                         'slug' => 'opennesstoexperience',
		                                                         'description' => '<p>Openness to Experience describes a dimension of cognitive style that distinguishes imaginative, creative people from down-to-earth, conventional people. Open people are intellectually curious, appreciative of art, and sensitive to beauty. They tend to be, compared to closed people, more aware of their feelings. They tend to think and act in individualistic and nonconforming ways. Intellectuals typically score high on Openness to Experience; consequently, this factor has also been called Culture or Intellect. Nonetheless, Intellect is probably best regarded as one aspect of openness to experience. Scores on Openness to Experience are only modestly related to years of education and scores on standard intelligent tests.</p>

																	          <p>Another characteristic of the open cognitive style is a facility for thinking in symbols and abstractions far removed from concrete experience. Depending on the individual\'s specific intellectual abilities, this symbolic cognition may take the form of mathematical, logical, or geometric thinking, artistic and metaphorical use of language, music composition or performance, or one of the many visual or performing arts. People with low scores on openness to experience tend to have narrow, common interests. They prefer the plain, straightforward, and obvious over the complex, ambiguous, and subtle. They may regard the arts and sciences with suspicion, regarding these endeavors as abstruse or of no practical use. Closed people prefer familiarity over novelty; they are conservative and resistant to change.</p>

																	          <p>Openness is often presented as healthier or more mature by psychologists, who are often themselves open to experience. However, open and closed styles of thinking are useful in different environments. The intellectual style of the open person may serve a professor well, but research has shown that closed thinking is related to superior job performance in police work, sales, and a number of service occupations.</p>',
		                                                         'score' => '',
		                                                         'range' => array('high' => 4.241, 'low' => 3.552),
		                                                         'high_statement' => 'Your score on Openness to Experience is high, indicating you enjoy novelty, variety, and change. You are curious, imaginative, and creative.',
		                                                         'average_statement' => 'Your score on Openness to Experience is average, indicating you enjoy tradition but are willing to try new things. Your thinking is neither simple nor complex. To others you appear to be a well-educated person but not an intellectual.',
		                                                         'low_statement' => 'Your score on Openness to Experience is low, indicating you like to think in plain and simple terms. Others describe you as down-to-earth, practical, and conservative.',
		                                                         'facets' => array('imagination' => array('title' => 'Imagination', 'score' => '', 'description' => 'To imaginative individuals, the real world is often too plain and ordinary. High scorers on this scale use fantasy as a way of creating a richer, more interesting world. Low scorers are on this scale are more oriented to facts than fantasy.'),
		                                                                           'artistic' => array('title' => 'Artistic Interests', 'score' => '', 'description' => 'High scorers on this scale love beauty, both in art and in nature. They become easily involved and absorbed in artistic and natural events. They are not necessarily artistically trained nor talented, although many will be. The defining features of this scale are interest in, and appreciation of natural and artificial beauty. Low scorers lack aesthetic sensitivity and interest in the arts.'),
		                                                                           'emotionality' => array('title' => 'Emotionality', 'score' => '', 'description' => 'Persons high on Emotionality have good access to and awareness of their own feelings. Low scorers are less aware of their feelings and tend not to express their emotions openly.'),
		                                                                           'adventurousness' => array('title' => 'Adventurousness', 'score' => '', 'description' => 'High scorers on adventurousness are eager to try new activities, travel to foreign lands, and experience different things. They find familiarity and routine boring. They will take a new route home just because it is different. Low scorers tend to feel uncomfortable with change and prefer familiar routines.'),
		                                                                           'intellect' => array('title' => 'Intellect', 'score' => '', 'description' => 'Intellect and artistic interests are the two most important, central aspects of openness to experience. High scorers on Intellect love to play with ideas. They are open-minded to new and unusual ideas, and like to debate intellectual issues. They enjoy riddles, puzzles, and brain teasers. Low scorers on Intellect prefer dealing with either people or things rather than ideas. They regard intellectual exercises as a waste of time. Intellect should not be equated with intelligence. Intellect is an intellectual style, not an intellectual ability, although high scorers on Intellect score slightly higher than low-Intellect individuals on standardized intelligence tests.'),
		                                                                           'liberalism' => array('title' => 'Liberalism', 'score' => '', 'description' => 'Psychological liberalism refers to a readiness to challenge authority, convention, and traditional values. In its most extreme form, psychological liberalism can even represent outright hostility toward rules, sympathy for law-breakers, and love of ambiguity, chaos, and disorder. Psychological conservatives prefer the security and stability brought by conformity to tradition. Psychological liberalism and conservatism are not identical to political affiliation, but certainly incline individuals toward certain political parties.'))
		               )
		);
	}

	static public function get_csv_keys() {
		$csv_keys = array(  'first_name' => 'First Name',
							'last_name' =>'Last Name',
							'date_created' =>'Entry Date',
							'gender'=>'Gender',
							'age_range'=>'Age Range',
							'email'=>'Email',
							's_administration'=>'Administration',
							's_apostleship'=>'Apostleship',
							's_discernment'=>'Discernment',
							's_evangelism'=>'Evangelism',
							's_exhortation'=>'Exhortation',
							's_faith'=>'Faith',
							's_giving'=>'Giving',
							's_knowledge'=>'Knowledge',
							's_leadership'=>'Leadership',
							's_mercy'=>'Mercy',
							's_pastoring'=>'Pastoring',
							's_prophecy'=>'Prophecy',
							's_serving'=>'Serving',
							's_teaching'=>'Teaching',
							's_wisdom'=>'Wisdom',
							'p_extraversion'=>'Extraversion',
							'p_agreeableness'=>'Agreeableness',
							'p_conscientiousness'=>'Conscientiousness',
							'p_neuroticism'=>'Neuroticism',
							'p_opennesstoexperience'=>'Openness To Experience',
							/*'billing_country'=> 'Country You Live In',
							'billing_city'=>'City of Residence',
							'billing_state'=>'State of Residence',
							'country_of_birth'=>'Country of Birth',
							'race'=>'Race/Ethnicity',
							'marital_status'=>'Marital Status',
							'income'=>'Income',
							'household_size'=>'Household',
							'religion'=>'Faith/Religious Preference',
							'religion_importatnce'=>'Importance of Religion',
							'faith_age'=>'Age You Came to Faith',
							'attendance'=>'Attendance',
							'formal_service'=>'Formal Service',
							'informal_service'=>'Informal Service',
							'current_ministry'=>'Ministry You Serve In',
							'capacity_service'=>'Capacity You Serve In',
							'equipped'=>'Properly Equipped',
							'spiritual_gifts_usage'=>'Using Your Gifts',
							'happiness_ministry'=>'Happy In Ministry'*/);

		return $csv_keys;
	}

	static function personality_sorter ($a, $b) {
		if ($a['score'] == $b['score']) {
			return 0;
		}
		return ($a['score'] < $b['score']) ? 1 : -1;
	}

	function disconnect_user() {

		$id = $_POST['user_id'];

		if($_POST['etype'] == 'legacy') {
            SGTLegacy::disconnect_entry();
        }

		if($_POST['etype'] == 'manual') {
		    die();
        }

		if(base64_encode($id) == $_POST['verify']) {
			update_user_meta($id, 'church_code_previous', get_user_meta($id, 'church_code', true));
			delete_user_meta($id, 'church_code');

			wp_send_json_success();

		} else
			wp_send_json_error();


		die();
	}

	function delete_manual_entry() {
		$id = $_POST['user_id'];

		if($_POST['etype'] != 'manual') {
		    die();
        }

		if(base64_encode($id) == $_POST['verify']) {
			$deletion_hash = $_POST['hash'];
			$new_entries = array();

			foreach (get_user_meta($id, 'church_manual_entries', true) as $entry) {
				$cur_hash = base64_encode( $entry["first_name"] . $entry["last_name"] . $entry["age_range"] );

				if ($cur_hash != $deletion_hash) {
					array_push($new_entries, $entry);
				}
			}

			update_user_meta($id, 'church_manual_entries', $new_entries);

			wp_send_json_success();
		} else
			wp_send_json_error();


		die();
	}

	function delete_results() {

		$id = $_POST['user_id'];

		if(base64_encode($id) == $_POST['verify'] &&
		    (get_user_meta($id, 'quiz_results_sgt_a') ||
		     get_user_meta($id, 'quiz_results_sgt_y') ||
		     get_user_meta($id, 'quiz_results_personality') ) ) {

			delete_user_meta( $id, 'quiz_results_sgt_a' );
			delete_user_meta( $id, 'quiz_results_sgt_y' );
			delete_user_meta( $id, 'quiz_results_personality' );
			wp_send_json_success();

		} else
			wp_send_json_error();

		die();
	}


	function simple_sign_up( $value ) {
		if(get_query_var('church-id'))
			$church = SGTChurch::get_church(get_query_var('church-id'));

		if ($church->get_simple_sign_up())
			return 1;

		return;
	}

}
