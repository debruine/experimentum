<?php

/****************************************************
 * Experiment classes
 ***************************************************/
 
 require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/Parsedown.php';
 
 class expChooser {
    public $id;
    public $exptype;
    public $subtype;
    public $version;
    public $exp = false;
    
     function __construct($i, $v=0) {
        $this->id = $i;
        $this->version = $v;
        
        if (is_numeric($i) && $i>0) {
            // get experiment info from mysql table
            $this_exp = new myQuery('SELECT exptype, subtype FROM exp WHERE id=' . $this->id);
            $info = $this_exp->get_assoc(0);
            
            $this->exptype = ($info['exptype'] == '2afc') ? 'tafc' : $info['exptype'];    // '2afc','jnd','rating','buttons','xafc','sort','nback','interactive','motivation','adaptation','other'
            $this->subtype = $info['subtype'];    // 'standard','adapt','speeded','adapt_nopre','large_n'

            switch ($this->exptype) {
                case 'xafc': 
                    $this->exp = new exp_xafc($i, $v); 
                    break;
                case 'tafc': 
                    $this->exp = new exp_tafc($i, $v); 
                    break;
                case 'sort':
                    $this->exp = new exp_sort($i, $v); 
                    break;
                case 'motivation':
                    $this->exp = new exp_motivation($i, $v); 
                    break;
                case 'jnd':
                    $this->exp = new exp_jnd($i, $v);
                    break;
                case 'buttons':
                    $this->exp = new exp_buttons($i, $v); 
                    break;
                case 'rating':
                    $this->exp = new exp_rating($i, $v); 
                    break;
                case 'adaptation':
                    $this->exp = new exp_adaptation($i, $v); 
                    break;
            }
        }
    }
    
    function check_exists() {
        if ($this->exptype == '') { return false; }
        return true;
    }
    
    function check_eligible() {
        $user_sex = $_SESSION['sex'];
        $user_age = $_SESSION['age'];
        
        $query = new myQuery('SELECT lower_age, upper_age, sex FROM exp WHERE id=' . $this->id);
        $expinfo = $query->get_one_array();
        
        $eligible = true;
        
        $eligible = $eligible && (is_null($expinfo['lower_age']) || $user_age >= $expinfo['lower_age']);
        $eligible = $eligible && (is_null($expinfo['upper_age']) || $user_age <= $expinfo['upper_age']);
        $eligible = $eligible && (is_null($expinfo['sex']) || $expinfo['sex'] == 'both' || $user_sex == $expinfo['sex']);
        
        // yoking check (only bother if still eligible)
        if ($eligible) {
            $query = new myQuery('SELECT l.path, c.path, r.path 
                                  FROM trial 
                                  LEFT JOIN stimuli AS l on l.id=left_img
                                  LEFT JOIN stimuli AS c on c.id=center_img
                                  LEFT JOIN stimuli AS r on r.id=right_img 
                                  WHERE exp_id=' . $this->id .' AND (
                                   LOCATE("*SELF*", l.path)
                                   OR LOCATE("*SELF*", c.path)
                                   OR LOCATE("*SELF*", r.path)
                                   OR LOCATE("*OTHER*", l.path)
                                   OR LOCATE("*OTHER*", c.path)
                                   OR LOCATE("*OTHER*", r.path)
                                  )');
            if ($query->get_num_rows() > 0) {
                // experiment needs a yoke table entry for eligibility
                $query = new myQuery('SELECT * FROM yoke WHERE user_id="' . intval($_SESSION['user_id']) . '" AND type="exp" AND id=' . $this->id);
                $eligible = $eligible && ($query->get_num_rows() == 1); 
            }
        }
        
        return $eligible;
    }
    
    function get_exp() {
        return $this->exp;
    }
 }

class experiment {
    public $id;
    public $name;
    public $exptype;
    public $subtype;
    public $version;
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

    function __construct($i, $v=0) {
        $this->id = $i;
        $this->version = $v;
        
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
        $text = "<div id='instructions'>" . ENDLINE;
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
                    <source src='{$stim}.ogg' type='audio/ogg' autoplay='false' />
                    <source src='{$stim}.mp3' type='audio/mp3' autoplay='false' />
                </audio>";
            } else if ($this->stimuli_type == 'video') {
                $text .= "<video preload='auto' />
                    <source src='{$stim}.ogv' type='video/ogg' autoplay='false' />
                    <source src='{$stim}.m4v' type='video/mp4' autoplay='false' />
                </video>";
            }
        }
        $text .= "</div>\n\n";
        
        // send to slideshow if adapt_nopre
        if ($this->subtype == 'adapt_nopre' && $this->version == 0) {
            $versionq = new myQuery('SELECT MAX(version) FROM versions WHERE exp_id=' . $this->id);
            $maxversion = $versionq->get_one();
            if ($this->design == 'within') {
                $all_versions = range(1, $maxversion);
                shuffle($all_versions);
                $_SESSION['within_adapt'] = $all_versions;
                $_SESSION['within_adapt_number'] = 0;
                $version = $all_versions[0];
            } else {
                $version = rand(1, $maxversion);
            }
        
            //header('Location: /slideshow?id=' . $this->id . '&v=' . $version); 
            //exit;
            $adapt_nopre = '/slideshow?id=' . $this->id . '&v=' . $version;
        }
        
        // continue button
        $text .= "<div id='continue_button' class='buttons' style='display:none;'>" . ENDLINE;
        if ($_SESSION['set_item_number'] > 0 || array_key_exists('project', $_SESSION)) {
            // remove option to escape during sets or projects
            if (!empty($adapt_nopre)) {
                $text .= "    <input type='button' onclick='window.location=\"" . $adapt_nopre . "\";' value='" . loc('Begin the Experiment') . "' />" . ENDLINE;
            } else {
                $text .= "    <input type='button' onclick='beginExp();' value='" . loc('Begin the Experiment') . "' />" . ENDLINE;
            }
        } else if ($this->subtype == 'speeded') {
            $text .= "    <div>" . loc('Place your fingers on the keys<br>and press the space bar<br>to consent &amp; begin the experiment') . "</div>" . ENDLINE;
            $text .= "    <input type='button' onclick='noConsent();' value='" . loc('I Do Not Consent, Return to the Home Page') . "' />" . ENDLINE;
        } else {
            $text .= '<p>' . loc("Please indicate whether you consent to this experiment by clicking on the appropriate button below.") . '</p>';
            if (!empty($adapt_nopre)) {
                $text .= "    <input type='button' onclick='window.location=\"" . $adapt_nopre . "\";' value='" . loc('I Consent, Begin the Experiment') . "' />" . ENDLINE;
            } else {
                $text .= "    <input type='button' onclick='beginExp();' value='" . loc('I Consent, Begin the Experiment') . "' />" . ENDLINE;
            }
            $text .= "    <input type='button' onclick='noConsent();' value='" . loc('I Do Not Consent, Return to the Home Page') . "' />" . ENDLINE;
        }
        
        $text .= "</div>" . ENDLINE;
        
        return $text;
    }
    
    function get_interface() {
        $text = '<div id="experiment" style="display:none;">' . ENDLINE;
        $text .= tag($this->question, 'div', 'id="question"');
        $text .= '<table class="' . $this->exptype . '">' . ENDLINE;
        $text .= $this->get_input_interface();
        $text .= $this->get_stimuli_interface();
        $text .= '</table>' . ENDLINE;
        $text .= '<div class="trialcounter">Trial <span id="trial_n">0</span> of ' . $this->random_stim . '</div>' . ENDLINE;
        $text .= '</div>' . ENDLINE;
        
        return $text;
    }
    
    function get_input_interface() {
        // input interface blank for xafc, tafc, motivation
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
        
        if (array_key_exists('go', $_GET)) { 
            $text .=     '    $(function() { beginExp(); });' . ENDTAG; 
        }

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
            $text .=     '    $("#image_loader").hide();' . ENDLINE .
                        '    $("#continue_button").show();' . ENDTAG ;
        }
        
        $text .=         '    function beginExp() {' . ENDLINE .
                        '        console.log("beginExp()");'. ENDLINE .
                        '        trial = 0;' . ENDLINE . // added to stop skipping first trial if the begin button is pushed twice
                        '        $("#instructions").hide();' . ENDLINE .
                        '        $("#continue_button").hide();' . ENDLINE .
                        '        $("#experiment").show();' . ENDLINE .
                        '        $("#recording").hide();' . ENDLINE .
                        '        $("#header").hide();' . ENDLINE .
                        '        nextTrial(0);' . ENDLINE .
                        '    }' . ENDTAG .
                        
                        '    function noConsent() {' . ENDLINE .
                        '        window.location.href="/";' . ENDLINE .
                        '    }' . ENDTAG;

        // audio-specific functions
        if ($this->stimuli_type == 'audio') {
            $text .=     '    var playing = false;' . ENDLINE .
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
                           '            alert("You must listen to all sounds before continuing");' . ENDLINE .
                           '            return false;' . ENDLINE .
                           '        }' . ENDTAG;
        }
        
        $text .=           '        var currentTime = new Date();' . ENDLINE .
                           '        var endTrial = currentTime.getTime();' . ENDLINE .
                           '        if (trial > 0) {' . ENDLINE .
                           '             ' . $this->record_trial_info() . ENDLINE .
                           '            rt[trialOrder[trial]] = endTrial - beginTrial;' . ENDLINE;
                           
        if ($this->subtype == "large_n") {
            $text .=       '            large_n_submit();' . ENDLINE;
        }
                           
        $text .=           '        }' . ENDTAG .

                           '        trial++;' . ENDLINE .
                           '        $("#rating").val("").focus();' . ENDTAG .
        
                           '        if (trial == trialOrder.length) {' . ENDLINE .
                           '            // hide experiment interface' . ENDLINE .
                           '            $("#experiment").hide();' . ENDLINE .
                           '            $("#recording").show();' . ENDLINE .
                           '            $("#header").show();' . ENDLINE;
                           
        if ($this->subtype != "large_n") {                                              
            $text .=    '            // record to database' . ENDLINE .    
                           '            var d = {' . ENDLINE .
                           '                id: ' . $this->id . ',' . ENDLINE .
                           '                version: "' . $this->version . '",' . ENDLINE .
                           '                response: response,' . ENDLINE .
                           '                side: side,' . ENDLINE .
                           '                rt: rt,' . ENDLINE .
                           '                order: trialOrder,' . ENDLINE .
                           '                exptype: "' . $this->exptype . '",' . ENDLINE .
                           '                starttime: starttime' . ENDLINE .
                           '            };' . ENDTAG .
                
                           '            $.ajax({' . ENDLINE .
                           '                type: "POST",' . ENDLINE .
                           '                async: false,' . ENDLINE .
                           '                url: "/include/scripts/record_exp2", ' . ENDLINE .
                           '                data: d,' . ENDLINE .
                           '                success: function(r) {' . ENDLINE .
                           '                    // send to feedback page' . ENDLINE .
                           '                    if (r.substr(0,1) != "/") {' . ENDLINE .
                           '                        alert(r);' . ENDLINE .
                           '                    } else {' . ENDLINE .
                           '                        window.location.href=r;' . ENDLINE .
                           '                    }' . ENDLINE .
                           '                }' . ENDLINE .
                           '            });' . ENDLINE;
        }
        
        $text .=           '        } else {' . ENDLINE .
                        '            ' . $this->set_up_next_trial() . ENDLINE .
                           '        }' . ENDLINE .
                           '    }' . ENDTAG;
                           
        // incremental submit function for large_n subtype
        if ($this->subtype == "large_n") {
            $text .=     '    function large_n_submit() {' . ENDLINE .
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
                           '                    window.location.href=r;' . ENDLINE .
                           '                }' . ENDLINE .
                           '            }' . ENDLINE .
                           '        });' . ENDLINE .
                           '    }' . ENDTAG;
        }

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
            for ($i = 0; $i < $this->random_stim; $i++) {
                $side[] = rand(1,2);
            }
        } else {
            $side = array_fill(0, $this->random_stim, 1);
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
                        .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                        .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                }
                if (side[trialOrder[trial]] == 1) {
                    if ($("#left_image").length > 0) {
                        $("#left_image_player").html("")
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_player").html("")
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                } else {
                    if ($("#left_image").length > 0) {
                        $("#left_image_player").html("")
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                            .append( $("<source />").attr("src", right_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                    if ($("#right_image").length > 0) {
                        $("#right_image_player").html("")
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                            .append( $("<source />").attr("src", left_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
                    }
                }
                $(".audio").removeClass("played").removeClass("playing").addClass("unplayed").find("span.play").text("PLAY");
                $("audio").load();
                $("#experiment .audio span.choose").hide();
                playing = false;
            ' . ENDLINE;
        }
        
        $text .= '
            if ($("#question").length > 0 && typeof(question) !== "undefined") $("#question").html(question[trialOrder[trial]]);
            $("#trial_n").html(trial);
            
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

class exp_sort extends exp_xafc {
    function get_input_interface() {
        $text = '';
        
        $cols_to_span = (empty($this->left_images) ? 0 : 1) + (empty($this->center_images) ? 0 : 1) + (empty($this->right_images) ? 0 : 1);
        ifEmpty($cols_to_span, 1);
    
        // input interface for sort
        $text .= '    <tr class="input_interface"><td>' . ENDLINE;
        $text .= '        <input type="button" onclick="nextTrial(null)" value="Next" />' . ENDLINE;
        
        $image_count = count($this->xafc_images[0]);
        for ($n = 1; $n <= $image_count; $n++) {
            $text .= "            <input type='hidden' class= 'sort_dv' id='hidden_sort_$n' value='' />" . ENDLINE;
        }
        $text .= "            <input type='hidden' id='n_moves' value='0' />" . ENDLINE;
        
        $text .= '    </td></tr>' . ENDLINE;
                        
        return $text;
    }
    
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
        $text .= '        <td id="sortable_images">' . ENDLINE;
        for ($n = 1; $n <= $image_count; $n++) {
            if ($this->stimuli_type == 'image') {
                $text .= "            <img src='' id='sort_$n' style='max-width: $max_width%;' />" . ENDLINE;
            }
        }
        $text .= '        </td>' . ENDLINE;

        $text .= '    </tr>' . ENDLINE;
        
        return $text;
    }
    
    function record_trial_info() {
        return '
            var resp = [];
            $("input.sort_dv").each( function(i) {
                resp.push($(this).val());
            });
            response[trialOrder[trial]] = $("#n_moves").val() + ":" + resp.join(";");
        ';
    }
    
    function set_up_next_trial() {
        return '// set up next trial
        
            var xafc_count = $(".sort img").length;
            var $stimuli_container = $("#sortable_images");
            for (var n=0; n<xafc_count; n++) {
                $("#sort_" + side[trialOrder[trial]][n])
                    .attr("src", xafc_images[trialOrder[trial]][n])
                    .attr("dv", n+1);
                
                // resort order    
                $("#sort_" + (n+1)).appendTo($stimuli_container);
                    
                $("#hidden_sort_" + (n+1))
                    .val(side[trialOrder[trial]][n]);
            }
            $("#n_moves").val(0); // reset number of moves
            
            if ($("#question").length > 0 && typeof(question) !== "undefined") $("#question").html(question[trialOrder[trial]]);
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
        }
        
        return $trial_att;
    }
    
    function get_javascript_specific() {
        $text = '    console.log("sort-specific javascript");' . ENDLINE;
        $text .= '
    $(".input_interface input[type=button]").button();
    $("#sortable_images").sortable({
        placeholder: "sort_placeholder",
        forcePlaceholderSizeType: true,
        update: function() { 
            $("#sortable_images img").each( function(i) {
                var dv = $(this).attr("dv");
                $("#hidden_sort_" + dv).val((i+1));
            });
            $("#n_moves").val(parseInt($("#n_moves").val()) + 1);
            
        } 
    }).disableSelection();' . ENDLINE;
    
        return $text;
    }
}

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
        }  else if ($this->stimuli_type == 'audio') {
                $text .= '
            $("#center_image_player").html("")
                .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".ogg").attr("type", "audio/ogg"))
                .append( $("<source />").attr("src", center_image[trialOrder[trial]] + ".mp3").attr("type", "audio/mp3"));
            
            $(".audio").removeClass("played").removeClass("playing").addClass("unplayed").find("span.play").text("PLAY");
            $("audio").load();
            $("#experiment .audio span.choose").hide();
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
        
        $text .= "            <input type='text' 
                                style='width: {$w}em' 
                                class='rating' 
                                name='rating' 
                                id='rating' 
                                value='' 
                                autocomplete='off'
                                maxlength='$maxlength'
                                onkeypress='ratingKeyPress(this, event, 1, " . $this->range . ")' />" . ENDLINE;
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

class exp_adaptation extends exp_buttons {
    function get_stimuli_interface() {
        $text = '';
        
        // image display interface
        $text .= '    <tr class="exp_images"><td></td><td id="adimg"><img id="adapt_img" src=""><img id="test_img" src="/images/stimuli/fixation"></td><td></td></tr>' . ENDLINE;
        
        return $text;
    }

    function get_javascript_specific() {
        $text =         '    console.log("adaptation-specific javascript");' . ENDLINE;
        
        $text .=         '    var wait_time = 100; // brief wait to change image so cached images do not flicker' . ENDLINE .
                        '    var adapt_time = 1000;' . ENDLINE .
                        '    var isi = 250;' . ENDLINE .
                        '    var test_time = 400;' . ENDLINE .
                        '    var running = false;' . ENDLINE .
                        '    var data = [];' . ENDLINE;
        
        $text .=         '    $("body").keyup(function(e) {' . ENDLINE .
                        '        if (e.keyCode == 32 && !running && $(".input_interface").hasClass("faded")) {' . ENDLINE .
                        '            trial++;' . ENDLINE .
                        '            $("#trial_n").text(trial);' . ENDLINE .
                        '            running = true;' . ENDLINE .         
                        '            $("#adapt_img").one("load", function() {' . ENDLINE .
                        '                $("#test_img").one("load", function() {' . ENDLINE .
                        '                    $("#question").text("What emotion does the second face show?");' . ENDLINE .
                        '                    setTimeout("\$(\'#adapt_img\').show();", wait_time);' . ENDLINE .        
                        '                    // after adapt_time, hide the adapt image and set the  test image' . ENDLINE .
                        '                    setTimeout("\$(\'#adapt_img\').hide();", wait_time + adapt_time);' . ENDLINE .            
                        '                    // after isi, show the test image' . ENDLINE .
                        '                    setTimeout("\$(\'#test_img\').show();", wait_time + adapt_time + isi);' . ENDLINE .            
                        '                    // after test_time, hide the test image and show the response interface' . ENDLINE .
                        '                    setTimeout("\$(\'#test_img\').hide(); \$(\'.input_interface\').removeClass(\'faded\'); running = false;", wait_time + adapt_time + isi + test_time);' . ENDLINE .
                        '                }).each(function() {' . ENDLINE .
                        '                    if (this.complete) $(this).trigger("load");' . ENDLINE .
                        '                }).attr("src", test_images[trial]);' . ENDLINE .
                        '            }).each(function() {' . ENDLINE .
                        '                if (this.complete) $(this).trigger("load");' . ENDLINE .
                        '            }).attr("src", adapt_images[trial]);' . ENDLINE .
                        '        }' . ENDLINE .
                        '    });' . ENDLINE;
                
        $text .=         '    function nextTrial(dv) {' . ENDLINE .
                        '        if (running == false) {' . ENDLINE .
                        '            // TODO: make buttons unclickable and faded ' . ENDLINE .
                        '            $(".input_interface").addClass("faded");' . ENDLINE .
                        '            $("#question").text("Press the spacebar to start the next trial");' . ENDLINE .

                        '            // record info' . ENDLINE .
                        '            data[trial] = {' . ENDLINE .
                        '                "trial": trial,' . ENDLINE .
                        '                "adapt": adapt_images[trial],' . ENDLINE .
                        '                "test": test_images[trial],' . ENDLINE .
                        '                "dv": dv' . ENDLINE .
                        '            };' . ENDLINE .
            
                        '            if (data.length == adapt_images.length) {' . ENDLINE .
                        '                // record to database' . ENDLINE .
                        '                var url =     "/include/scripts/record_exp2?id=' . $this->id . '" +' . ENDLINE .
                        '                            "&version=' . $this->version . '" + ' . ENDLINE .
                        '                            "&response=" + escape(response) + ' . ENDLINE .
                        '                            "&side=" + side + ' . ENDLINE .
                        '                            "&rt=" + rt + ' . ENDLINE .
                        '                            "&order=" + trialOrder +' . ENDLINE .
                        '                            "&exptype=' . $this->exptype . '" +' . ENDLINE .
                        '                            "&starttime=" + escape(starttime);' . ENDLINE .
            
                        '                $.get(url, function(r) {' . ENDLINE .
                        '                    // send to feedback page' . ENDLINE .
                        '                    window.location.href="/fb?type=exp&id=' . $this->id . '";' . ENDLINE .
                        '                });' . ENDLINE .
                        '            }' . ENDLINE .
                        '        }' . ENDLINE .
                        '    };' . ENDLINE;
        
        return $text;
    }    
}

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
}

 
?>