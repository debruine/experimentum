<?php

/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/
 
 require_once 'exp.trial.class.php';
 require_once 'Parsedown.php';
    
class experiment {
    public $id;
    public $name;
    public $exptype;
    public $subtype;
    public $design;
    public $trial_order;
    public $side;
    public $instructions;
    public $question;
    public $labels;
    public $range;
    public $anchors;
    public $orient;
    public $random_stim;
    public $trials;
    public $default_time = 4000;
    public $increment_time = 100;
    public $left_images = array();
    public $center_images = array();
    public $right_images = array();
    public $xafc_images = array();
    public $stimuli_type = 'image'; // can be 'image', 'audio', or 'video'
    
    function get_name() { return $this->name; }

    function __construct($i) {
        $this->id = $i;
        
        if (is_numeric($i) && $i>0) {
            // get experiment info from mysql table
            $this_exp = new myQuery('SELECT * FROM exp WHERE id=' . $this->id);
            $info = $this_exp->get_assoc(0);
            
            // set experiment info
            $this->name = $info['name'];
            $this->exptype = ($info['exptype'] == '2afc') ? 'tafc' : $info['exptype'];
            $this->subtype = $info['subtype'];
            $this->design = $info['design'];
            $this->trial_order = $info['trial_order'];
            $this->side = $info['side'];
            $this->instructions = $info['instructions'];
            $this->question = $info['question'];
            $this->labels = array($info['label1'], $info['label2'], $info['label3'], $info['label4']);
            $this->range = $info['rating_range'];
            $this->anchors = array($info['low_anchor'], $info['high_anchor']);
            $this->orient = $info['orient'];
            $this->random_stim = $info['random_stim'];
            $this->default_time = $info['default_time'];
            $this->increment_time = $info['increment_time'];                
            
            // get trial info
            $trials_query = new myQuery('SELECT trial_n, t.name, 
                            REPLACE(REPLACE(l.path, "*SELF*", IF(self IS NULL, "control", self)), "*OTHER*", IF(other IS NULL, "control", other)) as left_image, 
                            REPLACE(REPLACE(c.path, "*SELF*", IF(self IS NULL, "control", self)), "*OTHER*", IF(other IS NULL, "control", other)) as center_image, 
                            REPLACE(REPLACE(r.path, "*SELF*", IF(self IS NULL, "control", self)), "*OTHER*", IF(other IS NULL, "control", other)) as right_image, 
                            GROUP_CONCAT(REPLACE(REPLACE(x.path, "*SELF*", IF(self IS NULL, "control", self)), "*OTHER*", IF(other IS NULL, "control", other)) ORDER BY n SEPARATOR ";") as xafc_images,
                            question, label1, label2, label3, label4, q_image,
                            l.type as ltype, c.type as ctype, r.type as rtype,  
                            GROUP_CONCAT(x.type ORDER BY n SEPARATOR ";") as xtype
                            FROM trial as t
                            LEFT JOIN xafc USING (exp_id, trial_n)
                            LEFT JOIN stimuli AS l ON (l.id=left_img)
                            LEFT JOIN stimuli AS c ON (c.id=center_img)
                            LEFT JOIN stimuli AS r ON (r.id=right_img)
                            LEFT JOIN stimuli AS x ON (x.id=xafc.image)
                            LEFT JOIN yoke ON (yoke.type="exp" AND t.exp_id=yoke.id AND yoke.user_id="' . $_SESSION['user_id'] . '")
                            WHERE t.exp_id=' . $this->id . ' GROUP BY trial_n');
            $trial_list = array();
            $types = array();
            foreach ($trials_query->get_assoc() as $trial) {
                $trial['xafc_images'] = explode(';', $trial['xafc_images']);
                
                // get types
                $types = array_merge($types, explode(';', $trial['xtype']));
                $types[] = $trial['ltype'];
                $types[] = $trial['ctype'];
                $types[] = $trial['rtype'];
                unset($trial['ltype']);
                unset($trial['ctype']);
                unset($trial['rtype']);
                unset($trial['xtype']);
                
                $trial_list[$trial['trial_n']] = new trial($trial);
            }
            
            $this->trials = $trial_list;
            
            // check if trials are images, audio or video and set stimuli_type
            $uniquetypes = array_filter(array_unique($types), 'strlen');
            $firsttype = array_shift($uniquetypes);
            if (in_array($firsttype, array('image', 'audio', 'video'))) {
                $this->stimuli_type = $firsttype;
            }
        }
    } 
    
    function get_experiment() {
        if ($this->exptype == '') {
            // experiment doesn't exist
            $text = 'Sorry, something has gone wrong. This experiment does not exist.';
        } else {
            $text  = $this->get_instructions();
            $text .= $this->get_interface();
            $text .= "<div id='recording' style='display:none;'>Saving data</div>" . ENDLINE;
            $text .= $this->get_javascript();
        }
        
        return $text;
    }
    
    function get_instructions() {
        $text = "<div class='instructions'>" . ENDLINE;
        //$text .= parsePara($this->instructions);
        $Parsedown = new Parsedown();
        $text .= $Parsedown->text($this->instructions);
        $text .= "</div>" . ENDLINE;
        
        // image loader
        foreach ($this->trials as $trial) {
            if ($trial->get_left_image() != '')     $this->left_images[]     = $trial->get_left_image();
            if ($trial->get_center_image() != '')     $this->center_images[]     = $trial->get_center_image();
            if ($trial->get_right_image() != '')     $this->right_images[]     = $trial->get_right_image();
            if ($trial->get_xafc_images() != '')    $this->xafc_images[]    = $trial->get_xafc_images();
        }
        $xafc_merged = (count($this->xafc_images)) ?
            call_user_func_array('array_merge', $this->xafc_images) :
            array();
        $stimuli = array_unique(array_merge($this->left_images, $this->center_images, $this->right_images, $xafc_merged));
        $stimuli = array_filter($stimuli, "strlen");
        
        $text .= "<div id='image_loader'>\n\t<div>Loading stimuli...</div>";
        //$text .= tag('Loading Stimuli...', 'div');
        $text .= "\n\t<span>" . count($stimuli) . "</span>\n\n";
        foreach ($stimuli as $stim) {
            if ($this->stimuli_type == 'image') {
                $text .= "\t<img src='$stim' />\n";
            } else if ($this->stimuli_type == 'audio') {
                $text .= "<audio preload='auto' />
                    <source src='{$stim}.mp3' type='audio/mp3' autoplay='false' />
                </audio>";
            } else if ($this->stimuli_type == 'video') {
                $text .= "<video preload='auto' />
                    <source src='{$stim}' type='video/mp4' autoplay='false' />
                </video>";
            }
        }
        $text .= "</div>\n\n";
        
        // continue button
        $text .= "<div id='continue_button' class='buttons' style='display:none;'>" . ENDLINE;
        if ($_SESSION['set_item_number'] > 0 || array_key_exists('project', $_SESSION)) {
            // remove option to escape during sets or projects
            $text .= "    <input type='button' id='beginExp' onclick='beginExp();' value='" . loc('Start') . "' />" . ENDLINE;
        } else if ($this->subtype == 'speeded') {
            $text .= "    <div>" . loc('Place your fingers on the keys<br>and press the space bar<br>to consent &amp; begin the experiment') . "</div>" . ENDLINE;
            $text .= "    <input type='button' onclick='noConsent();' value='" . loc('I Do Not Consent, Return to the Home Page') . "' />" . ENDLINE;
        } else {
            $text .= '<p>' . loc("Please indicate whether you consent to this experiment by clicking on the appropriate button below.") . '</p>';
            $text .= "    <input type='button' id='beginExp' onclick='beginExp();' value='" . loc('I Consent, Begin the Experiment') . "' />" . ENDLINE;
            $text .= "    <input type='button' onclick='noConsent();' value='" . loc('I Do Not Consent, Return to the Home Page') . "' />" . ENDLINE;
        }
        
        $text .= "</div>" . ENDLINE;
        
        return $text;
    }
    
    function get_interface() {
        $text = '<div id="experiment" style="display:none;">' . ENDLINE;
        $text .= tag($this->question, 'div', 'id="question"');
        $text .= '<a id="prev_trial" title="Previous Trial"><</a>' . ENDLINE;
        $text .= '<a id="next_trial" title="Next Trial">></a>' . ENDLINE;
        $text .= '<table class="' . $this->exptype . '">' . ENDLINE;
        $text .= $this->get_input_interface();
        $text .= $this->get_stimuli_interface();
        $text .= '</table>' . ENDLINE;
        //$text .= '<div class="trialcounter">Trial <span id="trial_n">0</span> of ' . $this->random_stim . '</div>' . ENDLINE;
        $text .= '</div>' . ENDLINE;
        
        return $text;
    }
    
    function get_input_interface() {
        // input interface blank for xafc, tafc, motivation, slideshow
        return '';
    }
    
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images"><td>DEFAULT STIMULUS INTERFACE HERE!</td></tr>' . ENDLINE;
        
        return $text;
    }
    
