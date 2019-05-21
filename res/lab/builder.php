<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
auth($RES_STATUS);

$title = array(
	'/res/' => 'Researchers',
	'/res/lab/' => 'Labs',
	'/res/lab/builder' => 'Add Lab'
	
);

/****************************************************
 * AJAX Responses
 ***************************************************/
 
if (array_key_exists('update', $_GET)) {
    
}

if (isset($_GET['id'])) {
    $q = new myQuery();
    $q->prepare(
        "SELECT * FROM lab WHERE id=?", 
        array('i', $_GET['id'])
    );
    $lab = $q->get_one_row();
} else if (isset($_GET['code'])) {
    $q = new myQuery();
    $q->prepare(
        "SELECT * FROM lab WHERE code=?", 
        array('s', $_GET['code'])
    );
    $lab = $q->get_one_row();
} else {
    $lab = array(
        "code" => "",
        "name" => "",
        "country" => "",
        "contact" => "",
        "email" => ""
    );
}


/****************************************************
 * Set up forms
 ***************************************************/
 
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';

$input = array();
$input_width = 350;

// code
$input['code'] = new input('code', 'code', $lab['code']);
$input['code']->set_question('Lab Code');
$input['code']->set_maxlength(7);
$input['code']->set_width($input_width);
$input['code']->set_required(true);

// name
$input['name'] = new input('name', 'name', $lab['name']);
$input['name']->set_question('Lab Name');
$input['name']->set_width($input_width);
$input['name']->set_required(true);

// contact
$input['contact'] = new textarea('contact', 'contact', $lab['contact']);
$input['contact']->set_question('Contact People');
$input['contact']->set_width($input_width);
$input['contact']->set_required(true);

// email
$input['email'] = new input('email', 'email', $lab['email']);
$input['email']->set_question('Contact Email');
$input['email']->set_width($input_width);
$input['email']->set_required(true);

// country
$input['country'] = new countries('country', 'country', $lab['country']);
$input['country']->set_question('Country');
$input['country']->set_required(true);

// language
$input['language'] = new select('language', 'language', $lab['language']);
$input['language']->set_question('Language');
$q = new myQuery("SELECT language FROM lab GROUP BY language ORDER BY language");
$langs = array();
if ($q->get_num_rows()) $langs = $q->get_key_val("language", "language");
$langs['new'] = "new language";
$input['language']->set_options($langs);
$input['language']->set_null(false);

// newlang
$input['newlang'] = new input('newlang', 'newlang');
$input['newlang']->set_width($input_width);
$input['newlang']->set_question('New Language');


// set up form table
$q = new formTable();
$q->set_table_id('labInformation');
$q->set_title('Lab Information');
$q->set_action('');
$q->set_questionList($input);
$q->set_method('post');
$q->set_buttons(array(
    'Update Lab' => 'update();'
));
$q->set_button_location('bottom');


$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);
$page->displayBody();

echo '<p class="alert" id="response" style="display:none;" onclick="this.toggle()"></p>' , ENDLINE;

$q->print_form();


/****************************************************
 * Javascripts for this page
 ***************************************************/

?>

<script>
    $("#newlang_row").hide();
    $("#language").change(function() {
        var shownewlang = $("#language").val() == "new";
        $("#newlang_row").toggle(shownewlang);
    })
    
    // update lab
    function update() {
        var theData = $('#myInformation_form').serialize();
        $.ajax({
            url: '?update',
            type: 'POST',
            data: theData,
            success: function(data) {
                
            }
        });
    }

    
</script>

<?php


$page->displayFooter();

?>