<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */

/**
 * Class SGTChurch
 * @property SGTChurchMember $member
 */

class SGTChurch {

	private static $churches = array();
	private $members;
	protected $member;
	protected $manual;
	protected $legacy;
	protected $entry;
	protected $churchAdmin;
	protected $churchName;
	protected $churchCode;
	protected $churchDiscount;
	protected $testsRemaining;
	protected $giftFilters;
	protected $simpleSignUp;
	protected $error;

	private function __construct($church_code) {

        $getUsers = get_users(array('fields' => 'all_with_meta',
            'number' => 1,
            'meta_query' => array('relation' => 'AND',
                array('key' => 'church_admin_code', 'value' => $church_code, 'compare' => '='),
                array('key' => 'church_admin_primary', 'compare' => 'EXISTS'))));
        $admin = array_shift($getUsers);
		if(empty($admin)) {
			$this->error = "Primary Admin not found";
			return false;
		}

		$this->churchAdmin = $admin;
		$this->churchName = $admin->church_name;
		$this->churchCode = $admin->church_admin_code;
		$this->churchDiscount = $admin->church_discount_code;

		//Grabbing Coupon instance
		$GFCoupons = GFCoupons::get_instance();
		//Grabbing coupon information based on this admins coupon code.
		$church_coupon = $GFCoupons->get_config(0, $this->get_church_discount());

		if($church_coupon['meta']['usageLimit'] = null || $church_coupon['meta']['usageLimit'] = false){
			return $this->testsRemaining;
		} else if( $church_coupon['meta']['usageCount'] = null || $church_coupon['meta']['usageCount'] = false) {
			return $this->testsRemaining;
		} else{
			return $this->testsRemaining = $church_coupon['meta']['usageLimit'] - $church_coupon['meta']['usageCount'];
		}
			

		

		$this->giftFilters = explode(', ', $admin->gift_filter);
		$this->simpleSignUp = $admin->simple_sign_up;
	}

	static function get_church($church_code) {

		if(empty(self::$churches)) {
			$church = new SGTChurch($church_code);
			self::$churches[$church_code] = $church;
			return $church;
		}

		return self::$churches[$church_code];

	}

	public function error() {

		if(isset($this->error)) {
			return $this->error;
		}

		return false;

	}

	public function has_members() {

		if(!isset($this->members)) {
			$this->members = get_users(array('orderby'=>'ID',
			                                 'order'=>'DESC',
			                                 'fields'=>'ID',
				                'meta_query'=> array('relation' => 'OR',
					                array('key'=>'church_admin_code', 'value' => $this->churchCode, 'compare' => '='),
					                array('key'=>'church_code', 'value' => $this->churchCode, 'compare' => '='))));
		}

		if(current($this->members))
			return true;

		reset($this->members);
		unset($this->member);
		return false;

	}

	public function the_member() {
		$this->member =  new SGTChurchMember(current($this->members));

		//Indent to next member for has_members function to check
		next($this->members);

		return $this->member;
	}

	public function has_manual_entry() {
		if(!isset($this->manual)) {
			$admin = get_users(array('orderby'=>'ID',
			                                 'order'=>'DESC',
			                                 'fields'=>'ID',
			                                 'meta_query'=> array('relation' => 'AND',
				                                 array('key'=>'church_admin_code', 'value' => $this->churchCode, 'compare' => '='),
				                                 array('key'=>'church_admin_primary', 'compare' => 'EXISTS'))));
			$this->manual = get_user_meta($admin[0], 'church_manual_entries', true);
		}

		if(is_array($this->manual) && !empty($this->manual)) {
            if (current_action($this->manual)) {
                return true;
            } else {
                reset($this->manual);
                unset($this->entry);
                return false;
            }
        } else {
            return false;
        }
	}

	public function the_manual_entry() {
		$this->entry = new QuizResultsSgtEntry(current($this->manual), $this);
        $this->entry->type = "manual";

		//Indent to next member for has_members function to check
		next($this->manual);

		return $this->entry;

	}