    function get_javascript() {
        $text =         '<script>' . ENDLINE;
        $text .=        '    window.onbeforeunload = function() {return "Using the back button will reset this experiment"; }' . ENDLINE;
        
        $text .=        '   $("#next_trial, #prev_trial").button();' . ENDLINE;
        
        if (array_key_exists('go', $_GET)) { 
            $text .=     '    $(function() { beginExp(); });' . ENDTAG; 
        }
        
        $text .=        '   $("#beginExp").button({ showLabel: false, icon: "ui-icon-play"});' . ENDTAG;

        // get image loader
        if ($this->stimuli_type == 'image' && !array_key_exists('go', $_GET) && count($this->trials) > 0) {
            $text .=     '    $("#image_loader img").one("load", function() {' . ENDLINE .
                        '        console.log($(this).attr("src") + " loaded");' . ENDLINE .
                        '        var ils = $("#image_loader span");' . ENDLINE .
                        '        var n = parseInt(ils.text());' . ENDLINE .
                        '        if (n - 1 <= 0) {' . ENDLINE .
                        '            $("#image_loader").hide();' . ENDLINE .
                        '            $("#continue_button").show();' . ENDLINE .
                        '        } else {' . ENDLINE .
                        '            ils.text(n-1);' . ENDLINE .
                        '        }' . ENDLINE .
                        '    }).each(function() {' . ENDLINE .
                        '        if(this.complete) $(this).load();' . ENDLINE .
                        '      });' . ENDTAG;
        } else {
            $text .=    '    $("#image_loader").hide();' . ENDLINE .
                        '    $("#continue_button").show();' . ENDTAG ;
        }
        
        $text .=        '    function beginExp() {' . ENDLINE .
                        '        console.log("beginExp()");'. ENDLINE .
                        '        trial = 0;' . ENDLINE . // added to stop skipping first trial if the begin button is pushed twice
                        '        $("div.instructions").hide();' . ENDLINE .
                        '        $("#continue_button").hide();' . ENDLINE .
                        '        $("#experiment").show();' . ENDLINE .
                        '        $("#recording").hide();' . ENDLINE .
                        '        $("#header").hide();' . ENDLINE .
                        '        maxstimsize();' . ENDLINE .
                        '        nextTrial(0);' . ENDLINE .
                        '    }' . ENDTAG .
                        
                        '    function noConsent() {' . ENDLINE .
                        '        window.onbeforeunload = function() {};' . ENDLINE .
                        '        window.location.href="/";' . ENDLINE .
                        '    }' . ENDTAG;

        // audio-specific functions
        if ($this->stimuli_type == 'audio') {
            $text .=    '    var playing = false;' . ENDLINE .
                        '    var theAudioContainer = null;' . ENDTAG .
    
                        '    $("#experiment .audio span.play").click( function() {' . ENDLINE .
                        '        if (playing == false) {' . ENDLINE .
                        '            playing = true;' . ENDLINE .
                        '            theAudioContainer = $(this).closest(".audio");' . ENDLINE .
                        '            theAudioContainer.addClass("playing");' . ENDLINE .
                        '            $(this).text("PLAYING");' . ENDLINE .
                        '            var thePlayer = document.getElementById( theAudioContainer.attr("id") + "_player" );' . ENDLINE .
                        '            thePlayer.play();' . ENDLINE .
                        '        }' . ENDLINE .
                        '    });' . ENDTAG .
    
                        '    $("#experiment audio").bind("ended", function() {' . ENDLINE .
                        '        playing=false; ' . ENDLINE .
                        '        $(this).closest("div.audio")' . ENDLINE . 
                        '                .removeClass("playing unplayed")' . ENDLINE . 
                        '                .addClass("played").find("span.play").text("PLAYED");' . ENDLINE .
        
                        '        // show choose if all played' . ENDLINE .
                        '        if ($("#experiment .audio.unplayed").length == 0) {' . ENDLINE .
                        '            $("#experiment .audio span.choose").show();' . ENDLINE .
                        '        }' . ENDLINE .
                        '    });' . ENDTAG;
        }
        
        // video-specific functions
        if ($this->stimuli_type == 'video') {
            $text .=    '    var playing = false;' . ENDLINE .
                        '    var theVideoContainer = null;' . ENDTAG .
    
                        '    $("#experiment video").click( function() {' . ENDLINE .
                        '        if (playing == false) {' . ENDLINE .
                        '            playing = true;' . ENDLINE .
                        '            console.log("playing:",$(this).find("source").attr("src"));' . ENDLINE .
                        '            theVideoContainer = $(this).closest(".video");' . ENDLINE .
                        '            theVideoContainer.addClass("playing");' . ENDLINE .
                        '            var thePlayer = document.getElementById( theVideoContainer.attr("id") + "_vplayer" );' . ENDLINE .
                        '            thePlayer.play();' . ENDLINE .
                        '        }' . ENDLINE .
                        '    });' . ENDTAG .
    
                        '    $("#experiment video").on("ended", function() {' . ENDLINE .
                        '        playing=false; ' . ENDLINE .
                        '        console.log("ended:",$(this).find("source").attr("src"));' . ENDLINE .
                        '        $(this).closest("div.video")' . ENDLINE . 
                        '                .removeClass("playing unplayed")' . ENDLINE . 
                        '                .addClass("played");' . ENDLINE .
        
                        '        // show choose if all played' . ENDLINE .
                        '        if ($("#experiment .video.unplayed").length == 0) {' . ENDLINE .
                        '            $("#experiment .video span.choose").show();' . ENDLINE .
                        '        }' . ENDLINE .
                        '    });' . ENDTAG;
        }
        
        // trial list and randomisation list
        $text .=         '    // trial attributes list' . ENDLINE;
        $text .=         '    trial = 0;' . ENDLINE;
        $text .=         '    response = [0];' . ENDLINE;
        $text .=         '    rt = [0];' . ENDLINE;
        $text .=         '    var ct = new Date();' . ENDLINE;
        $text .=         '    starttime = "' . date('Y-m-d H:i:s') . '";' . ENDLINE;
        $text .=         '    beginTrial = 0;' . ENDLINE;


        // randomise trial order and select random_stim if applicable
        $trial_n = array();
        foreach ($this->trials as $n => $trial) { $trial_n[$n] = $trial->get_trial_n(); }
        if ($this->trial_order == 'random') { shuffle($trial_n); } elseif ($this->trial_order == 'norepeat') { shuffle($trial_n); }
        if ($this->random_stim < count($trial_n)) { // choose a subset of the trials
            shuffle($trial_n); // shuffle again in case trial order is accidentally set to not shuffle (which would make no sense)
            $trial_n = array_slice($trial_n, 0, $this->random_stim);
        }
        $text .=         '    trialOrder = [0,'     . implode(',', $trial_n) . '];' . ENDLINE;
        
        // randomise side if applicable
        $text .= $this->side();
        
        // other specific attributes
        $trial_att = $this->get_trial_att();
        foreach ($trial_att as $att => $values) {
            $unique_values = array_unique($values);
            if (implode('', $unique_values) == '') {
                // array is  empty, delete attribute
                unset($trial_att[$att]);
            } else {
                // array is not empty, add values
                if (is_array($values[1])) {
                    foreach ($values as $n => $v) {
                        $values[$n] = "['" . implode("',\n\t\t\t'", $v) . "']";
                    }
                    $text .= "    $att = ['',\n\t\t"     . implode(",\n\t\t", $values)     . "\n\t];" . ENDLINE;
                } else {
                    $text .= "    $att = ['',\n\t\t'"     . implode("',\n\t\t'", $values)     . "'\n\t];" . ENDLINE;
                }
            }
        }
        
        // next trial function
        $text .=           '    function nextTrial(r) {' . ENDLINE .
                           '        console.log("nextTrial(" + r + ")");'. ENDLINE .
                           '        $(".input_interface input").removeClass("ui-state-active").removeClass("ui-state-hover");' . ENDLINE;
        
        if ($this->stimuli_type == 'audio') {
            $text .=       '        if ($("#experiment .audio.unplayed").length > 0) {' . ENDLINE .
                           '            alert("You must listen to all sounds before continuing.");' . ENDLINE .
                           '            return false;' . ENDLINE .
                           '        }' . ENDTAG;
        } else if ($this->stimuli_type == 'video') {
            $text .=       '        if ($("#experiment .video.unplayed").length > 0) {' . ENDLINE .
                           '            alert("You must view all videos before continuing. Click on a video to view it.");' . ENDLINE .
                           '            return false;' . ENDLINE .
                           '        }' . ENDTAG;
        }
        
        $text .=           '        var currentTime = new Date();' . ENDLINE .
                           '        var endTrial = currentTime.getTime();' . ENDLINE .
                           '        if (trial > 0) {' . ENDLINE .
                           '            ' . $this->record_trial_info() . ENDLINE .
                           '            rt[trialOrder[trial]] = endTrial - beginTrial;' . ENDLINE. 
                           '            trial_submit();' . ENDLINE .
                           '        }' . ENDTAG .
                           '        trial++;' . ENDLINE .
                           '        $("#rating").val("").focus();' . ENDTAG .
        
                           '        if (trial == trialOrder.length) {' . ENDLINE .
                           '            // hide experiment interface' . ENDLINE .
                           '            $("#experiment").hide();' . ENDLINE .
                           '            $("#recording").show();' . ENDLINE .
                           '            $("#header").show();' . ENDLINE .
                           '        } else {' . ENDLINE .
                           '            ' . $this->set_up_next_trial() . ENDLINE .
                           '        }' . ENDLINE .
                           '    }' . ENDTAG;
                           
        $text .=           '    function trial_submit() {' . ENDLINE .
                           '        // record to database' . ENDLINE .    
                           '        var d = {' . ENDLINE .
                           '            id: ' . $this->id . ',' . ENDLINE .
                           '            trial: trialOrder[trial],' . ENDLINE .
                           '            response: response[trialOrder[trial]],' . ENDLINE .
                           '            side: side[trialOrder[trial]],' . ENDLINE .
                           '            rt: rt[trialOrder[trial]],' . ENDLINE .
                           '            order: trial,' . ENDLINE .
                           '            exptype: "' . $this->exptype . '",' . ENDLINE .
                           '            starttime: starttime' . ENDLINE .
                           '        };' . ENDTAG .
                            
                           '        if (trial == trialOrder.length - 1) {' . ENDLINE .
                           '            d.done = true;' . ENDLINE . 
                           '        }' . ENDLINE .
                           
                           '        $.ajax({' . ENDLINE .
                           '            type: "POST",' . ENDLINE .
                           '            async: true,' . ENDLINE .
                           '            url: "/include/scripts/record_exp", ' . ENDLINE .
                           '            data: d,' . ENDLINE .
                           '            success: function(r) {' . ENDLINE .
                           '                // send to feedback page' . ENDLINE .
                           '                if (r.substr(0,1) == "/") {' . ENDLINE .
                           '                    window.onbeforeunload = function() {};' . ENDLINE .
                           '                    window.location.href=r;' . ENDLINE .
                           '                }' . ENDLINE .
                           '            }' . ENDLINE .
                           '        });' . ENDLINE .
                           '    }' . ENDTAG;

        // specific functions for this experiment type
        $text .= $this->get_javascript_specific();
        $text .=         '</script>'; 
        
        return $text;
    }
    
