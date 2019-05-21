<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once DOC_ROOT . '/include/classes/quest.php';
require_once DOC_ROOT . '/include/classes/Parsedown.php';
auth($RES_STATUS);

if (validID($_GET['id']) && !permit('project', $_GET['id'])) header('Location: /res/');
$item_id = $_GET['id'];

$title = array(
    '/res/' => 'Researchers',
    '/res/project/' => 'Projects',
    '' => 'Info'
);

$styles = array(
    "#setitems" => "width: 100%;",
    "#setitems td+td+td+td+td" => "text-align: right;",
    "#setitems td" => "border-left: 1px dotted grey;",
    "#setitems tr" => "border-right: 1px dotted grey;",
    "span.set_nest" => "display: inline-block; width: 20px; height: 20px; background: transparent no-repeat center center url(/images/linearicons/arrow-down?c=F00);",
    "span.set_nest.hide_set"    => "background-image: url(/images/linearicons/arrow-right?c=000);",
    ".potential-error" => "color: hsl(0, 100%, 40%);"
);
    
/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<input type="hidden" id="item_id" value="" />
<input type="hidden" id="item_type" value="project" />

<h2>Project <span id='id'></span>: <span id='res_name'></span></h2>

<div class='toolbar'>
    <div id="function_buttonset"><?php
        echo '<button id="view-project">Go</button>';
        echo '<button id="edit-item">Edit</button>';
        echo '<button id="delete-item">Delete</button>';
        echo '<button id="duplicate-item">Duplicate</button>';
        echo '<button id="download-exp">Exp Data</button>';
        echo '<button id="download-quest">Quest Data</button>';
        echo '<button id="get-json">Structure</button>';
    ?></div>
</div>

<table class='info'> 
    <tr><td>Name:</td><td id='name'>...</td></tr>
    <tr><td>Status:</td> <td><span id='status-select'>...</span></td></tr>
    <tr><td>Created on:</td><td id='create_date'>...</td></tr>
    <tr><td>Owners:</td> 
        <td id='owners'>
            <ul id='owner-edit'></ul>
            <?php if ($_SESSION['status'] != 'student') { ?>
            <input id='owner-add-input' type='text' > 
            <button class='tinybutton' id='owner-add'>add</button>
            <button class='tinybutton' id='owner-add-items'>add all items</button>
            <?php } ?>
        </td></tr>
    <tr><td>Labnotes:</td><td id='labnotes'> ...</td></tr>
    <tr><td>URL:</td><td><span id='url'>...</span></td></tr>
    <tr><td>Completion:</td><td id='completion'><span id='users'>...</span> users started <span id='sessions'>...</span> sessions</td></tr>
    <tr><td>Restrictions:</td><td><span id='sex'>...</span> ages <span id='lower_age'>...</span> to <span id='upper_age'>...</span> years</td></tr>
    <tr><td>Blurb:</td><td id='blurb'>...</td></tr>
    <tr><td>Intro:</td><td id='intro'>...</td></tr>
</table>

<p class="fullwidth">The table below shows the number of total completions 
    (and unique participants) for the items from this project. If the items are 
    used in other projects, data collected via the other projects will not count 
    towards the participant numbers below (but will count towards the timing estimates).</p>
    

<table id="setitems">
    <thead>
        <tr>
            <td>Item</td>
            <td>Name</td>
            <td>Status</td>
            <td>Type</td>
            <td>People</td>
            <td>Men</td>
            <td>Women</td>
            <td>Median Time</td>
            <td>90th Percentile</td>
        </tr>
    </thead>
    <tbody id='project_items'></tbody>
    <tfoot>
        <td>Totals</td>
        <td></td>
        <td></td>
        <td></td>
        <td id="total_people">...</td>
        <td id="total_men">...</td>
        <td id="total_women">...</td>
        <td id="total_median">...</td>
        <td id="total_upper">...</td>
</table>

<div id="help" title="Set Info Help">
    
    <ul>
        <li>The table shows information about each item in the set.</li>
        <li>Subsets are indented under their set name.</li>
        <li>The totals at the bottom are for every item, even if all or some of the subsets are &ldquo;one of&rdquo; types.</li>
        <li>Click the &ldquo;Test&rdquo; button to generate a sample order.</li>
        <li>Click the &ldquo;Go&rdquo; button to participate in the set.</li>
    </ul>
</div>

<!--**************************************************-->
<!-- !Javascripts for this page -->
<!--**************************************************-->


<script>
    $( "#view-project" ).click(function() {
        window.location = $('#url').text();
    });
    
    $('#owner-add-input').autocomplete({
        source: [],
        focus: function( event, ui ) {
            $(this).val(ui.item.name);
            return false;
        },
        select: function( event, ui ) {
            $(this).val(ui.item.name).data('id', ui.item.value);
            return false;
        }
    }).data('id', 0);
    
    function getProjectInfo() {
        $.ajax({
            url: '/res/scripts/project_info',
            type: 'POST',
            dataType: 'json',
            data: {id: <?= $item_id ?>},
            success: function(data) {
                if (data.error) {
                    $('<div />').dialog({
                        width: 600
                    }).html(data.error);
                } else {
                    $('#item_id').val(data.info.id);
                    $('#id').html(data.info.id);
                    $('#name').html(data.info.name);
                    $('#res_name').html(data.info.res_name);
                    $('#status').html(data.info.status);
                    $('#create_date').html(data.info.create_date);
                    $('#url').html(data.info.url);
                    $('#labnotes').html(data.info.labnotes || '<span class="error">Please add labnotes</span>');
                    $('#users').html(data.info.users);
                    $('#sessions').html(data.info.sessions);
                    $('#sex').html(data.info.sex);
                    $('#lower_age').html(data.info.lower_age);
                    $('#upper_age').html(data.info.upper_age);
                    $('#intro').html(data.info.intro);
                    $('#blurb').html(data.info.blurb);
                    $('#status-select').html(data.status);
                    
                    $('#owner-edit').html(data.owners.owner_edit);
                    $('#owner-add-input').autocomplete('option', 'source', data.owners.source);
                    $('.tinybutton').button();
                    
                    $('#project_items').html(data.project_items);
                    item_stats(data.items_for_data, $('#item_id').val());
                    
                    $('span.set_nest').click( function() {
                        var hide = !$(this).hasClass("hide_set");
                        var toggle_class = $(this).closest('tr').attr('id');
                        console.log(hide, toggle_class);
                        $('tr.' + toggle_class).toggle(!hide);
                        stripe('#setitems tbody');
                        
                        $(this).toggleClass("hide_set", hide);
                    }).click();
                }
            }
        });
    }
    
    getProjectInfo();

    
    $('#status-select').on('click', '#all-status-change', function() {
        var projstatus = $('#status').val();
        $('#project_items tr').each(function() {
            var tr = this;
            var item = tr.id.split("_");
            var item_type = item[0];
            var item_id = item[1];
            if (item_type == "set") item_type = "sets";
            $.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                data: {
                    type: item_type,
                    status: projstatus,
                    id: item_id
                },
                success: function(data) {
                    if (data.error) {
                        $('<div title="Problem with Status Change" />').html(data.error).dialog();
                    } else {
                        console.log('changed', tr.id);
                        var item_status = $(tr).find('> td.status')
                                                 .html(projstatus)
                                                 .toggleClass('potential-error'. projstatus != 'active');
                    }
                }
            });
        })
    });
    
    
    
</script>

<script src="/res/scripts/res.js"></script>

<?php

$page->displayFooter();

?>