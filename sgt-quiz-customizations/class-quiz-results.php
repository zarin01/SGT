<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */


abstract class QuizResults {

	protected $quizData;
	protected $date;
	protected $member;

	abstract protected function process_data();
	abstract protected function get_selector();

	protected function format($string, $format = '') {
		if($format === NULL)
			return $string;

		if(!function_exists($format))
			$format = 'ucfirst';

		return $format($string);
	}

	protected function get_formatted($value, $format = '') {
		$value = "get_$value";
		return $this->format($this->$value(), $format);
	}

	public function quiz_date($format = '') {
		if($format)
			return date($format, $this->date);

		return $this->date;
	}
}