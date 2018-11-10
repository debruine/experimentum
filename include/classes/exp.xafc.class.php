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
 
class exp_xafc extends experiment {
    // function get_input_interface() defaults to experiment class function

    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;

        // set max-width
        $image_count = count($this->xafc_images[0]);
        if ($image_count<6) {
            $max_width = (100/$image_count) - 2;
        } else {
            $max_width = (100/(ceil($image_count/2))) - 2;
        }
        
        $text .= '        <td>' . ENDLINE;
        for ($n = 1; $n <= $image_count; $n++) {
            if ($this->stimuli_type == 'image') {
                $text .= "            <img src='' id='xafc_$n' style='max-width: $max_width%;' onclick='nextTrial($n)' />" . ENDLINE;
            } else if ($this->stimuli_type == 'audio') {
                $text .= '        <div class="audio" id="xafc_' . $n . '">
                <span class="play">PLAY</span>
                <span class="choose" onclick="nextTrial("' . $n . '"); return false;">choose</span>
                <audio id="xafc_' . $n . '_player">
                    Your browser does not support the audio element.
                </audio>
            </div>' . ENDLINE;
            } else if ($this->stimuli_type == 'video') {
                $text .= '        <div class="video" id="xafc_' . $n . '">
                <video id="xafc_' . $n . '_vplayer">
                    <source src="" type="video/mp4" />
                    Your browser does not support the video element.
                </video>
                <span class="choose" onclick="nextTrial("' . $n . '"); return false;">choose</span>
            </div>' . ENDLINE;
            }
        }
        $text .= '        </td>' . ENDLINE;

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function side() {
        // side randomisation: side is recorded as the position for each image, so 5,3,4,1,2 means that the first image is in position 5, the second in position 3, etc.
        $c = count($this->xafc_images[0]);
        $r = range(1,$c);
        $total = count($this->trials); // $this->random_stim;
        
        for ($i = 0; $i<$total; $i++) {
            if ($this->side == 'random') { shuffle($r); }
            $side[] = '[' . implode(',',$r) . ']';
        }

        return "    side = [0,\n\t\t\t"     . implode(",\n\t\t\t", $side) . '];' . ENDLINE;
    }
    
    function set_up_next_trial() {
        return '// set up next trial

            var xafc_count = $(".xafc img").length;
            for (var n=0; n<xafc_count; n++) {
                var nextDV = n+1;
                $("#xafc_" + side[trialOrder[trial]][n])
                    .attr("src", xafc_images[trialOrder[trial]][n])
                    .removeAttr("onclick")
                    .unbind("click")
                    .attr("dv", n+1)
                    .click(function() { nextTrial($(this).attr("dv")); });
            }
            
            if ($("#question").length > 0 && typeof(question) !== "undefined") {
                $("#question").html(question[trialOrder[trial]]);
            }
            
            $("#trial_n").html(trial);
            
            var currentTime = new Date();
            beginTrial = currentTime.getTime();
            return false;';
    }
    
    function get_trial_att() {
        $trial_att = array();
        
        foreach ($this->trials as $n => $trial) {
            $trial_att['xafc_images'][$n] = $trial->get_xafc_images();
            $trial_att['question'][$n] = $trial->get_question();
            $trial_att['q_image'][$n] = $trial->get_q_image();
        }
        
        return $trial_att;
    }
    
    function get_javascript_specific() {
        $text = '    console.log("xafc-specific javascript");' . ENDLINE;
        return $text;
    }
}

?>