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
 
class exp_tafc extends experiment {
    // function get_input_interface() defaults to experiment class function
    
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;

        if ($this->stimuli_type == 'image') {
            $text .= '        <td onclick="nextTrial(1)"><img src=""  id="left_image" /></td>' . ENDLINE;
            if (!empty($this->center_images)) {
                $text .= '        <td><img src=""  id="center_image" /></td>' . ENDLINE;
            }
            $text .= '        <td onclick="nextTrial(0)"><img src=""  id="right_image" /></td>' . ENDLINE;
        } else if ($this->stimuli_type == 'audio') {
            $text .= '        <td><div class="audio" id="left_image">
                <span class="play">PLAY</span>
                <span class="choose" onclick="nextTrial(1); return false;">choose</span>
                <audio id="left_image_player">
                    Your browser does not support the audio element.
                </audio>
            </div></td>' . ENDLINE;
            
            if (!empty($this->center_images)) {
                $text .= '        <td><div class="audio" id="center_image">
                <span class="play">PLAY</span>
                <audio id="center_image_player">
                    Your browser does not support the audio element.
                </audio></div></td>' . ENDLINE;
            }
            
            $text .= '        <td><div class="audio" id="right_image">
                <span class="play">PLAY</span>
                <span class="choose" onclick="nextTrial(0); return false;">choose</span>
                <audio id="right_image_player">
                    Your browser does not support the audio element.
                </audio>
            </div></td>' . ENDLINE;
        } else if ($this->stimuli_type == 'video') {
            $text .= '        <td><div class="video" id="left_image">
                <video id="left_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video>
                <span class="choose" onclick="nextTrial(1); return false;">choose</span>
            </div></td>' . ENDLINE;
            
            if (!empty($this->center_images)) {
                $text .= '        <td><div class="video" id="center_image">
                <video id="center_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video></div></td>' . ENDLINE;
            }
            
            $text .= '        <td><div class="video" id="right_image">
                <video id="right_image_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video>
                <span class="choose" onclick="nextTrial(0); return false;">choose</span>
            </div></td>' . ENDLINE;
        }

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function record_trial_info() {
        return '
            if (side[trialOrder[trial]] == 2) {
                // target face on right
                response[trialOrder[trial]] = 1-r;
            } else {
                // target face on left
                response[trialOrder[trial]] = r;
            }
        ';
    }
    
    function get_javascript_specific() {
        $text = '    console.log("2afc-specific javascript");' . ENDLINE;

        // !speeded buttons javascript
        if ($this->subtype == 'speeded') {
            $text .= '
    var leftKey = "f";
    var rightKey = "j";
    $(document).keypress( function(e) {
        console.log(e.which + " clicked");
        var str = String.fromCharCode(e.which);
        if (e.which == 32 && trial == 0) { 
            e.preventDefault(); beginExp(); 
        } else if (str == leftKey) {
            nextTrial(1);
        } else if (str == rightKey) {
            nextTrial(0);
        }
    });
';
        }
        
        return $text;
    }
}

?>