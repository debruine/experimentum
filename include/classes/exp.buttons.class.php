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
    
class exp_buttons extends experiment {
    function get_input_interface() {
        $text = '';
        
        $cols_to_span = (empty($this->left_images) ? 0 : 1) + (empty($this->center_images) ? 0 : 1) + (empty($this->right_images) ? 0 : 1);
        ifEmpty($cols_to_span, 1);
    
        // buttons input interface
        if ($this->subtype != "speeded") {
            $text .= '    <tr class="input_interface">' . ENDLINE;    
            $text .= "        <td colspan='$cols_to_span'>" . ENDLINE;
            $text .= "            <span id='low_anchor'>" . $this->anchors[0] . "</span>" . ENDLINE;
            $button_query = new myQuery('SELECT dv, display FROM buttons WHERE exp_id=' . $this->id . " ORDER BY n");
            foreach ($button_query->get_assoc() as $b) {
                $text .= "        <input type='button' value='{$b['display']}' onclick='nextTrial(\"{$b['dv']}\")'/>" . ENDLINE;
            }
            $text .= "            <span id='high_anchor'>" . $this->anchors[1] . "</span>" . ENDLINE;
            $text .= '        </td>' . ENDLINE;
            $text .= '    </tr>' . ENDLINE;
        }
        
        $text .=     '    <tr id="time_out" style="display: none;">' . ENDLINE;
        $text .=     "        <td colspan='$cols_to_span'>" . ENDLINE;
        $text .=     "            <h2>Time Out<h2>" . ENDLINE;
        $text .=     "            <input type='button' value='Next Trial' onclick='nextTrial(null)'/>" . ENDLINE;
        $text .=     '        </td>' . ENDLINE;
        $text .=     '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;

        if ($this->stimuli_type == 'image') {
            if (!empty($this->left_images))     $text .= '        <td><img src=""  id="left_image" /></td>' . ENDLINE;
            if (!empty($this->center_images))     $text .= '        <td><img src=""  id="center_image" /></td>' . ENDLINE;
            if (!empty($this->right_images))     $text .= '        <td><img src=""  id="right_image" /></td>' . ENDLINE;
        } else if ($this->stimuli_type == 'audio') {
            if (!empty($this->left_images)) {
                $text .= '<td><div class="audio" id="left_image">
                <span class="play">PLAY</span>
                <audio id="left_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            }
            if (!empty($this->center_images)) {
                $text .= '<td><div class="audio" id="center_image">
                <span class="play">PLAY</span>
                <audio id="center_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            }
            if (!empty($this->right_images)) {
                $text .= '<td><div class="audio" id="right_image">
                <span class="play">PLAY</span>
                <audio id="right_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            }
        } else if ($this->stimuli_type == 'video') {
            if (!empty($this->left_images)) {
                $text .= '<td><div class="video" id="left_image">
                <video id="left_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </audio></div></td>' . ENDLINE;
            }
            if (!empty($this->center_images)) {
                $text .= '<td><div class="video" id="center_image">
                <video id="center_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video></div></td>' . ENDLINE;
            }
            if (!empty($this->right_images)) {
                $text .= '<td><div class="video" id="right_image">
                <video id="right_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video></div></td>' . ENDLINE;
            }
        }

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_javascript_specific() {
        $text = '    console.log("buttons-specific javascript");' . ENDLINE;

        // speeded buttons javascript
        if ($this->subtype == 'speeded') {
            $text .= '        $(document).keypress( function(e) {' . ENDLINE;
            $text .= '            if (e.which == 32 && trial == 0) { e.preventDefault(); beginExp(); }' . ENDLINE;
            
            $button_query = new myQuery('SELECT dv, display FROM buttons WHERE exp_id=' . $this->id);
            foreach ($button_query->get_assoc() as $b) {
                $text .= '            else if (String.fromCharCode(e.which) == "' . $b['display']. '") {' . ENDLINE;
                $text .= '                nextTrial("' . $b['dv'] . '");' . ENDLINE;
                $text .= '            }' . ENDLINE;
            }
            
            $text .= '        });' . ENDLINE;
        }
        
        return $text;
    }
}

?>