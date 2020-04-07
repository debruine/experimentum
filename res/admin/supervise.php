<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'super'), "/res/");

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('Supervision')
);

$styles = array();

// change status
if (array_key_exists("change", $_GET)) {
    if (!in_array($_POST['status'], $ALL_STATUS)) {
        echo 'Error--' . $_POST['status'] . ' not a valid status';
        exit;
    }
    
    $query = new myQuery("UPDATE user set status='{$_POST['status']}' WHERE user_id={$_POST['user_id']}");
    if ($query->get_affected_rows() > 0) {
        echo "Status changed to {$_POST['status']}";
    } else {
        echo 'Error--status not changed';
    }
    exit;
}

/****************************************************/
/* Get List of Supervisees */
/***************************************************/

$query = new myQuery();

if ($_SESSION['status'] == 'admin') {
    $query->set_query(
        'SELECT r.user_id AS ID, 
                CONCAT(r.lastname, ", ", r.firstname) as Name,
                r.email as Email,
                s.user_id as Supervisor,
                status as Status,
                r.supervisor_id as ` `
           FROM res AS r 
      LEFT JOIN user AS u   ON u.user_id = r.user_id
      LEFT JOIN res AS s    ON s.user_id = r.supervisor_id
       ORDER BY u.status+0, r.lastname');
} else {
    $query->prepare(
        'SELECT r.user_id AS ID, 
                CONCAT(r.lastname, ", ", r.firstname) as Name,
                r.email as Email,
                CONCAT(s.lastname, ", ", s.firstname) as Supervisor,
                status as Status,
                r.supervisor_id as ` `
           FROM res AS r
      LEFT JOIN user AS u   ON u.user_id = r.user_id
      LEFT JOIN res AS s    ON s.user_id = r.supervisor_id
          WHERE r.supervisor_id = ?
       ORDER BY u.status+0, r.lastname',
       array('i', $_SESSION['user_id'])
    );
}

// supervisor list
$q = new myQuery("SELECT user_id, CONCAT(lastname, ', ', firstname) as name 
                            FROM res
                       LEFT JOIN user USING (user_id) 
                           WHERE status IN ('admin', 'super')
                        ORDER BY lastname, firstname");
$super = $q->get_key_val('user_id', 'name');
$supermenu = "var super_menu =  '<select class=\"super_changer\">' +" . ENDLINE;
$supermenu .= "    '<option value=\"\">None Assigned</option>' +" . ENDLINE;
foreach ($super as $id => $name) {
    $supermenu .= "    '<option value=\"{$id}\">{$name}</option>' +" . ENDLINE;
}
$supermenu .= "'</select>';" . ENDLINE;


/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);

$page->displayBody();

?>

<p>Users with "registered" status have requested research status. Click on a status to change it.</p>

<?= $query->get_result_as_table(true, true) ?>

<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

var status_menu = '<select class="status_changer">' +
                  '<option value="test" style="color: var(--rainbow1)">test</option>' +
                  '<option value="guest" style="color: var(--rainbow2)">guest</option>' +
                  '<option value="registered" style="color: var(--rainbow3)">registered</option>' + 
                  '<option value="student" style="color: var(--rainbow4)">student</option>' + 
                  '<option value="res" style="color: var(--rainbow5)">researcher</option>' + 
                  '<option value="super" style="color: var(--rainbow6)">supervisor</option>' + 
                  '<option value="admin" style="color: black">admin</option>' + 
                  '</select>';

<?= $supermenu ?>

function sendMail(button, supervisee_id) {
    var supervisor_id = $(button).closest("tr").find("select.super_changer").val();
    if (supervisor_id == "") { return false; }
    
    $.ajax({
        url: '/res/scripts/emailres',
        type: 'POST',
        dataType: 'json',
        data: {
            supervisor_id: supervisor_id,
            supervisee_id: supervisee_id
        },
        success: function(data) {
            if (data.error) {
                $('<div />').dialog({
                    width: 600
                }).html(data.error);
            } else {
                $('<div />').dialog({
                    width: 600
                }).html("Email Sent to " + data.sor + " and " + data.see);
            }
        }
    });
}

function changeSupervisor(sel, the_id) {
    var $sel = $(sel);
    $sel.css('color', 'red');
    $.ajax({
        url: '/res/scripts/supervise',
        type: 'POST',
        dataType: 'json',
        data: {
            supervisor_id: $sel.val(),
            supervisee_id: the_id
        },
        success: function(data) {
            if (data.error) {
                $('<div />').dialog({
                    width: 600
                }).html(data.error);
            } else {
                $sel.css('color', 'black');
            }
        }
    });
}

function changeResStatus(sel, the_id) {
    var $sel = $(sel);
    $sel.css('color', 'red');
    $.ajax({
        url: '/res/scripts/resstatus',
        type: 'POST',
        dataType: 'json',
        data: {
            status: $sel.val(),
            id: the_id
        },
        success: function(data) {
            if (data.error) {
                $('<div />').dialog({
                    width: 600
                }).html(data.error);
            } else {
                if (sel.value == "test") {
                    $sel.css('color', 'var(--rainbow1)');
                } else if (sel.value == "guest") {
                    $sel.css('color', 'var(--rainbow2)');
                } else if (sel.value == "registered") {
                    $sel.css('color', 'var(--rainbow3)');
                } else if (sel.value == "student") {
                    $sel.css('color', 'var(--rainbow4)');
                } else if (sel.value == "res") {
                    $sel.css('color', 'var(--rainbow5)');
                } else if (sel.value == "super") {
                    $sel.css('color', 'var(--rainbow6)');
                } else if (sel.value == "admin") {
                    $sel.css('color', 'black');
                }
            }
        }
    });
}

function removeRes(button, supervisee_id) {
    var $row = $(button).closest("tr");
    var theStatus = $row.find('select.status_changer').val();
    if (!['test','guest','registered'].includes(theStatus)) {
        $('<div />').dialog({ width: 600 }).html("Change the status to test, guest, or registered first");
        return false;
    }
    
    var theName = $row.find('td').eq(1).text();
    
    $('<div />').dialog({
        resizable: false,
        modal: true,
        width: 600,
        title: "Remove",
        show: 'fade',
        buttons: {
            "Cancel": function() {
                $(this).dialog("close");
            },
            "Remove": function() {
                $(this).dialog("close");
                
                $.ajax({
                    url: '/res/scripts/removeres',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        supervisee_id: supervisee_id
                    },
                    success: function(data) {
                        if (data.error) {
                            $('<div />').dialog({
                                width: 600
                            }).html(data.error);
                        } else {
                            $row.remove();
                        }
                    }
                });
            }
        }
    }).html('Are you sure you want to remove ' + theName + ' from consideration for researcher status?');;
}

