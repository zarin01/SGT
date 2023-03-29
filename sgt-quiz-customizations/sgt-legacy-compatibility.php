<?php

class SGTLegacy {

	function __construct() {
		add_action( 'wp_authenticate', array( $this, 'authenticate_check'), 1, 2);

		add_action( 'wp_login', array( $this, 'legacy_database_check' ), 10, 2);
		add_action( 'wp_login', array( $this, 'legacy_subscription_check' ), 10, 2);
		add_action( 'show_user_profile', array( $this, 'legacy_subscription_date' ), 10 );
		add_action( 'edit_user_profile', array( $this, 'legacy_subscription_date' ), 10 );
		add_action( 'edit_user_profile_update', array( $this, 'update_legacy_subscription_date' ) );
		add_action( 'personal_options_update',  array( $this, 'update_legacy_subscription_date' ) );

		add_filter( 'sgt_verify_subscription', array( $this, 'verify_subscription_legacy_date' ), 10, 3);
		add_filter( 'sgt_verify_subscription_not_found', array( $this, 'verify_subscription_legacy_date' ), 10, 2);

		add_action( 'wp_ajax_sgt_legacy_load_more', array( $this, 'load_more' ) );

	}

	function authenticate_check($user, $pass){
		if(!$user)
			return;
		if(get_user_by('user_login', $user[0]))
			return;

		$customer = $this->locate_admin($user, 'emailAddress',true);

		if($customer) {
			if($pass == $customer['Password']) {
				$this->wp_create_user( $customer );
			}
		}

	}

	static function locate_admin($locator, $by = 'emailAddress', $return = false) {
		global $wpdb;

		$customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM customers WHERE $by=%s", $locator ), ARRAY_A);
		if($return)
			return $customer;

		return (bool) $customer;

	}

	static function wp_create_user($customer) {

		if(!$customer)
			return false;
		if(!$customer['emailAddress'] || !$customer['Password'])
			return false;

		if(get_user_by('login', $customer['emailAddress']) || get_user_by('email', $customer['emailAddress']))
			return false;

		remove_action( 'user_register', array( 'SGTQuizSettings', 'auto_login_new_user' ) );

		$user_id = wp_insert_user(array('user_login'    => $customer['emailAddress'],
										'user_pass'     => $customer['Password'],
										'user_email'    => $customer['emailAddress'],
										'first_name'    => $customer['name'],
										'user_registered'   =>  $customer['ocreatedate'],
										'church_name'   => $customer['company_name'],
										'billing_country'   =>  $customer['country']));

		$user = get_user_by('ID', $user_id);
		$coupon = new SGTQuizSettings;
		$coupon->generate_church_code(0, 6, $customer['id']);
		update_user_meta($user_id, 'church_admin_code', $customer['id']);
		update_user_meta($user_id, 'church_admin_primary', 1);

		$discount = $coupon->generate_church_discount_code(10);
		update_user_meta($user_id, 'church_discount_code', $discount);

		if($score = self::get_legacy_score_by_email($customer['emailAddress'])) {
			update_user_meta($user_id, 'quiz_results_sgt_a', $score);
		}

		if(strtotime($customer['subscriptiondate']) > strtotime('now')) {
			update_user_meta($user_id, 'legacy_sub_expiration', $customer['subscriptiondate'] );
			$user->set_role('churchadmin');
		} else {
			$user->set_role('spiritualgiftstest');
		}

		update_user_meta($user_id, 'sgt_legacy_checked', 1);

		return $user_id;
	}

	function legacy_database_check( $user_login, $user ){
		if(get_user_meta($user->ID, 'sgt_legacy_checked', true))
			return;

		$user_info = get_userdata($user->ID);
		if($customer = $this->locate_admin($user_info->user_email, 'emailAddress', true)) {

			$coupon = new SGTQuizSettings;
			$coupon->generate_church_code(0, 6, $customer['id']);
			update_user_meta($user->ID, 'church_admin_code', $customer['id']);
			update_user_meta($user->ID, 'church_admin_primary', 1);

			$discount = $coupon->generate_church_discount_code(10);
			update_user_meta($user->ID, 'church_discount_code', $discount);

			if($score = self::get_legacy_score_by_email($customer['emailAddress'])) {
				update_user_meta($user->ID, 'quiz_results_sgt_a', $score);
			}

			if(strtotime($customer['subscriptiondate']) > strtotime('now')) {
				update_user_meta($user->ID, 'legacy_sub_expiration', $customer['subscriptiondate'] );
				$user->set_role('churchadmin');
			} else {
				$user->set_role('spiritualgiftstest');
			}
		}

		update_user_meta($user->ID, 'sgt_legacy_checked', 1);
	}

	function get_legacy_tests_by_customer_id($id, $offset, $limit) {
		global $wpdb;

		if(!$id)
			return false;

		if(!is_numeric($id))
			return false;

		if($limit == 0)
			$limit = $this->get_legacy_tests_count_by_customer_id($id) + 1;

		$tests = $wpdb->get_results($wpdb->prepare("SELECT * FROM test_form_data WHERE customer_id=%s ORDER BY id DESC LIMIT %d OFFSET %d", $id, $limit, $offset ), ARRAY_A);

		return $tests;

	}

