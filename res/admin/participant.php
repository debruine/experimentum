<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('res','admin'), "/res/");

/****************************************************/
/* !Display Page */
/***************************************************/

$title = array(
    '/res/' => loc('Researchers'),
    '/res/admin/' => loc('Admin'),
    '' => loc('Participants')
);

$styles = array(
    '#graph_container'  =>  'width: 90%; 
                            height: 500px; 
                            margin: 1em auto;'
);

if (array_key_exists('tables', $_GET)) {

    // get list of all tables

    $sections = array(
        "exp" => "Experiments",
        "quest" => "Questionnaires"
    );
    
    foreach($sections as $section => $sectname) {
        echo "<table class='sortable' id='participant_{$section}_table'>\n";
        echo "<tr><th>ID</th><th>$sectname</th><th>Count</th><th>Last Date</th></tr>\n\n";
        
        $query = new myQuery("SELECT id, res_name FROM $section ORDER BY id DESC");
        $rows = $query->get_assoc();
        
        foreach ($rows as $myrow) {
            $query = sprintf("SELECT count(DISTINCT session_id) as c, 
                                     MAX(%s) as maxdate 
                                FROM %s_data 
                               WHERE %s_id=? AND user_id=?
                            GROUP BY NULL", 
                ($section=='exp'? 'dt' : 'endtime'), 
                $section, 
                $section
            );
            $q2 = new myQuery();
            $q2->prepare($query, array('ii', $myrow['id'], $_GET['user_id']));
            
            if ($q2->get_num_rows() > 0) {
                $rows2 = $q2->get_row();
                echo sprintf("<tr><td><a href='../%s/info?id=%d'>%s</a></td><td>%s</td><td>%s</td><td>%s</td><?tr>\n\n", 
                    $section, $myrow['id'], $myrow['id'], $myrow['res_name'], $rows2['c'], $rows2['maxdate']
                );
            }
        }
        
        echo "</table>\n\n";
    }
    
    exit;
}

if (array_key_exists('find', $_GET)) {
    $mystatus = array_search($_SESSION['status'], $ALL_STATUS);
        
    // get user data
    $id = ($_GET['id']) ? $_GET['id'] : $_POST['id'];
    
    if ($_POST['username'] && $_POST['username'] != $_POST['old_username']) {
        if ($_POST['findcontaining'] == 'true') {
            $query = "SELECT user.*, IF(status<=? OR 
                                    'admin'=? OR 
                                    user.user_id=?,
                                    LEFT(MD5(regdate),10), '') as p
                            FROM user WHERE LOCATE(?, username)";
            $params = array('isis', $mystatus, $_SESSION['status'], $_SESSION['user_id'], $_POST['username']);
            $searched_on = "username containing $_POST[username]";
        } else {
            $query = "SELECT user.*, IF(status<=? OR 
                                    ?='admin' OR 
                                    user.user_id=?,
                                    LEFT(MD5(regdate),10), '') as p 
                            FROM user WHERE username=?";
            $params = array('isis', $mystatus, $_SESSION['status'], $_SESSION['user_id'], $_POST['username']);
            $searched_on = "username = $_POST[username]";
        }
    } else {
        $query = "SELECT user.*, IF(status<=? OR 
                                    ?='admin' OR 
                                    user.user_id=?,
                                    LEFT(MD5(regdate),10), '') as p FROM user WHERE user_id=?";
        $params = array('isii', $mystatus, $_SESSION['status'], $_SESSION['user_id'], $id);
        $searched_on = "user_id = $id"; 
    }
    
    $q = new myQuery();
    $q->prepare($query, $params);
    
    if ($q->get_num_rows() == 0) { // change user form
        echo 'error:Could not find the user with ' . $searched_on;
    } elseif ($q->get_num_rows() > 1) { 
        $userlist = $q->get_assoc();
        $userlist['userlist'] = true;
        echo json_encode($userlist);
    } else {
    
        $userdata = $q->get_assoc(0);
        $query = new myQuery("SELECT * FROM login WHERE user_id='$userdata[user_id]' ORDER BY logintime DESC");
        $myrow = $query->get_assoc(0);
        $login_n = $query->get_num_rows();
        
        $userdata['login_n'] = $login_n;
        $userdata['logintime'] = $myrow['logintime'];
        $userdata['ip'] = $myrow['ip'];
        
        echo json_encode($userdata);
    }
    exit;
}

