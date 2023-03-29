<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */


class QuizResultsPersonality extends QuizResults {

	//The sets for quizData groupings
	private static $sets = array('extraversion' => array('friendliness', 'gregariousness', 'assertiveness', 'activity_level', 'excitement_seeking', 'cheerfulness'),
						 'agreeableness' => array('trust', 'morality', 'altruism', 'cooperation', 'modesty', 'sympathy'),
						 'conscientiousness' => array('self-efficacy', 'orderliness', 'dutifulness', 'achievement-striving', 'self-discipline', 'cautiousness'),
						 'neuroticism' => array('anxiety', 'anger', 'depression', 'self-consciousness', 'immoderation', 'vulnerability'),
						 'opennesstoexperience' => array('imagination', 'artistic', 'emotionality', 'adventurousness', 'intellect', 'liberalism'));

	private static $sets_settings;

	private $theDomain;
	private $theFacet;

	function __construct(SGTChurchMember $member) {
		$this->member = $member;
		$this->quizData = $this->member->quiz_results_personality;

		$this->process_data();

	}

	protected function process_data() {
		//Make sure all keys are lower case
		$this->quizData = array_change_key_case($this->quizData);

		//Set the date quiz was taken
		if($this->quizData['date_created']) {
			$this->date = strtotime($this->quizData['date_created']);
			unset( $this->quizData['date_created'] );
		}

		$temp = $this->quizData;
		unset($this->quizData);
		foreach(self::$sets as $key => $set) {
			$this->quizData[$key] = array();
			foreach($set as $trait) {
				$this->quizData[$key][$trait] = $temp[$trait];
			}
			arsort($this->quizData[$key]);
		}
		uasort($this->quizData, array(__CLASS__, 'sorter'));

	}

	private function sorter($a, $b) {
		if (array_sum($a) == array_sum($b)) {
			return 0;
		}
		return (array_sum($a) < array_sum($b)) ? 1 : -1;
	}

	static function sets_settings(){
		if(!self::$sets_settings)
			self::$sets_settings = SGTQuizSettings::get_personality_sets();

		return self::$sets_settings;
	}

	private function set_the_domain() {
		$this->theDomain = key($this->quizData);
	}

	public function get_the_domain() {
		return $this->theDomain;
	}

	private function is_domain($domain) {
		$domain = strtolower($domain);
		if(!array_key_exists($domain, self::sets_settings()))
			return false;

		return $this->sets_settings()[$domain];

	}

	public function has_domain() {

		if(current($this->quizData))
			return true;

		unset($this->theDomain);
		reset($this->quizData);
		return false;

	}

	public function the_domain() {
		$this->set_the_domain();

		//Indent array to next array element for next use
		next($this->quizData);
	}

	private function set_the_facet() {
		$this->theFacet = key($this->quizData[$this->get_the_domain()]);
	}

	public function get_the_facet() {
		return $this->theFacet;
	}

	private function is_facet($facet) {
		$facet = strtolower($facet);
		foreach(self::sets_settings() as $domain => $settings) {
			if($domain == $facet)
				return false;

			if(array_key_exists($facet, $settings['facets']))
				return $settings['facets'][$facet];
		}

		return false;
	}

	static function get_facet_domain($facet) {
		$facet = strtolower($facet);
		foreach(self::sets_settings() as $domain => $settings) {
			if($domain == $facet)
				return false;

			if(array_key_exists($facet, $settings['facets']))
				return $domain;
		}

		return false;

	}

	public function has_facet($domain = '') {
		if(!$domain)
			$domain = $this->get_the_domain();

		if(current($this->quizData[$domain]))
			return true;

		unset($this->theFacet);
		reset($this->quizData[$domain]);
		return false;

	}

	public function the_facet($domain = '') {
		if(!$domain)
			$domain = $this->get_the_domain();

		$this->set_the_facet();

		next($this->quizData[$domain]);

	}

	protected function get_selector($selector = '') {
		if($selector)
			return $selector;

		return $this->get_the_facet() ? $this->get_the_facet() : $this->get_the_domain();

	}

	public function get_name($selector = '') {
		return $this->get_selector($selector);
	}

	public function the_name($format = '') {
		echo $this->get_formatted('name', $format);
	}

	public function get_title($selector = '') {
		$selector = $this->get_selector($selector);
		if(self::is_domain($selector))
			return self::$sets_settings[$selector]['title'];

		if($facet = self::is_facet($selector))
			return $facet['title'];

		return false;

	}