    function record_trial_info() {
        // default for all but tafc, sort, jnd
        return             '    response[trialOrder[trial]] = r;' . ENDLINE;
    }
    
    function side() {
        // side randomisation (default for all but xafc)
        
        $total = count($this->trials); // $this->random_stim;
        if ($this->side == 'random') {        
            for ($i = 0; $i < $total; $i++) {
                $side[] = rand(1,2);
            }
        } else {
            $side = array_fill(0, $total, 1);
        }
        
        return             '    side = [0,'     . implode(",", $side) . '];' . ENDLINE;
    }
    
    function get_trial_att() {
        // default for tafc, buttons, rating
        $trial_att = array();
        
        foreach ($this->trials as $n => $trial) {
            $trial_att['left_image'][$n] = $trial->get_left_image();
            $trial_att['center_image'][$n] = $trial->get_center_image();
            $trial_att['right_image'][$n] = $trial->get_right_image();
            $trial_att['question'][$n] = $trial->get_question();
        }
        
        return $trial_att;
    }
    
    function set_up_next_trial() {
        $text = '// set up next trial' . ENDLINE;
        if ($this->stimuli_type == 'image') {
            $text .= '
                if ($("#center_image").length > 0) $("#center_image").attr("src", center_image[trialOrder[trial]]);
                if (side[trialOrder[trial]] == 1) {
                    if ($("#left_image").length > 0) $("#left_image").attr("src", left_image[trialOrder[trial]]);
                    if ($("#right_image").length > 0) $("#right_image").attr("src", right_image[trialOrder[trial]]);
                } else {
                    if ($("#left_image").length > 0) $("#left_image").attr("src", right_image[trialOrder[trial]]);
                    if ($("#right_image").length > 0) $("#right_image").attr("src", left_image[trialOrder[trial]]);
                }' . ENDLINE;
        }  else if ($this->stimuli_type == 'audio') {
            $text .= '
                if ($("#center_image").length > 0) {
                    $("#center_image_player").html("")
                        .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                }
                if (side[trialOrder[trial]] == 1) {
                    if ($("#left_image").length > 0) {
                        $("#left_image_player").html("")
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_player").html("")
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                } else {
                    if ($("#left_image").length > 0) {
                        $("#left_image_player").html("")
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_player").html("")
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                }
                $(".audio").removeClass("played").removeClass("playing").addClass("unplayed").find("span.play").text("PLAY");
                $("audio").load();
                $("#experiment .audio span.choose").hide();
                playing = false;
            ' . ENDLINE;
        }  else if ($this->stimuli_type == 'video') {
            $text .= '
                $("video").hide().get(0).pause();
                if ($("#center_image").length > 0) {
                    $("#center_image_vplayer source").attr("src", center_image[trialOrder[trial]]);
                    console.log("loading:", center_image[trialOrder[trial]]);
                }
                if (side[trialOrder[trial]] == 1) {
                    if ($("#left_image").length > 0) {
                        $("#left_image_vplayer source").attr("src", left_image[trialOrder[trial]]);
                        console.log("loading:", left_image[trialOrder[trial]]);
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_vplayer source").attr("src", right_image[trialOrder[trial]]);
                        console.log("loading:", right_image[trialOrder[trial]]);
                    }
                } else {
                    if ($("#left_image").length > 0) {
                        $("#left_image_vplayer source").attr("src", right_image[trialOrder[trial]]);
                        console.log("loading:",right_image[trialOrder[trial]]);
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_vplayer source").attr("src", left_image[trialOrder[trial]]);
                        console.log("loading:", left_image[trialOrder[trial]]);
                    }
                }
                $(".video").removeClass("played").removeClass("playing").addClass("unplayed")
                $("video").show().each(function() {
                    $(this).get(0).load();
                });
                $("#experiment .video span.choose").hide();
                playing = false;
            ' . ENDLINE;
        }
        
        $text .= '
            if ($("#question").length > 0 && typeof(question) !== "undefined") $("#question").html(question[trialOrder[trial]]);
            $("#trial_n").html(trial);
            $("#footer").text("Trial "+ trial +" of " + (trialOrder.length-1));
            
            var currentTime = new Date();
            beginTrial = currentTime.getTime();
            return false;' . ENDLINE;
        
        return $text;
    }
    
    function get_javascript_specific() {
        // placeholder for functions specific to an experiment type
        $text = '    console.log("no specific javascript");' . ENDLINE;
        return $text;
    }
}

?>