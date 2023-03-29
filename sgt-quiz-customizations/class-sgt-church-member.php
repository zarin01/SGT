<?php
/**
 * Author: Robert Iseley
 * Date: 7/12/18
 */

/**
 * Class SGTChurchMember
 * @property QuizResultsSGT $sgt
 * @property QuizResultsPersonality $personality
 */
class SGTChurchMember extends WP_User
{


    protected $churchCode;
    protected $isChurchAdmin;
    protected $isChurchPrimary;
    protected $church;
    private $sgt;
    private $personality;
    protected $youth;
    protected $country;
    protected $gender;
    protected $ageRange;

    function __construct($id = 0, $name = '', $site_id = '')
    {
        parent::__construct($id, $name, $site_id);


        if ($this->churchCode = $this->church_admin_code)
            $this->isChurchAdmin = true;

        if (!$this->churchCode)
            $this->churchCode = $this->church_code;

        if ($this->church_admin_primary)
            $this->isChurchPrimary = true;

        if ($this->churchCode) ;
        $this->church = SGTChurch::get_church($this->churchCode);

        if ($this->quiz_results_sgt_a || $this->quiz_results_sgt_y)
            $this->sgt = new QuizResultsSgt($this);

        if (!$this->quiz_results_sgt_a && $this->quiz_results_sgt_y)
            $this->youth = true;

        if ($this->quiz_results_personality)
            $this->personality = new QuizResultsPersonality($this);

        $this->country = $this->billing_country;
        $this->ageRange = $this->age_range;
    }

    function has_quiz($quiz = 'any')
    {
        if ($quiz == 'any' && (isset($this->sgt) || isset($this->personality)))
            return true;

        if (isset($this->$quiz))
            return true;

        return false;
    }

    function the_quiz($quiz)
    {
        return $this->$quiz;
    }

    function get_church()
    {
        return $this->church;
    }

    public function get_church_code()
    {
        return $this->churchCode;
    }

    public function quiz_date($format = 'm/d/Y')
    {

        if ($this->sgt)
            return $this->sgt->quiz_date($format);
        if ($this->personality)
            return $this->personality->quiz_date($format);

        return false;

    }

    public function is_admin()
    {
        return $this->isChurchAdmin;
    }

    public function admin_for($userid)
    {

        if (!$this->is_admin())
            return false;

        if (!is_numeric($userid))
            return false;

        $user = new SGTChurchMember($userid);

        if ($user->get_church_code() == $this->get_church_code())
            return true;

        return false;
    }

    public function get_age_range()
    {
        return $this->ageRange;
    }

    public function get_gender()
    {
        return $this->gender;
    }

    public function get_top_gift()
    {
        if (!$this->sgt)
            return NULL;

        return $this->sgt->get_nth_name(1);
    }

    public function get_top_three($return = 'string')
    {
        if (!$this->sgt)
            return NULL;

        $top_three = array();
        $top_three[] = $this->sgt->get_nth_name(1);
        $top_three[] = $this->sgt->get_nth_name(2);
        $top_three[] = $this->sgt->get_nth_name(3);

        if ($return == 'string')
            return implode(' ', $top_three);

        return $top_three;
    }

    public function get_all_gift($return = 'string')
    {
        if (!$this->sgt)
            return NULL;

        $top_fifty = array();
        for ($i = 15; $i >= 1; $i--) {
            $top_fifty[] = $this->sgt->get_nth_name($i);
        }

        return $top_fifty;
    }

    public function get_all_gift_range($return = 'string')
    {
        if (!$this->sgt)
            return NULL;

        $top_fifty = array();
        for ($i = 15; $i >= 1; $i--) {
            $top_fifty[] = $this->sgt->get_nth_range($i);
        }

        return $top_fifty;
    }

    public function get_top_personality()
    {
        if (!$this->personality)
            return NULL;

        return $this->personality->get_nth_name(1);
    }

    public function identity_set()
    {
        if (!$this->sgt || !$this->personality)
            return NULL;

        $identity_sets = new WP_Query(array('post_type' => 'combinedresults', 'tag' => $this->get_top_gift() . " " . $this->personality->get_nth_range(1) . '-' . $this->get_top_personality()));
        return $identity_sets->posts[0]->post_title;

    }

    public function is_youth()
    {
        return $this->youth;
    }

}

function sgt_get_current_user()
{
    return new SGTChurchMember(get_current_user_id());
}