	static function get_legacy_tests_count_by_customer_id($id) {
		global $wpdb;

		if(!$id)
			return false;

		if(!is_numeric($id))
			return false;

		$row = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) as count FROM test_form_data WHERE customer_id=%s", $id ), ARRAY_A);

		return $row['count'];

	}

	static function get_legacy_scores_by_customer_id($customer_id, $offset = 0, $limit = LEGACYLIMIT) {
		global $wpdb;
		$scores = array();
		$tests = (new SGTLegacy)->get_legacy_tests_by_customer_id($customer_id, $offset, $limit);
		if(!$tests)
			return false;

		foreach($tests as $test) {
			unset($score);
			if($score['sgt'] = self::get_legacy_score_by_form_entry_id($test['id'])) {
			    $score['ID'] = $test['id'];
				$score['first_name'] = $test['first_name'];
				$score['last_name']  = $test['last_name'];
				$score['date_created'] = $test['date_taken'];
				$score['email'] = $test['email'];
				$scores[] = $score;
			}
		}

		return $scores;
	}

	function get_legacy_score_by_email($email) {
		global $wpdb;
		if(!$email)
			return false;

		$test = $wpdb->get_row($wpdb->prepare("SELECT * FROM test_form_data WHERE email=%s ORDER BY id DESC", $email ), ARRAY_A);
		if(!$test)
			return false;

		$score = self::get_legacy_score_by_form_entry_id($test['id']);
		if(!$score)
			return false;

		$score['date_created'] = $test['date_taken'];

		return $score;

	}

	function get_legacy_score_by_form_entry_id($id) {
		global $wpdb;
		$score = array();

		$answers = $wpdb->get_results($wpdb->prepare("SELECT * FROM test_data WHERE form_customer_id=%s ORDER BY id DESC", $id ), ARRAY_A);
		if(!$answers)
			return false;

		foreach($answers as $answer) {
			$score[$answer['category_id']] += $answer['score'];
		}

		foreach( $score as $key => $value ) {
			$cat_name = $wpdb->get_row($wpdb->prepare("SELECT category_name FROM categories WHERE id=%s ORDER BY id DESC", $key ), ARRAY_A);
			$score[strtolower($cat_name['category_name'])] = $score[$key];
			unset($score[$key]);
		}
		ksort($score);

		return $score;
	}

	function legacy_subscription_check( $user_login, $user ){
		if($expire_date = get_user_meta($user->ID, 'legacy_sub_expiration', true)) {
			if(strtotime($expire_date) < strtotime('yesterday')) {
				$user->set_role('spiritualgiftstest');
				delete_user_meta($user->ID, 'legacy_sub_expiration', $expire_date);
				return false;
			}
		}
		return true;
	}

	function legacy_subscription_date( $user ) {
		$expiration_date = get_user_meta($user->ID, 'legacy_sub_expiration', true);
		?>

		<div class="visible-only-for-admin">
			<h3>Legacy Subscription Expiration</h3>
			<table class="form-table" >
				<tr>
					<th><label for="quote_of_the_day">Date (yyyy-mm-dd):</label></th>
					<td>
						<?php if ( current_user_can( 'administrator' ) ) : ?>

							<input type="text" name="legacy_sub_expiration" value="<?php echo $expiration_date ?>" class="regular-text" />

						<?php else : ?>

							<?php echo $expiration_date ?>

						<?php endif ?>
					</td>
				</tr>
			</table>
		</div>

		<?php
	}

	function update_legacy_subscription_date( $user_id ) {
		if ( isset( $_POST['legacy_sub_expiration'] ) && current_user_can( 'administrator' ) )
			update_user_meta($user_id, 'legacy_sub_expiration', sanitize_text_field( wp_unslash( $_POST['legacy_sub_expiration'] ) ) );
	}

	function verify_subscription_legacy_date($verified, $church_code, $users) {
		$legacy = false;
		if($verified) {
			foreach($users as $user) {
				if(self::legacy_subscription_check('', $user)) {
					$legacy = true;
				}
			}
			return false;
		}

		if(!$users) {
			if(strtotime(self::get_legacy_subscription_date_by_customer_id($church_code)) > strtotime('yesterday')) {
				return true;
			}
		}

		return $verified;
	}

	static function get_legacy_subscription_date_by_customer_id($customer_id) {
		global $wpdb;

		$customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM customers WHERE id=%s ORDER BY id DESC", $customer_id ), ARRAY_A);

		return $customer['subscriptiondate'];
	}

	function load_more() {
		$admin = sgt_get_current_user();
		$entries = $this->get_legacy_scores_by_customer_id($admin->get_church()->get_church_code(), $_POST['offset']);
		if(!$entries)
			return false;

		$html = '';
		foreach($entries as $entry) {
			$entry_object = new QuizResultsSgtEntry($entry, $admin->get_church());
			$html .= $admin->get_church()->generate_entry_row($entry_object);
		}
		echo $html;
	}

	static function maybe_add_csv_data($data) {
		$admin_id = get_current_user_id();
		$church_code = get_user_meta($admin_id, 'church_admin_code', true);

		if(!is_numeric($church_code)) {
			return $data;
		}

		$entries = self::get_legacy_scores_by_customer_id($church_code, 0, 8000);
		foreach($entries as $entry) {
			$data[] = self::format_entry_data($entry);
		}

		return $data;
	}

	function format_entry_data($data) {

		$quiz_data = $data['sgt'];

		$gift_averages = SGTQuizSettings::get_gift_averages();

		$gift_ranking = array();
		foreach ($quiz_data as $gift => $score) {

			$gift_ranking[$gift] = $score/$gift_averages[$gift];
		}
		arsort($gift_ranking);
		$gift_keys = array_keys($gift_ranking);

		$member_data = array();
		foreach(SGTQuizSettings::get_csv_keys() as $key => $title) {
			switch($key) {
				case 'first_name':
				case 'last_name':
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

	static function get_api_results($request) {
		global $wpdb;
			$rec_limit = isset($_GET['limit']) ? $_GET['limit'] : 500;
			if (isset($_GET['page'])) {
				$offset = ($_GET['page'] - 1) * $rec_limit;
			} else {
				$page = 0;
				$offset = 0;
			}

			$rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM customers WHERE id = %s', $request->get_param('id') ));

			if (!empty($rows)) {
				$query_string = 'SELECT tfd.* FROM customers c LEFT JOIN test_form_data tfd on c.id = tfd.customer_id
    WHERE c.id = "' . $request->get_param('id'). '" AND tfd.date_taken != 0000-00-00 AND tfd.date_taken !="0000-00-00 00:00:00" AND tfd.date_taken!=""';

				if (isset($_GET['user_id']) && $_GET['user_id'] !== "") {
					$query_string .= 'AND tfd.id = ' . $_GET['user_id'] . '';
				}
				if (isset($_GET['name']) && $_GET['name'] !== "") {
					$query_string .= 'AND tfd.first_name LIKE "%' . $_GET['name'] . '%" OR tfd.last_name LIKE "%' . $_GET['name'] . '%" ';
				}
				if (isset($_GET['email']) && $_GET['email'] !== "") {
					$query_string .= 'AND tfd.email = "' . $_GET['email'] . '" ';
				}
				if (isset($_GET['fromDate']) && $_GET['fromDate'] !== "" && isset($_GET['toDate']) && $_GET['toDate'] !== "") {
					$query_string .= 'AND STR_TO_DATE(tfd.date_taken, "%m/%d/%Y") >= STR_TO_DATE("'. $_GET['fromDate'] . '", "%m/%d/%Y") AND STR_TO_DATE(tfd.date_taken, "%m/%d/%Y") <= STR_TO_DATE("'. $_GET['toDate'] . '", "%m/%d/%Y") ';
				}
				$query_string .= ' order by c.id desc LIMIT ' . $offset . ', ' . $rec_limit . '';
				$result_data = array();
				//$sql = mysqli_query($conn,$query_string);
				//print_r($query_string);
				$sql = $wpdb->get_results($query_string, ARRAY_A);
				//print_r($sql);
				//while ($result = mysqli_fetch_assoc($sql)) {
				foreach($sql as $result) {
					$result_data["test_form_data"][] = $result;
				}
//print_r($result_data);
				foreach ($result_data["test_form_data"] as $key => $result) {
					$query_string = "SELECT c.category_name, sum( td.score) AS score, c.sub_category FROM test_data td, categories c where  ministry_flag=0 and c.id=td.category_id and td.customer_id=" . $result['customer_id'] . " and td.form_customer_id=" . $result['id'] . " group by td.category_id order by score desc";
					//$sql = mysqli_query($conn,$query_string);
					$sql = $wpdb->get_results($query_string, ARRAY_A);
					$i = 0;
					//while ($result = mysqli_fetch_assoc($sql)) {
					//print_r($query_string);
					//print_r($sql);
					foreach($sql as $result) {
						if ($i < 1) {
							$result_data["test_form_data"][$key]['test_version'] = $result["sub_category"];
						}
						unset($result["sub_category"]);
						if ($i < 3) {
							$result_data["test_form_data"][$key]['top_three_categories'][] = $result;
						}
						$result_data["test_form_data"][$key]["all_categories"][] = $result;
						$i++;
					}
				}
				return $result_data;
			}
			return array();
		}

		static function disconnect_entry() {
	        global $wpdb;
	        $id = $_POST['user_id'];
            if ($wpdb->query("UPDATE test_form_data SET customer_id = 99999999999 WHERE id = $id"))
                wp_send_json_success();
            else
                wp_send_json_error();

            die();
        }
}
$l = new SGTLegacy();
