<?php
    
/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/

class expChooser {
    public $id;
    public $exptype;
    public $subtype;
    public $exp = false;
    
     function __construct($i) {
        $this->id = $i;
        
        if (is_numeric($i) && $i>0) {
            // get experiment info from mysql table
            $this_exp = new myQuery('SELECT exptype, subtype FROM exp WHERE id=' . $this->id);
            $info = $this_exp->get_assoc(0);
            
            $this->exptype = ($info['exptype'] == '2afc') ? 'tafc' : $info['exptype'];    // '2afc','jnd','rating','slider','buttons','xafc','sort','nback','interactive','motivation','adaptation','other'
            $this->subtype = $info['subtype'];    // 'standard','adapt','speeded','adapt_nopre','large_n'

            switch ($this->exptype) {
                case 'xafc': 
                    require_once 'exp.xafc.class.php';
                    $this->exp = new exp_xafc($i); 
                    break;
                case 'tafc': 
                    require_once 'exp.tafc.class.php';
                    $this->exp = new exp_tafc($i); 
                    break;
                case 'sort':
                    require_once 'exp.sort.class.php';
                    $this->exp = new exp_sort($i); 
                    break;
                case 'motivation':
                    require_once 'exp.motivation.class.php';
                    $this->exp = new exp_motivation($i); 
                    break;
                case 'jnd':
                    require_once 'exp.jnd.class.php';
                    $this->exp = new exp_jnd($i);
                    break;
                case 'buttons':
                    require_once 'exp.buttons.class.php';
                    $this->exp = new exp_buttons($i); 
                    break;
                case 'rating':
                    require_once 'exp.rating.class.php';
                    $this->exp = new exp_rating($i); 
                    break;
                case 'slideshow':
                    require_once 'exp.slideshow.class.php';
                    $this->exp = new exp_slideshow($i); 
                    break;
                case 'slider':
                    require_once 'exp.slider.class.php';
                    $this->exp = new exp_slider($i); 
                    break;
            }
        }
    }
    
    function check_exists() {
        if ($this->exptype == '') { return false; }
        return true;
    }
    
    function check_eligible() {
        $user_sex = $_SESSION['sex'];
        $user_age = $_SESSION['age'];
        
        $query = new myQuery('SELECT lower_age, upper_age, sex FROM exp WHERE id=' . $this->id);
        $expinfo = $query->get_one_array();
        
        $eligible = true;
        
        $eligible = $eligible && (is_null($expinfo['lower_age']) || $user_age >= $expinfo['lower_age']);
        $eligible = $eligible && (is_null($expinfo['upper_age']) || $user_age <= $expinfo['upper_age']);
        $eligible = $eligible && (is_null($expinfo['sex']) || $expinfo['sex'] == 'both' || $user_sex == $expinfo['sex']);
        
        // yoking check (only bother if still eligible)
        if ($eligible) {
            $query = new myQuery('SELECT l.path, c.path, r.path 
                                  FROM trial 
                                  LEFT JOIN stimuli AS l on l.id=left_img
                                  LEFT JOIN stimuli AS c on c.id=center_img
                                  LEFT JOIN stimuli AS r on r.id=right_img 
                                  WHERE exp_id=' . $this->id .' AND (
                                   LOCATE("*SELF*", l.path)
                                   OR LOCATE("*SELF*", c.path)
                                   OR LOCATE("*SELF*", r.path)
                                   OR LOCATE("*OTHER*", l.path)
                                   OR LOCATE("*OTHER*", c.path)
                                   OR LOCATE("*OTHER*", r.path)
                                  )');
            if ($query->get_num_rows() > 0) {
                // experiment needs a yoke table entry for eligibility
                $query = new myQuery('SELECT * FROM yoke WHERE user_id="' . intval($_SESSION['user_id']) . '" AND type="exp" AND id=' . $this->id);
                $eligible = $eligible && ($query->get_num_rows() == 1); 
            }
        }
        
        return $eligible;
    }
    
    function get_exp() {
        return $this->exp;
    }
 }
 
 ?>