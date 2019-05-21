<?php
    
/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/
 
 require_once 'exp.buttons.class.php';
    
class exp_slider extends exp_buttons {
    function get_input_interface() {
        $text = '';
        
        $cols_to_span = (empty($this->left_images) ? 0 : 1) + (empty($this->center_images) ? 0 : 1) + (empty($this->right_images) ? 0 : 1);
        ifEmpty($cols_to_span, 1);
    
        // slider input interface
        $text .= '    <tr class="input_interface">' . ENDLINE;    
        $text .= "        <td colspan='$cols_to_span'>" . ENDLINE;
        $text .= "            <span id='low_anchor'>" . $this->anchors[0] . "</span>" . ENDLINE;
        
        $maxlength = ($this->range) ? strlen(strval($this->range)) : 64;
        $w = min($maxlength, 15);
        
        $text .= "            <div id='exp_slider'></div>" . ENDLINE;
        $text .= "            <span id='high_anchor'>" . $this->anchors[1] . "</span>" . ENDLINE;
        $text .= '        </td>' . ENDLINE;
        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    // function get_stimuli_interface() {} default to exp_buttons function
    
    function get_javascript_specific() {

        $text =         '    console.log("slider-specific javascript");' . ENDLINE;
        
        $text .=        '    $("#exp_slider").slider({
                                min: '.$this->slider_min.',
                                max: '.$this->slider_max.',
                                step: '.$this->slider_step.',
                                change: function(e, ui) {
                                    $(ui.handle).show();
                                }
                             });' . ENDLINE .
                        '    $("#exp_slider .ui-slider-handle").hide();' . ENDLINE;
        
        $text .=        '    $("#next_trial").show();' . ENDLINE . 
                        '    $(document).keydown(function(e) {
                                 if (e.which == 13) { $("#next_trial").click(); }
                             });' . ENDLINE . 
                        '    $("#next_trial").click(function(){' . ENDLINE .
                        '        if ($("#exp_slider .ui-slider-handle:visible").length == 0) { return true; }' . ENDLINE .
                        '        var response = $("#exp_slider").slider("value");' . ENDLINE .
                        '        $("#exp_slider").slider("value", null);' . ENDLINE .
                        '        $("#exp_slider .ui-slider-handle").hide();' . ENDLINE .
                        '        nextTrial(response);' . ENDLINE .
                        '        return false;' . ENDLINE .
                        '    });';
        
        return $text;

    }
}

?>