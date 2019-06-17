<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';

$title = array('/osf' => 'OSF Test');
$page = new page($title);
$page->set_menu(true);

$style = array(
    '#profile_img' => 'float: right;',
    'ol' => 'list-style-type: upper-roman;',
    'ol ol' => 'list-style-type: upper-alpha;',
    'ol ol ol' => 'list-style-type: decimal;',
    'ol ol ol ol' => 'list-style-type: lower-alpha;',
    'ol ol ol ol ol' => 'list-style-type: lower-roman;',
    'ol ol ol ol ol ol' => 'list-style-type: decimal;'
);

define('OAUTH2_CLIENT_ID', 'ea9854468f4645d89247eacac8b81fa3');
define('OAUTH2_CLIENT_SECRET', 'FQsDnHKBo4p4ThIpXH1J0mSDGJNe0vgzXfjGV1bT');

$redirectURI = "https://exp.psy.gla.ac.uk/osf";
$authorizeURL = "https://accounts.osf.io/oauth2/authorize";
$revokeURL = "https://accounts.osf.io/oauth2/revoke";
$tokenURL = "https://accounts.osf.io/oauth2/token";
$apiURLBase = 'https://api.osf.io/v2/';

function apiRequest($url, $post=FALSE, $headers=array()) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if ($post) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post, '', '&'));
    }
    $headers[] = 'Accept: application/json';
    if (session('access_token')) {
        $headers[] = 'Authorization: Bearer ' . session('access_token');
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    return json_decode($response, TRUE);
}

function get($key, $default=NULL) {
  return array_key_exists($key, $_GET) ? $_GET[$key] : $default;
}

function session($key, $default=NULL) {
  return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : $default;
}

function array2ul($array) {
    $out = "<ol>";
    foreach($array as $key => $elem){
        if(!is_array($elem)){
                $out .= "<li><span>$key:[$elem]</span></li>";
        }
        else $out .= "<li><span>$key</span>".array2ul($elem)."</li>";
    }
    $out .= "</ol>";
    return $out; 
}

if (get('action') == 'login') {
    // Generate a random hash and store in the session for security
    $_SESSION['state'] = hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']);
    unset($_SESSION['access_token']);
    $params = array(
        'response_type' => 'code',
        'client_id' => OAUTH2_CLIENT_ID,
        'redirect_uri' => $redirectURI,
        'scope' => 'osf.users.profile_read', //'osf.full_read',
        'state' => $_SESSION['state']
    );
    
    // Redirect the user to authorization page
    $url = $authorizeURL . '?' . http_build_query($params, '', '&');
    header('Location: ' . $url);
    die();
}

if (get('code')) {
  // Verify the state matches our stored state
  if (!get('state') || $_SESSION['state'] != get('state')) {
    header('Location: ' . $_SERVER['PHP_SELF']);
    die();
  }
  
  $params = array(
      'code' => get('code'),
      'redirect_uri' => $redirectURI,
      'client_id' => OAUTH2_CLIENT_ID,
      'client_secret' => OAUTH2_CLIENT_SECRET,
      'grant_type' => 'authorization_code'
      //'state' => $_SESSION['state'],
  );
  
  // Exchange the auth code for a token
  $token = apiRequest($tokenURL, $params);
  if ($token['error']) {
      echo "Access error: " . $token['error_description'];
      die();
  }
  $_SESSION['access_token'] = $token['access_token'];
  header('Location: ' . $_SERVER['PHP_SELF']);
  die();
}

if (get('action') == 'revoke') {
  apiRequest($revokeURL, array('token' => session('access_token')));
  unset($_SESSION['access_token']);
  unset($_SESSION['state']);
}

$page->displayHead($style);
$page->displayBody();

?>

<p>I'm playing with the OSF API to learn how to integrate OSF with Experimentum
    or other web apps. You can test this by authorising read-only access to your 
    public user profile below (OSF will explicitly ask you to authorise specific access). 
    <b>I'm not storing any information</b>, so this web app's access expires
    after a few hours or when you click the Revoke button.</p>

<?php
    

if (session('access_token')) {
  $user = apiRequest($apiURLBase . 'users/me/');
  echo '<h3><button id="osf_revoke">Revoke OSF Access</button></h3>';
  echo '<h3>' . $user['data']['attributes']['full_name'];
  if (isset($user['data']['links']['profile_image'])) {
      $profile_img = $user['data']['links']['profile_image'];
      echo "<img src='{$profile_img}' id='profile_img' />\n";
  }
  echo '</h3>';
  
  $regdate = $user['data']['attributes']['date_registered'];
  $d = date_create_from_format("Y-m-d\TH:i:s.u", $regdate);
  $date = date_format($d, "Y-m-d");
  $time = date_format($d, "H:i");
  $today = new DateTime('now');
  $interval = $today->diff($d, TRUE);
  $length = round($interval->format('%a') / 365.25, 1);
  
  
  
  echo "<p>You registered with the OSF {$length} years ago on {$date}.</p>";
  
  $preprints = $user['data']['relationships']['preprints']['links']['related']['href'];
  echo "<p><a href='$preprints'>Preprints</a></p>";
  
  echo '<h3>All Accessible Data</h3>';
  echo array2ul($user);
} else {
  echo '<p><button id="osf_login">Authorise OSF Access</button></p>';
}

?>

<script>
    $('#osf_login').button().click(function() {
        location.href = '/osf?action=login';
    });
    
    $('#osf_revoke').button().click(function() {
        location.href = '/osf?action=revoke';
    });
</script>


<?php

$page->displayFooter();

?>