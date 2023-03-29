<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */


class QuizResultsSgt extends QuizResultsSgtBase {

	function __construct(SGTChurchMember $member) {
		$this->member = $member;
		$member->quiz_results_sgt_a ? $this->quizData = $this->member->quiz_results_sgt_a : $this->quizData = $this->member->quiz_results_sgt_y;

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

        //Remove gifts church admin doesn't want shown
        if($this->member->get_church()) {
            foreach($this->member->get_church()->get_gift_filters() as $filter) {
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
	
	public function email_summary() {
		$summary = '';
			$summary .= '<table class="top-three-gifts quick-summary-table">'
			                   . '<tr><th colspan="3">Spiritual Gifts Results:</th></tr>'

			                   . '<tr class="tabel-header-row">'
			                   . '<th>Rank</th>'
			                   . '<th>Range</th>'
			                   . '<th>Spiritual Gift</th>'
			                   . '</tr>';

			$ranges = array("high", "average", "low");
			$i = 1;
			foreach ($ranges as $range) {
				while($this->has_gift()) : $this->the_gift();
					if ($range == $this->get_range()) {
						$summary .=  sprintf('<tr class="%s %s">', $this->get_range(), $this->get_name());
						$summary .=  sprintf('<td>%s</td>', $i++);
						$summary .=  sprintf('<td>%s</td>', ucwords($this->get_range()));
						$summary .=  sprintf('<td>%s</td>', ucwords($this->get_name()));
						$summary .=  "</tr>";
					}
				endwhile;
			}

			$summary .= '</table>';

		return $summary;
	}

}