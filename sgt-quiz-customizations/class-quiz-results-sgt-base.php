<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */


abstract class QuizResultsSgtBase extends QuizResults {

	protected $theGift;
	protected $giftRanks = array();
	protected static $giftParameters = array();
	protected $description;

	static function gift_parameters($gift = '') {
		if(!self::$giftParameters)
			self::$giftParameters = SGTQuizSettings::get_gift_parameters();

		if($gift)
			return self::$giftParameters[$gift];

		return self::$giftParameters;
	}

	private function set_the_gift() {
		$this->theGift = key($this->giftRanks);
	}

	public function get_the_gift() {
		return $this->theGift;
	}

	public function is_gift($gift) {
		return array_key_exists($gift, $this->quizData);
	}

	public function has_gift() {

		if(current($this->giftRanks))
			return true;

		unset($this->theGift);
		reset($this->giftRanks);
		return false;

	}

	public function the_gift() {
		$this->set_the_gift();
		unset($this->description);

		//Indent array to next element for next use
		next($this->giftRanks);

	}

	public function reset_gift() {
		unset($this->description);
		unset($this->theGift);
		reset($this->giftRanks);
	}

	protected function get_selector($selector = '') {
		if($selector)
			return $selector;

		return $this->get_the_gift();
	}

	public function get_name($selector = '') {
		$selector = $this->get_selector($selector);
		if(!$this->is_gift($selector))
			return false;

		return $this->get_selector($selector);
	}

	function the_name($format = '') {
		echo $this->get_formatted('name', $format);
	}

	function get_range($selector = '') {
		$selector = $this->get_selector($selector);
		if(!$this->is_gift($selector))
			return false;

		$score = $this->get_score($selector);
		$parameters = $this->gift_parameters($selector);
		if($score >= $parameters['high'])
			return 'high';
		if($score <= $parameters['low'])
			return 'low';

		return 'average';

	}

	function the_range($format = '') {
		echo $this->get_formatted('range', $format);
	}

	function get_score($selector = '') {
		$selector = $this->get_selector($selector);
		if(!$domain = $this->is_gift($selector))
			return false;

		return $this->quizData[$selector];

	}

	public function the_score($format = NULL) {
		echo $this->get_formatted('score', $format);
	}

	public function get_description($selector = '') {
		$selector = $this->get_selector($selector);
		if(!$this->description)
			$this->description = get_page_by_path($selector, OBJECT, 'sgtresults');

		if(!$this->description)
			return NULL;

		return apply_filters('the_content', $this->description->post_content);
	}

	public function the_description($format = NULL) {
		echo $this->get_formatted('description', $format);
	}

	public function get_description_title($selector = '') {
		$selector = $this->get_selector($selector);
		if(!$this->description)
			$this->description = get_page_by_path($selector, OBJECT, 'sgtresults');

		if(!$this->description)
			return NULL;

		return apply_filters('the_title', $this->description->post_title);
	}

	public  function the_description_title($format = NULL) {
		echo $this->get_formatted('description_title', $format);
	}

	function get_nth($n) {
		//arrays start at 0
		$n = $n - 1;

		$keys = array_keys($this->giftRanks);
		return $keys[$n];
	}

	function get_nth_name($n) {
		return $this->get_name($this->get_nth($n));
	}

	function nth_name($n) {
		echo $this->get_nth_name($n);
	}

	function get_nth_range($n) {
		return $this->get_range($this->get_nth($n));
	}

	function nth_range($n) {
		echo $this->get_nth_range($n);
	}
}