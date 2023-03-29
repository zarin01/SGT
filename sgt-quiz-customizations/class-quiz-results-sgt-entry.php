<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */


class QuizResultsSgtEntry extends QuizResultsSgtBase {

	private $entry;
	private $church;
	public $ID;
	protected $firstName;
	protected $lastName;
	protected $ageRange;
	protected $gender;
	public $type;

	function __construct($entry, SGTChurch $church) {
		$this->entry = $entry;
		$this->church = $church;
		$this->ID = $entry['ID'];
		$this->date = strtotime($this->entry['date_created']);
		$this->firstName = $entry['first_name'];
		$this->lastName = $entry['last_name'];
		$this->ageRange = $entry['age_range'];
		$this->gender = $entry['gender'];

		$this->process_data();
	}

	protected function process_data() {
		$this->quizData = $this->entry['sgt'];

		if($this->church->get_gift_filters()) {
			foreach($this->church->get_gift_filters() as $filter) {
				unset($this->quizData[$filter]);
			}
		}

		// Divide all scores by 7 (number of question per gift)
		$this->quizData = array_map(function ($val) { return round($val / 7, 3); }, $this->quizData);


		//Create the population rankings percentiles and sort
		$gift_averages = SGTQuizSettings::get_gift_averages();
		foreach($this->quizData as $gift => $score) {
			$this->giftRanks[$gift] = $score/$gift_averages[$gift];
		}
		arsort($this->giftRanks);
	}

	public function get_first_name() {
		return $this->firstName;
	}

	public function get_last_name() {
		return $this->lastName;
	}

	public function get_age_range() {
		return $this->ageRange;
	}

	public function get_gender() {
		return $this->gender;
	}
}