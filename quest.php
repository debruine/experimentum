<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(1);


/****************************************************
 * AJAX Responses
 ***************************************************/

if (array_key_exists('submit', $_GET)) {
    // save data and return nextpage
    $clean = my_clean($_POST);
    
    $questions = array();
    $answers = array();
    foreach ($clean as $q => $a) {
        if (substr($q, 0, 1) == 'q' && is_numeric(substr($q, 1))) {
            $questions[] = $q;
            $answers[] = '"' . $a . '"';
        }
    }
    
    // record data
    /*
    $q = sprintf('INSERT INTO quest_%d (%s starttime, endtime, user_id) VALUES (%s "%s", "%s", "%d")',
        $clean['quest_id'],
        (count($questions) > 0) ? implode(',', $questions) . ',' : '',
        (count($answers) > 0) ? implode(',', $answers) . ',' : '',
        $clean['starttime'],
        date('Y-m-d H:i:s'),
        $_SESSION['user_id']
    );
    $q = str_replace('"NULL"', 'NULL', $q);
    $query = new myQuery($q);
    */
    
    // record data in quest_data
    foreach ($clean as $qu => $a) {
        if (substr($qu, 0, 1) == 'q' && is_numeric(substr($qu, 1))) {
            $q = sprintf('INSERT INTO quest_data (quest_id, user_id, session_id, question_id, dv, `order`, dt) 
                 VALUES(%d, %d, %d, %d, "%s", %d, "%s")',
                 $clean['quest_id'],
                 $_SESSION['user_id'],
                 $_SESSION['session_id'],
                 str_replace('q', '', $qu),
                 $a,
                 $order++,
                 date('Y-m-d H:i:s')
            );
            $q = str_replace('"NULL"', 'NULL', $q);
            $query = new myQuery($q);
        }
    }
    
    
    
    // send to feedback page
    echo 'url;/fb?type=quest&id=' . $clean['quest_id'];

    exit;
}

/****************************************************
 * Display Questionnaire
 ***************************************************/


// set up questionnaire
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

$quest_id=$_GET['id'];
$q = new questionnaire($quest_id);

if (!$q->check_exists()) {
    header('Location: /'); 
    exit;
}

if (!$q->check_eligible()) { 
    if (in_array($_SESSION['status'], $RES_STATUS)) {
        $ineligible = "<p class='error'>You would not be able to see this questionnaire because of your age or sex if you were a non-researcher.</p>";
    } else {
        header('Location: /fb?ineligible&type=quest&id=' . $quest_id); exit;
    }
}

$title = array(
    $q->get_name()
);

$styles = array(
    '#dialog-confirm' => 'display: none;'
);

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

echo $ineligible;

$q->print_form();

?>

<div id='dialog-confirm'>
    <p>You have not answered some questions. Are you sure you want to submit?</p>
</div>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

    $(function() {

        // cancel empty alert on change for select field
        $('#qTable select, #qTable input, #qTable textarea').change( function() {
            if ($(this).val() != "NULL") {
                $(this).closest('#qTable > tbody > tr').removeClass('emptyAlert');
            }
        });
    
    });
    
    // form is submitted
    function submitQ(quest_id) {
        recordAnswers();
        return true;
        
        // check for empty questions
        var fields = {};
        $.each($('#maincontent form').serializeArray(), function(index,value) {
            fields[value.name] = value.value;
        });
        
        $('div.slider').each(function() {
            fields[$(this).attr("id")] = $(this).slider("value");
        });
        
        var emptyFields = 0;
        // look through visible questionnaire rows (only rows that have id and not ranking rows) for empty variables
        $('#qTable > tbody > tr[id]:not(.ranking):visible').each( function(i) {
            $(this).removeClass('emptyAlert');
            var qid = $(this).attr('id').replace('_row','');

            if (fields[qid] == '' || fields[qid] == null || fields[qid] == 'NULL') {
                $(this).addClass('emptyAlert');
                emptyFields++;
            }
        });
        if (emptyFields > 0) {
            $('#dialog-confirm').dialog({
                resizable: false,
                modal: true,
                title: "Missing Data",
                show: 'fade',
                buttons: {
                    "Submit with missing info": function() {
                        $(this).dialog("close");
                        
                        recordAnswers();
                    },
                    "Go back to questionnaire": function() {
                        $(this).dialog("close");
                    }
                }
            });  
        } else {
            recordAnswers();
        }
    }
    
    function recordAnswers() {
        var theData = $('#maincontent form').serializeArray();
        $('div.slider').each(function() {
            theData[theData.length] = {
                name: $(this).attr("id"), 
                value: $(this).slider("value") 
            };
        });
        
        // record answers
        $.ajax({
            type: 'POST',
            url: '/include/scripts/record_quest', //'/quest?submit',
            data: theData,
            success: function(response) {
                parsedResponse = response.split(';');
                if (parsedResponse[0] == 'url') {
                    window.location.href=parsedResponse[1];
                } else {
                    $('<div />').html(response).dialog();
                }
            }
        });
    }

</script>

<?php

$page->displayFooter();

?>