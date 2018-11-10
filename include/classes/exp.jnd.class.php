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

class exp_jnd extends experiment {
    function get_input_interface() {
        $text = '';
        
        $cols_to_span = (empty($this->left_images) ? 0 : 1) + (empty($this->center_images) ? 0 : 1) + (empty($this->right_images) ? 0 : 1);
        ifEmpty($cols_to_span, 1);
    
        // jnd input interface
        if (!empty($this->center_images)) {
            $text .= '    <tr class="input_interface jnd3">' . ENDLINE;
        } else {
            $text .= '    <tr class="input_interface">' . ENDLINE;
        }
        $revlabels = array_reverse($this->labels, true);
        foreach ($revlabels as $i => $label) {
            $text .= "    <td onclick='nextTrial(" . ($i+4) . ")'>$label</td>" . ENDLINE;
        }
        if (!empty($this->center_images)) $text .= "    <td class='center'></td>" . ENDLINE;
        foreach ($this->labels as $i => $label) {
            $text .= "    <td onclick='nextTrial(" . (-$i+3) . ")'>$label</td>" . ENDLINE;
        }
        
        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;

        if ($this->stimuli_type == 'image') {
            $text .= '        <td colspan="4"><img src=""  id="left_image" /></td>' . ENDLINE;
            if (!empty($this->center_images)) $text .= '        <td><img src="" id="center_image" /></td>' . ENDLINE;
            $text .= '        <td colspan="4"><img src=""  id="right_image" /></td>' . ENDLINE;
        } else if ($this->stimuli_type == 'audio') {
            $text .= '        <td colspan="4"><div class="audio" id="left_image">
                <span class="play">PLAY</span>
                <audio id="left_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            
            if (!empty($this->center_images)) {
                $text .= '        <td><div class="audio" id="center_image">
                <span class="play">PLAY</span>
                <audio id="center_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            }
            
            $text .= '        <td colspan="4"><div class="audio" id="right_image">
                <span class="play">PLAY</span>
                <audio id="right_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
        }

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_trial_att() {
        $trial_att = array();
        foreach ($this->trials as $n => $trial) {
            $trial_att['left_image'][$n] = $trial->get_left_image();
            $trial_att['center_image'][$n] = $trial->get_center_image();
            $trial_att['right_image'][$n] = $trial->get_right_image();
            $trial_att['question'][$n] = $trial->get_question();
            $trial_att['label1'][$n] = $trial->get_label1();
            $trial_att['label2'][$n] = $trial->get_label2();
            $trial_att['label3'][$n] = $trial->get_label3();
            $trial_att['label4'][$n] = $trial->get_label4();
        }
        
        return $trial_att;
    }
    
    function record_trial_info() {
        return '
            if (side[trialOrder[trial]] == 2) {
                // target face on right
                response[trialOrder[trial]] = 7-r;
            } else {
                // target face on left
                response[trialOrder[trial]] = r;
            }
        ';
    }
}

?>