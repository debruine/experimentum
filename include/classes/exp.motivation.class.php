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
 
class exp_motivation extends experiment {
    // function get_input_interface() defaults to experiment class function
    
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images">' . ENDLINE;
        
        $text .= '        <td><div id="motivation-container">' . ENDLINE;
        $text .= '            <span id="countdownlabels">7 &amp; 8 &uarr;<br />1 &amp; 2 &darr;</span>' . ENDLINE;
        $text .= '            <div id="countdown"></div>' . ENDLINE;
        $text .= '            <img id="center_image" class="motivation" src=""/>' . ENDLINE;
        $text .= '            <div id="spacebar">Press the space bar for the next trial</div>' . ENDLINE;
        $text .= '        </div></td>' . ENDLINE;

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function set_up_next_trial() {
        $text = '// set up next trial' . ENDLINE;
        
        if ($this->stimuli_type == 'image') {
            $text .= '
            $("#center_image").attr("src", center_image[trialOrder[trial]]);'. ENDLINE;
        } else if ($this->stimuli_type == 'audio') {
                $text .= '
            $("#center_image_player").html("")
                .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
            
            $(".audio").removeClass("played").removeClass("playing").addClass("unplayed").find("span.play").text("PLAY");
            $("audio").load();
            $("#experiment .audio span.choose").hide();
            playing = false;' . ENDLINE;
        } else if ($this->stimuli_type == 'video') {
                $text .= '
            $("video").hide().get(0).pause();
            $("#center_image_vplayer <source").attr("src", center_image[trialOrder[trial]]);
            console.log("loading:", center_image[trialOrder[trial]]);
            
            $(".video").removeClass("played").removeClass("playing").addClass("unplayed");
            $("video").show().each(function() {
                $(this).get(0).load();
            });
            playing = false;' . ENDLINE;
        }
        
        $text .= '
            if ($("#question").length > 0 && typeof(question) !== "undefined") $("#question").html(question[trialOrder[trial]]);
            $("#trial_n").html(trial);
            
            var currentTime = new Date();
            beginTrial = currentTime.getTime();
            return false;' . ENDLINE;
            
        return $text;
    }
    
    function get_trial_att() {
        $trial_att = array();
        
        foreach ($this->trials as $n => $trial) {
            $trial_att['center_image'][$n] = $trial->get_center_image();
            $trial_att['question'][$n] = $trial->get_question();
        }
        
        return $trial_att;
    }
    
    function get_javascript_specific() {
        $text = '    console.log("motivation-specific javascript");' . ENDLINE;
        
        $text .= '
    var defaultTime = ' . $this->default_time . '; // how many milliseconds the image should show if no buttons are pressed
    var incrementTime = ' . $this->increment_time . '; // how many milliseconds to increment or decrement per keypress
    var countDownActive = false; // registers whether the countdown is active so you can record keypresses or not
    var refreshIntervalId = null; // object for saving and cancelling the setInterval function
    var upKey1 = 55; // 7
    var upKey2 = 56; // 8
    var upKey = upKey1;
    var downKey1 = 49; // 1
    var downKey2 = 50; // 2
    var downKey = downKey1;
    var upKeyPresses = 0;
    var downKeyPresses = 0;
    
    $(function() {
        $("img.motivation").hide();
        
        // initialise countdown slider
        $( "#countdown" ).slider({
            orientation: "vertical",
            range: "min",
            min: 0,
            max: defaultTime,
            value: defaultTime
        });
        
        // increment on keypresses
        $(document).keypress(function(e) {
            var d = new Date();
            if (countDownActive) {
                switch(e.which) {
                    // user presses the down Keys
                    case downKey:    
                        countDown(-1*incrementTime);
                        downKeyPresses++;
                        downKey = (downKey == downKey1) ? downKey2 : downKey1; // enforces key switching
                        e.preventDefault();
                        break;    
                                
                    // user presses the up keys
                    case upKey:
                        countDown(incrementTime);
                        upKeyPresses++;
                        upKey = (upKey == upKey1) ? upKey2 : upKey1;
                        e.preventDefault();
                        break;
                    case 32:
                        e.preventDefault(); // prevent screen scroll on accidental space during trial
                        break;
                }
            } else {
                switch(e.which) {
                    // user presses the space key to start the trial
                    case 32: 
                        e.preventDefault(); // prevent screen scroll
                        startCountDown(); 
                        break;
                }
            }
        });
    });
    
    function startCountDown() {
        upKeyPresses = 0;
        downKeyPresses = 0;
        $( "#countdown" ).slider("option", "value", defaultTime);
        countDownActive = true;
        currentTime = new Date();
        beginTrial = currentTime.getTime();
        $("#spacebar").hide();
        $("img.motivation").show();
        refreshIntervalId = setInterval("countDown(-100)", 100);
    }
    
    function countDown(incr) {
        var currentValue = $( "#countdown" ).slider("option", "value");
        var newValue = (currentValue + incr > defaultTime) ? defaultTime : currentValue + incr; // make sure you cannot bank time
        $( "#countdown" ).slider("option", "value", newValue);
        
        if (currentValue + incr <= 0) { 
            clearInterval(refreshIntervalId); 
            countDownActive = false;
            $("img.motivation").hide();
            $("#spacebar").show();

            nextTrial({up: upKeyPresses, down: downKeyPresses});
        }
    }';
    
        return $text;
    }
}

?>