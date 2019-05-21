<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res','admin'), "/res/");

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('User Status')
);

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
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(true);

$page->displayHead($styles);

$page->displayBody();

$query = new myQuery('SELECT res.user_id AS ID, 
                          CONCAT(lastname, ", ", firstname) as Name,
                          email as Email,
                          status as Status
                      FROM res
                      LEFT JOIN user USING (user_id)
                      LEFT JOIN dashboard as d ON d.id = res.user_id 
                        AND d.type="user" 
                        AND d.user_id=' . $_SESSION['user_id'] . '
                      ORDER BY status+0, lastname');
                          
echo $query->get_result_as_table(true, true);

?>

<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$(function() {
    var status_menu = '<select class="status_changer">' +
                      '<option value="test">test</option>' +
                      '<option value="guest">guest</option>' +
                      '<option value="registered">registered</option>' + 
                      '<option value="student">student</option>' + 
                      '<option value="res">researcher</option>' + 
                      '<option value="admin">admin</option>' + 
                      '</select>';
    
    $('table.query tbody tr').each( function(i) {
        var status_cell = $(this).find('td:nth-child(4)');
        var the_id = $(this).find('td:nth-child(1)').html();
        var the_status = status_cell.html();
        status_cell.wrapInner('<span />').find('span').click(function() {
            $('select.status_changer').hide().prev('span').show();
            $(this).hide().next('select').show();
        });
        status_cell.append(status_menu);
        status_cell.find('select').hide().val(the_status).change(function() {
            var $sel = $(this);
            $.ajax({
                url: '/res/scripts/resstatus',
                type: 'POST',
                data: {
                    status: $sel.val(),
                    id: the_id
                },
                success: function(data) {
                    if (data == 'Status of user '+the_id+' changed to '+ $sel.val() ) {
                        $sel.hide().prev('span').show().html($sel.val());
                    } else {
                        $('<div />').dialog({
                            width: 600
                        }).html(data);
                        $sel.hide().prev('span').show();
                    }
                }
            });
            
            
        });
    });
});

</script>

<script src="/include/js/sorttable.js"></script>

<?php

$page->displayFooter();

?>