	public function has_legacy_entry() {
		if(!isset($this->legacy)) {
			$this->legacy = SGTLegacy::get_legacy_scores_by_customer_id($this->churchCode);
		}

		if(!is_array($this->legacy) || empty($this->legacy))
			return false;

		if(current($this->legacy))
			return true;

		reset($this->legacy);
		unset($this->entry);
		return false;

	}

	public function the_legacy_entry() {
		$this->entry = new QuizResultsSgtEntry(current($this->legacy), $this);
		$this->entry->type = "legacy";

		//Indent to next member for has_members function to check
		next($this->legacy);

		return $this->entry;

	}

	public function get_the_member() {
		return $this->member;
	}

	public function get_gift_filters() {
		return $this->giftFilters;
	}

	public function get_church_name() {
		return $this->churchName;
	}

	public function get_church_code() {
		return $this->churchCode;
	}

	public function get_church_discount() {
		return $this->churchDiscount;
	}

	public function get_tests_remaining() {
		return $this->testsRemaining;
	}

	public function get_secret() {
		return crc32($this->get_church_code());
	}

	public function get_simple_sign_up() {
		return $this->simpleSignUp;
	}

	public function generate_entry_row(QuizResultsSgtEntry $entry = NULL) {
		if(!$entry)
			$entry = $this->entry;

		$data = sprintf('data-name="%s %s"', $entry->get_first_name(), $entry->get_last_name());
		$data .= sprintf(' data-date="%s"',$entry->quiz_date(NULL));
		$data .= sprintf(' data-age-range="%s"', $entry->get_age_range());

		$data .= sprintf(' data-gender="%s"', $entry->get_gender());
		$data .= sprintf(' data-top-gift="%s"', $entry->get_nth_name(1));
		$data .= sprintf(' data-top-three="%s %s %s"', $entry->get_nth_name(1), $entry->get_nth_name(2), $entry->get_nth_name(3));

		$row = sprintf("<tr class='church-results-row manual %s' %s >", $entry->get_nth_name(1), $data);
		$row .= sprintf('<td>%s %s</td>', $entry->get_first_name(), $entry->get_last_name());
		$row .= sprintf('<td>%s</td>', $entry->quiz_date('m/d/Y'));

		$row .= "<td></td>";

		$row .= '<td><div class="button expand-results"><span class="open">Summary</span><span class="close">Close</span></div>';
        if($entry->type == "legacy")
            $row .= sprintf('<a href="#" class="disconnect-user" data-user="%s" data-verify="%s" data-name="%s" data-type="%s">Disconnect User</a>', $entry->ID, base64_encode($entry->ID), $entry->get_first_name() . " ". $entry->get_last_name(), $entry->type);
		
		$admin = get_users(array('orderby'=>'ID',
			'order'=>'DESC',
			'fields'=>'ID',
			'meta_query'=> array('relation' => 'AND',
				array('key'=>'church_admin_code', 'value' => $this->churchCode, 'compare' => '='),
				array('key'=>'church_admin_primary', 'compare' => 'EXISTS'))));

		$row .= sprintf('<a href="#" class="delete-manual-entry" data-user="%s" data-verify="%s" data-name="%s" data-type="%s" data-hash="%s">Delete Entry</a>', $admin[0], base64_encode($admin[0]), $entry->get_first_name() . " ". $entry->get_last_name(), $entry->type, base64_encode($entry->get_first_name() . $entry->get_last_name() . $entry->get_age_range()));

		$row .= '<div class="subsection">';
		$row .= '<table>';
		$row .= '<tr class="table-header-row">';
		$row .= '<th>Gift/Trait</th>';
		$row .= '<th>Score</th>';
		$row .= '</tr>';
		for($i = 1; $i <=3; $i++) {
			$row .= sprintf('<tr><td>%s</td><td>%s</td>', $entry->get_nth_name($i), $entry->get_nth_range($i));
		}

		$row .= '</table>';
		$row .= '</div>';
		$row .= '</td>';
		$row .= '</tr>';
		return $row;
	}

}