$('table.query tbody tr td:nth-child(2)').wrapInner("<a></a>").find('a').click(function() {
    var user_id = $(this).closest('tr').find('td:nth-child(1)').html();
    document.location = '/res/admin/participant?id=' + user_id;
});

$('table.query tbody tr').each( function(i) {
    var the_id = $(this).find('td:nth-child(1)').html();
    
    // add function buttons
    var $mailbutton = $("<button class='tinybutton' />")
                        .html("Send Email").button().click( function() {
                            sendMail(this, the_id);
                        });
    var $rembutton = $("<button class='tinybutton' />")
                        .html("Remove").button().click( function() {
                            removeRes(this, the_id);
                        });
    $(this).find('td:nth-child(6)').html("").append($mailbutton).append($rembutton);
    
    
    // replace status with drop-down menu
    var status_cell = $(this).find('td:nth-child(5)');
    var the_status = status_cell.html();
    status_cell.html(status_menu);
    $sel = status_cell.find('select');
    $sel.show().val(the_status).change(function() {
        changeResStatus(this, the_id);
    });
    if (the_status == "test") {
        $sel.css('color', 'var(--rainbow1)');
    } else if (the_status == "guest") {
        $sel.css('color', 'var(--rainbow2)');
    } else if (the_status == "registered") {
        $sel.css('color', 'var(--rainbow3)');
    } else if (the_status == "student") {
        $sel.css('color', 'var(--rainbow4)');
    } else if (the_status == "res") {
        $sel.css('color', 'var(--rainbow5)');
    } else if (the_status == "super") {
        $sel.css('color', 'var(--rainbow6)');
    } else if (the_status == "admin") {
        $sel.css('color', 'black');
    }
<?php if ($_SESSION['status'] == 'admin') { ?>
    // get supervisor list
    var super_cell = $(this).find('td:nth-child(4)');
    var the_super = super_cell.html();
    super_cell.html(super_menu);
    super_cell.find('select').show().val(the_super).change(function() {
        changeSupervisor(this, the_id);
        if (this.value == "") {
            $(this).css('background', '#f7f7db');
        } else {
            $(this).css('background', 'white');
        }
    });
    if (the_super == "") {
        super_cell.find('select').css('background', '#f7f7db');
    }
<?php } ?>
});

</script>

<script src="/include/js/sorttable.js"></script>

<?php

$page->displayFooter();

?>