// reset password
if (array_key_exists("resetpswd", $_GET)) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789123456789";
    $password = substr(str_shuffle($chars),0,10);
    
    $query = new myQuery("UPDATE user set password=MD5('{$password}') WHERE user_id={$_POST[id]}");
    if ($query->get_affected_rows() > 0) {
        echo "Password changed to: $password";
    } else {
        echo 'Error--password not changed';
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

?>

<dl class='usersearch'>
    <dt><label for='username'>username</label>:</dt>    
        <dd>
            <input type='text' id='username' name='username' value='' maxlength='32' size='16' />
            <input type='checkbox' id='findcontaining' name='findcontaining' value='true' /> <label for='findcontaining'>containing</label>
        </dd>
    <dt><label for='id'>id</label>:</dt>                
        <dd><input type='text' id='id' name='id' value='' maxlength='8' size='8' /></dd>
</dl>
<div class="buttonset">
    <button id='finduser'>find user</button>
    <button id='resetpswd'>reset password</button>
</div>

<p id="msgtext"></p>
    
<dl id="userinfo">
    <dt>Sex:</dt><dd id='sex'></dd>
    <dt>Birthdate:</dt><dd id='birthdate'></dd>
    <dt>Status:</dt><dd id='status'></dd>
    <dt>Autologin link:</dt><dd id='autologin'></dd>
</dl>

<button id="show_comp_tables">Show Completed Studies</button>
<div id="completed_tables">
    
</div>


<!--*************************************************
 * Javascripts for this page
 *************************************************-->

<script>

$(function() {
    $('#userinfo').hide();
    $('.buttonset').buttonset();
    $('#id, #username').keydown(function(e){
        if (e.which == KEYCODE.enter || e.which == KEYCODE.tab) $('#finduser').click();
        if ($('#finduser span').html() == 'reset search') { return false; }
    });
    $('#finduser').click( function() {
        if ($('#finduser span').html() == 'reset search') {
            $('#msgtext').html("");
            $('#id,#username').val('');
            $('#userinfo,#show_comp_tables').hide();
            $('#finduser span').html('find user');
            $('#resetpswd').button({disabled: true});
            $('#completed_tables').html('');
            $('#findcontaining').attr('checked', false);
            $('#username, #findcontaining, label[for="findcontaining"]').show();
            $('#userlist').remove();
        } else {
            $.ajax({
                url: 'participant?find',
                data: $('.usersearch input').serialize(),
                type: 'POST',
                dataType: 'json',
                success: function(data) {
                    $('#msgtext').html("");
                    if (data.userlist) {
                        var userlist = $('<select id="userlist" />');
                        
                        var n = 0;
                        $.each(data, function(user) {
                            if (user != 'userlist') {
                                userlist.append('<option value="' + data[user].username + '">'+ data[user].username + '</option>');
                                n++;
                            }
                        });
                        userlist.prepend('<option value="" selected="selected">' + n + ' matching usernames found</option>');
                        $('#username').after(userlist);
                        $('#userlist').change( function() {
                            $('#username').val($('#userlist').val());
                            $('#username, #findcontaining, label[for="findcontaining"]').show();
                            $('#finduser span').html('find user');
                            $('#finduser').click();
                            $('#userlist').remove();
                        });
                        $('#findcontaining').attr('checked', false);
                        $('#finduser span').html('reset search');
                        $('#username, #findcontaining, label[for="findcontaining"]').hide();
                    } else {
                        $('#id').val(data.user_id);
                        $('#username').val(data.username);
                        $('#sex').html(data.sex);
                        $('#birthdate').html(data.birthday);
                        if (data.p == '' || data.user_id == <?= $_SESSION['user_id'] ?>) {
                            $('#status').html(data.status);
                        } else {
                            var $status = $('<select/>');
                            $status.append('<option value="test">test</option>');
                            $status.append('<option value="guest">guest</option>');
                            $status.append('<option value="registered">registered</option>');
                            <?php if ($_SESSION['status'] == 'admin' || $_SESSION['status'] == 'res') { ?> $status.append('<option value="student">student</option>'); <?php } ?>
                            <?php if ($_SESSION['status'] == 'admin') { ?> $status.append('<option value="res">researcher</option>'); <?php } ?>
                            <?php if ($_SESSION['status'] == 'admin') { ?> $status.append('<option value="admin">admin</option>'); <?php } ?>
                            $status.val(data.status);
                            $('#status').html("").append($status);
                        }
                        if (data.p == '') {
                            $('#autologin').html('You do not have authorisation to get the autologin for ' + data.status + 's');
                        } else {
                            $('#autologin').html('<a href="/include/scripts/login?u=' + data.user_id + '&p=' + data.p + '&url=/">http://<?= $_SERVER['HTTP_HOST'] ?>/include/scripts/login?u=' + data.user_id + '&p=' + data.p + '&url=/</a>');
                        }
                        $('#userinfo').show();
                        $('#resetpswd').button({disabled: false});
                        $('#show_comp_tables').show();
                        $('#finduser span').html('reset search');
                    }
                }
            });
        }
    });
    
    $('#status').on('change', 'select', function() {
        $.ajax({
            url: '/res/scripts/resstatus',
            data: {
                id: $('#id').val(),
                status: $('#status select').val()
            },
            type: 'POST',
            dataType: 'json',
            success: function(data) {
                growl(data, 3000);
            }
        });
    });
    
    $('#resetpswd').click( function() {
        $.ajax({
            url: 'participant?resetpswd',
            data: $('#id').serialize(),
            type: 'POST',
            success: function(data) {
                $('#msgtext').html(data);
            }
        });
    }).button({disabled: true});

    $('#show_comp_tables').button().click(function() {
        $('#completed_tables').html("<img src='/images/loaders/loading.gif' />").load('participant?tables&user_id=' + $('#id').val(), function() {
            stripe('#completed_tables tbody');
            
            $('#completed_tables table.sortable').each(function() {
                var t = $(this).get(0);
                sorttable.makeSortable(t);
            });
            $('#show_comp_tables').hide();
        });
    }).hide();
});

</script>

<script src="/include/js/sorttable.js"></script>

<?php

$page->displayFooter();

?>