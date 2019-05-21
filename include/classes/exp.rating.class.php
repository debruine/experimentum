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
    
class exp_rating extends exp_buttons {
    function get_input_interface() {
        $text = '';
        
        $cols_to_span = (empty($this->left_images) ? 0 : 1) + (empty($this->center_images) ? 0 : 1) + (empty($this->right_images) ? 0 : 1);
        ifEmpty($cols_to_span, 1);
    
        // rating input interface
        $text .= '    <tr class="input_interface">' . ENDLINE;    
        $text .= "        <td colspan='$cols_to_span'>" . ENDLINE;
        $text .= "            <span id='low_anchor'>" . $this->anchors[0] . "</span>" . ENDLINE;
        
        $maxlength = ($this->range) ? strlen(strval($this->range)) : 64;
        $w = min($maxlength, 15);
        
        $text .= "            <input type='number' 
                                min='1' max='{$this->range}'
                                style='width: {$w}em' 
                                class='rating' 
                                name='rating' 
                                id='rating' 
                                value='' 
                                autocomplete='off'
                                maxlength='$maxlength'
                                onkeypress='ratingKeyPress(this, event, 1, {$this->range})' />" . ENDLINE;
        $text .= "            <span id='high_anchor'>" . $this->anchors[1] . "</span>" . ENDLINE;
        $text .= '        </td>' . ENDLINE;
        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    // function get_stimuli_interface() {} default to exp_buttons function
    
    function get_javascript_specific() {

        $text =         '    console.log("rating-specific javascript");' . ENDLINE;
        $text .=         '    function ratingKeyPress(myfield, e, min, max) {' . ENDLINE .
                        '        var keycode;' . ENDLINE .
                        '        if (window.event) {' . ENDLINE .
                        '            keycode = window.event.keyCode;' . ENDLINE .
                        '        } else if (e) {' . ENDLINE .
                        '            keycode = e.which;' . ENDLINE .
                        '        } else {' . ENDLINE .
                        '            return true;' . ENDLINE .
                        '        }' . ENDLINE .
                        '' . ENDLINE .
                        '        if (keycode == 13) {' . ENDLINE .
                        '            var response = $("#rating").val();' . ENDLINE .
                        '            if (max==0 && response.length>0) {' . ENDLINE .
                        '                nextTrial("\'" + response + "\'");' . ENDLINE .
                        '            } else if (response>=min && response<=max) {' . ENDLINE .
                        '                nextTrial(response);' . ENDLINE .
                        '            } else {' . ENDLINE .
                        '                $("#rating").val("");' . ENDLINE .
                        '            }' . ENDLINE .
                        '            return false;' . ENDLINE .
                        '        } else {' . ENDLINE .
                        '            return true;' . ENDLINE .
                        '        }' . ENDLINE .
                        '    }';
        
        return $text;

    }
}

?>