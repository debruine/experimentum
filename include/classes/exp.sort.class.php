<?php
    
/**************************************************************************
 * Experimentum Experiment Classes
 *
 * PHP version 7
 *
 * @author     Lisa DeBruine <debruine@gmail.com>
 * @copyright  2018
 *************************************************************************/
 
 require_once 'exp.xafc.class.php';
    
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

?>