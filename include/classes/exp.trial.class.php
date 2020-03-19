<?php
    
/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/
 
class trial {
    public $trial_n;
    public $name;
    public $left_image;
    public $center_image;
    public $right_image;
    public $xafc_images; // array holding all images in an xafc or sort 
    public $question;
    public $label1;
    public $label2;
    public $label3;
    public $label4;
    public $q_image;
    public $frames;
    
    function __construct($info = array()) {
        foreach ($info as $var => $value) {
            $this->$var = $value;
        }
    }
    
    function get_trial_n() { return $this->trial_n; }
    function get_left_image() { return $this->left_image; }
    function get_center_image() { return $this->center_image; }
    function get_right_image() { return $this->right_image; }
    function get_xafc_images() { return $this->xafc_images; }
    function get_question() { return $this->question; }
    function get_label1() { return $this->label1; }
    function get_label2() { return $this->label2; }
    function get_label3() { return $this->label3; }
    function get_label4() { return $this->label4; }
    function get_q_image() { return $this->q_image; }
    function get_frames() { return $this->frames; }
}

?>