	public function the_title($format = '') {
		echo $this->get_formatted('title', $format);
	}

	public function get_range($selector = '') {
		$selector = $this->get_selector($selector);
		if($this->is_facet($selector)) {
			$domain = self::get_facet_domain( $selector );
			$domain = $this->is_domain( $domain );
			$divider = 4;
		} else {
			$domain = $this->is_domain( $selector );
			$divider = 20;
		}
		$score = $this->get_score($selector);
		if($score / $divider >= $domain['range']['high'])
			return 'high';
		if($score / $divider <= $domain['range']['low'])
			return 'low';

		return 'average';

	}

	public function the_range($format = '') {
		echo $this->get_formatted('range', $format);
	}


	public function get_percentile($selector = '') {
		$selector = $this->get_selector($selector);

		$score = $this->get_score($selector);

		if($this->is_domain($selector))
			return round($score / 120 * 100);

		if($this->is_facet($selector))
			return round($score / 20 * 100);

		return false;
	}

	public function the_percentile($format = '') {
		echo $this->get_formatted('percentile', $format);
	}

	public function get_description($selector = '') {
		$selector = $this->get_selector($selector);
		if(self::is_domain($selector))
			return self::$sets_settings[$selector]['description'];

		if($facet = self::is_facet($selector))
			return $facet['description'];

		return false;

	}

	public function the_description($format = NULL) {
		echo $this->get_formatted('description', $format);

	}

	public function get_statement($selector = '' ) {

		$selector = $this->get_selector($selector);
		if(self::is_domain($selector))
			return self::$sets_settings[$selector][$this->get_range($selector).'_statement'];

		return false;

	}

	public function the_statement($format = NULL) {
		echo $this->get_formatted('statement', $format);
	}

	public function get_score($selector = '') {
		$selector = $this->get_selector($selector);

		if(self::is_domain($selector))
			return array_sum($this->quizData[$selector]);

		if(self::is_facet($selector))
			return $this->quizData[self::get_facet_domain($selector)][$selector];

		return false;

	}

	public function the_score($format = NULL) {
		echo $this->get_formatted('score');
	}

	//Nth methods return a facet if domain is specified
	public function nth($n, $domain = '') {
		//arrays start at 0
		$n = $n - 1;

		if($domain)
			$keys = array_keys($this->quizData[$domain]);
		else
			$keys = array_keys($this->quizData);

		return $keys[$n];
	}

	public function get_nth_name($n, $domain = '') {
		return $this->get_name($this->nth($n, $domain));
	}

	public function nth_name($n, $domain = '') {
		echo $this->get_nth_name($n, $domain);
	}

	public function get_nth_title($n, $domain ='') {
		return $this->get_title($this->nth($n, $domain));
	}

	public function nth_title($n, $domain ='') {
		echo $this->get_nth_title($n, $domain);
	}

	public function get_nth_range($n, $domain = '') {
		return $this->get_range($this->nth($n, $domain));
	}

	public function nth_range($n, $domain = '') {
		echo $this->get_nth_range($n, $domain);
	}

	public function get_nth_percentile($n, $domain = '') {

	}

	public function nth_percentile($n, $domain = '') {
		echo $this->get_nth_percentile($n, $domain);

	}

	public function get_nth_description($n, $domain = '') {

	}

	public function nth_description($n, $domain = '') {
		echo $this->get_nth_description($n, $domain);

	}

	public function get_nth_statement($n, $domain = '' ) {

	}

	public function nth_statement($n, $domain = '') {
		echo $this->get_nth_statement($n, $domain);
	}

	public function get_nth_score($n, $domain = '') {

	}

	public function nth_score($n, $domain) {
		echo $this->get_nth_score($n, $domain);
	}

	public function email_summary() {

		$summary = '<table class="sub-header">'
			                   . '<tr><th colspan="2">Personality Assessment Results:</th></tr>'
			                   . '<tr class="tabel-header-row">'
			                   . '<th>Range</th>'
			                   . '<th>Personality Trait</th>'
			                   . '</tr>';


		while($this->has_domain()) : $this->the_domain();
			$summary .= sprintf('<tr><td>%s</td><td>%s</td></tr>', ucwords($this->get_range()), ucwords($this->get_title()));
		endwhile;
		$summary .= '</table>';

		return $summary;
	}

}