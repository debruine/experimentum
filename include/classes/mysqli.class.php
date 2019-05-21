<?php

// error-handling function
function myerror($msg='MySQL Error') {
    echo '<div class="mysqlerror">'.$msg.'</div>';
}

// connect to mysql database
function my_connect($host = MYSQL_HOST, $user = MYSQL_USER, $pswd = MYSQL_PSWD, $mydb = MYSQL_DB) {
    $db = new mysqli($host, $user, $pswd, $mydb);
    if ($db->connect_errno) {
        return 'Unable to connect to database [' . $db->connect_error . ']';
    } else {
        return $db;
    }
}

// clean a string or array for insertion to mysql
function my_clean($x) {
    global $db;
    
    if (!is_object($db)) { $db = my_connect(); }
    
    if (is_array($x)) {
        foreach ($x as $k=>$v) {
            $clean[$k] = my_clean($v);
        }
    } else if (is_numeric($x)) {
        $clean = $x;
    } else {
        $clean = $db->real_escape_string($x);
    }

    return $clean;
}

/****************************************************
 * MySQL classes
 ***************************************************/
 
class myQuery {
    public  $query;
    private $affected_rows;
    private $insert_id;
    private $data;
    private $num_rows;
    
    function __construct($q = null, $makearray = false) {
        global $db;
        
        if (!is_object($db)) { $db = my_connect(); }
        
        if ($makearray) {
            $q = explode(';', $q);
            foreach ($q as $k => $v) {
                if (trim($v) == '') { 
                    unset($q[$k]);
                } else {
                    $q[$k] = trim($v);
                }
            }
        }
        
        if (is_null($q)) {
            return true;
        } else {
            $this->data = array();
            return $this->set_query($q);
        }
    }

    function set_query($x) { 
        $this->query = $x; 
        return $this->execute_query();
    }
    function get_query()             { return $this->query;             }
    function get_affected_rows()     { return $this->affected_rows;     }
    function get_insert_id()         { return $this->insert_id;         }
    function get_num_rows()          { return $this->num_rows;         }
    
    function execute_query() {
        global $db;
        
        // run query or array of queries
        try {
            if (is_array($this->query)) { 
                foreach ($this->query as $subQ) {
                    if (!$result = $db->query($subQ)) { 
                        throw new Exception($subQ . '<br />' . $db->error);
                    }
                }
            } else {
                if (!$result = $db->query($this->query)) { 
                    throw new Exception($this->query . '<br />' . $db->error);
                }
            }
        } catch (Throwable $e) {
            if ($_SESSION['user_id'] == 1) { echo $e->getMessage(); }
            return false;
        }
        
        $this->affected_rows = $db->affected_rows;
        $this->insert_id = $db->insert_id;
        if (is_object($result)) {
            $this->num_rows = $result->num_rows;
            
            // fetch result
            $this->data = array();
            if ($this->num_rows > 0) {
                while ($mydata = $result->fetch_assoc()) {
                    $this->data[] = $mydata;
                }
            }
        }
        
        return true;
    }
    
    function prepare($query, $params, $return = array()) {
        global $db;
        
        foreach ($params as $key => $value) {
            $p[$key] = &$params[$key]; 
        }
        
        if ($stmt = $db->prepare($query)) {
            call_user_func_array(array(&$stmt, 'bind_param'), $p);
            $stmt -> execute();
            
            if ($return) {
                foreach ($return as $key => $value) {
                    $r[$value] = &$return[$value]; 
                }

                call_user_func_array(array(&$stmt, 'bind_result'), $r); 

                $stmt -> fetch();

                $this->data = $r;
            }
            $stmt -> close();
        }
    }
    
    // main function to return results
    
    function get_assoc($row = false, $key = false, $col = false) {
        $results_array = array();
        reset($this->data);
        
        if ($key !== false) {
            foreach ($this->data as $mydata) {
                if ($col) {         // one column is the key ($key) and one column is the value ($col)
                    $results_array[$mydata[$key]] = $mydata[$col];
                } else {             // one column is the key ($key) and the value is the whole array
                    $results_array[$mydata[$key]] = $mydata;
                }
            }
        } else if ($col !== false) { // get just one column
            foreach ($this->data as $mydata) {
                $results_array[] = $mydata[$col];
            }
        } else {                     // get all results (default)
            $results_array = $this->data;
        }
        
        if ($row === false) {         // return all results (default)
            return $results_array;
        } else {                     // return just one row ($row)
            return $results_array[$row];
        }
    }

    // convenience functions to return common subsets of results
    
    function get_by_id($id) {
        return $this->get_assoc(false, $id);
    }
    
    function get_key_val($key, $val) {
        return $this->get_assoc(false, $key, $val);
    }
    
    function get_row($row = 0) {
        return $this->get_assoc($row, false, false);
    }
    
    function get_one_row($row = 0) { // alias of get_row()
        return $this->get_row($row);
    }
    
    function get_one_array() { // alias of get_row()
        return $this->get_row();
    }
    
    function get_col($col) {
        return $this->get_assoc(false, false, $col);
    }
    
    function get_one_col($col) { // alias of get_col()
        return $this->get_col($col);
    }
    
    function get_one($row=0, $col=0) {
        $a = $this->get_row($row);
        if (!is_array($a)) {
            return "Row <code>$row</code> does not exist";
        } elseif (array_key_exists($col, $a)) {
            return $a[$col];
        } else {
            $keys = array_keys($a);
            if (array_key_exists($col, $keys)) {
                return $a[$keys[$col]];
            } else {
                return "Column <code>$col</code> does not exist in row <code>$row</code>";
            }
        }
    }
    
    function get_row_col($row=0, $col=0) { // alias of get_one()
        return $this->get_one($row, $col);
    }
    
    function get_result_as_table($header = true, $sortable=false, $rotate=false) {
        $array = $this->get_assoc();
        if (empty($array)) return false;
        
        if ($rotate) {
            $array = rotate_array($array);
        }
        
        $sort = ($sortable) ? ' sortable' : '';
        $return = '<table class="query' . $sort . '">' . PHP_EOL;
        
        // table header
        if ($header) {
        $return .= '<thead><tr>';
            $keys = array_keys($array);
            foreach ($array[$keys[0]] as $h => $v) {
                $return .= "    <th>$h</th>" . PHP_EOL;
            }
            $return .= '</tr></thead>';
        }
        
        // table data
        $return .= "<tbody>";
        foreach($array as $a) {
            $return .= "<tr>";
            foreach ($a as $v) {
                $return .= "    <td>$v</td>" . PHP_EOL;
            }
            $return .= '</tr>' . PHP_EOL;
        }
        $return .= "</tbody>";
        
        $return .= '</table>' . PHP_EOL . PHP_EOL;
        
        // add sorting script
        if ($sortable) $return .= '<script src="/include/js/sorttable.js"></script>' . PHP_EOL;
        
        return $return;
    }
}
 
?>