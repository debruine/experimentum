<?php
    
/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/
 
 require_once 'exp.experiment.class.php';
 
class exp_slideshow extends experiment {
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;

        if ($this->stimuli_type == 'image') {
            if (!empty($this->left_images)) {
                $text .= '        <td><img src=""  id="left_image" /></td>' . ENDLINE;
            }
            if (!empty($this->center_images)) {
                $text .= '        <td><img src=""  id="center_image" /></td>' . ENDLINE;
            }
            if (!empty($this->right_images)) {
                $text .= '        <td><img src=""  id="right_image" /></td>' . ENDLINE;
            }
        }
        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_javascript_specific() {
        $text = 'console.log("adaptation-specific javascript");' . ENDLINE;
        
        $text .= "
        function slideshow() {
            nextTrial(0);
            setTimeout(slideshow, {$this->increment_time});
        }
        
        $('#beginExp').click(function() { 
            setTimeout(slideshow, {$this->increment_time});
        });" . ENDLINE;

        return $text;
    }    
}

?>