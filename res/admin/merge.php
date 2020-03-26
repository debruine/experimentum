<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(array('admin', 'res'), "/res/");

$clean = my_clean($_GET);
if (empty($clean)) $clean = array();

$title = array(
    "/res/" => loc("Researchers"),
    "/res/admin/" => loc("Admin"),
    "/res/admin/merge" => loc("Merge Users")
);
$styles = array(".samedata" => "text-align: center;");


$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

echo '<h2>Merge User Data</h2>';

if (($clean['username1'] && $clean['username2']) || ($clean['userid1'] && $clean['userid2'])) {

    if (!empty($clean['username1'])) {
        $q = new myQuery("SELECT user_id, username FROM user WHERE username='$clean[username1]'");
    } else {
        $q = new myQuery("SELECT user_id, username FROM user WHERE user_id='$clean[userid1]'");
    } 
    
    if ($q->get_num_rows() == 1) {
        $u1 = $q->get_one_row();
        $clean['username1'] = $u1['username'];
        $user1 = $u1['user_id'];
    }
    
    if (!empty($clean['username2'])) {
        $q = new myQuery("SELECT user_id, username FROM user WHERE username='$clean[username2]'");
    } else {
        $q = new myQuery("SELECT user_id, username FROM user WHERE user_id='$clean[userid2]'");
    }
    
    if ($q->get_num_rows() == 1) {
        $u2 = $q->get_one_row();
        $clean['username2'] = $u2['username'];
        $user2 = $u2['user_id'];
    }
    
    
    
    if (is_null($user1) || is_null($user2)) {
        echo sprintf("<h2 class='warning'>Unable to merge %s (%s) and %s (%s) </h2>", 
            $clean['username1'], 
            $clean['userid1'],
            $clean['username2'],
            $clean['userid2']
        );
        if (is_null($user1)) { echo "<h3>{$clean['username1']} ({$clean['userid1']}) does not exist</h3>\n"; }
        if (is_null($user2)) { echo "<h3>{$clean['username2']} ({$clean['userid2']}) does not exist</h3>\n"; }
        
    } elseif ($user1 != $user2) {
        $q = new myQuery("SELECT * FROM user WHERE user_id='$user1'");
        $userdata[0] = $q->get_assoc();
        
        $q = new myQuery("SELECT * FROM user WHERE user_id='$user2'");
        $userdata[1] = $q->get_assoc();
        
        $samedata = array(
            "sex",
            "birthday",
            "status",
            "password"
        );
        
        $getdata = sprintf("&amp;userid1=%s&amp;userid2=%s%s%s%s%s",
            $user1,
            $user2,
            $clean['sex'] ? "&amp;sex=$clean[sex]" : "",
            $clean['birthday'] ? "&amp;birthday=$clean[birthday]" : "",
            $clean['status'] ? "&amp;status=$clean[status]" : "",
            $clean['password'] ? "&amp;password=$clean[password]" : ""
        );
        
        foreach ($samedata as $col) {
            if ($userdata[0][$col] != $userdata[1][$col] && empty($clean[$col])) {
                echo "<h2><strong>$col</strong> are not the same, which one do you want to keep?</h2>\n";
                echo "<ul class='samedata'>\n";
                echo sprintf("    <li><a href='merge?%s=%s%s'>%s - %s</a></li>\n",
                    $col,
                    $userdata[0][$col],
                    $getdata,
                    $clean['username1'],
                    $userdata[0][$col]
                );
                echo sprintf("    <li><a href='merge?%s=%s%s'>%s - %s</a></li>\n",
                    $col,
                    $userdata[1][$col],
                    $getdata,
                    $clean['username2'],
                    $userdata[1][$col]
                );
                echo "</ul>\n\n";
                
                $page->displayFooter();
                exit;
            } 
        }
        
        if (array_key_exists("merge", $_GET)) {
            // set user1 to the chosen sex and birthday
            $query = sprintf("UPDATE user SET sex=%s, birthday=%s, status=%s, password=%s WHERE user_id=%s",
                $clean['sex'] ? "'" . $clean['sex'] . "'" : "sex",
                $clean['birthday'] ? "'" . $clean['birthday'] . "'" : "birthday",
                $clean['status'] ? "'" . $clean['status'] . "'" : "status",
                $clean['password'] ? "'" . $clean['password'] . "'" : "password",
                $user1
            );
            $q = new myQuery($query);
            
            // change all tables user2 to user1
            $q = new myQuery("SHOW tables WHERE Tables_in_exp != 'user'");
            $tables = $q->get_assoc();
            $affected_tables = array();
            foreach ($tables as $t) {
                $table = $t['Tables_in_exp'];
                // update tables if user_id column exists
                $q2 = new myQuery("SHOW COLUMNS FROM $table WHERE Field='user_id'");

                if ($q2->get_num_rows() == 1 && $table != "user") {
                    $q3 = new myQuery("UPDATE $table SET user_id=$user1 WHERE user_id=$user2");
                    if ($q3->get_affected_rows() > 0 ) $affected_tables[] = $table;
                }
            }
            
            $q = new myQuery("DELETE FROM res WHERE user_id={$user2}");
            
            // set username2 to unused
            $q = new myQuery("DELETE FROM user WHERE user_id=$user2");
        
            echo sprintf("<h2>User %s deleted and merged into user %s (%s)</h2>", 
                $clean['username2'],
                $clean['username1'],
                $user1
            );
            
            echo "<h3>" . count($affected_tables) . " tables affected</h3>\n";
            if (count($affected_tables) > 0) echo htmlArray($affected_tables);
        } else {
            echo sprintf("<p>Are you sure you want to keep %s (%s) and delete %s (%s)? ", 
                $clean['username1'],
                $user1,
                $clean['username2'],
                $user2
            ); 
            
            echo "<input type='button' class='inline' onclick=\"window.location.href='merge';\" value='Cancel'>\n";
            echo "<input type='button' class='inline' onclick=\"window.location.href='merge?merge{$getdata}';\" value='Merge'>\n";        
        }
    } else {
        echo sprintf("<h2 class='warning'>Unable to merge %s (%s) and %s (%s) </h2>", 
            $clean['username1'], 
            $clean['userid1'],
            $clean['username2'],
            $clean['userid2']
        );
    }
} else {
?>

<h3>Data from the deleted user will be attributed to the kept user.</h3>

<form action='' method='get'>
    
<table class='smallform'>
    <thead><tr><th colspan='2'>Username</th></tr></thead>
    <tr><td>Keep</td><td><input type="text" name="username1" value="<?= $clean['username1'] ?>" /></td></tr>
    <tr><td>Delete</td><td><input type="text" name="username2" value="<?= $clean['username2'] ?>" /></td></tr>
</table>

<h2>or</h2>

<table class='smallform'>
    <thead><tr><th colspan='2'>User ID</th></tr></thead>
    <tr><td>Keep</td><td><input type="text" name="userid1" value="<?= $clean['userid1'] ?>" /></td></tr>
    <tr><td>Delete</td><td><input type="text" name="userid2" value="<?= $clean['userid2'] ?>" /></td></tr>
</table>

<div style='text-align: center; padding-top: 1em;'>
    <input type='submit' class='submit' value='Merge' />
</div>

</div>
</form>

<?php } ?>


<script>
    $('input[type=submit]').button();
    $('input[type=button]').button();
</script>

<?php

$page->displayFooter();

?>