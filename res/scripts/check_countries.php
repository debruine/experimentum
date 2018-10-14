<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
auth(8);

include_once "Net/GeoIP.php";

$geoipCountry = Net_GeoIP::getInstance("/usr/local/zend/share/pear/data/Net_GeoIP/data/GeoIP.dat");
$geoipCity = Net_GeoIP::getInstance("/usr/local/zend/share/pear/data/Net_GeoIP/data/GeoLiteCity.dat");

$q = new myQuery('SELECT ip FROM login WHERE country_check IS NULL GROUP BY ip LIMIT 5000');

$all_IPS = $q->get_assoc(false, false, 'ip');

echo count($all_IPS) . ' IPs to process... ';

foreach ($all_IPS as $ip) {
	//echo "<li>$ip</li>";
	$country = $geoipCountry->lookupCountryCode($ip); 
	$location = $geoipCity->lookupLocation($ip);
	$city = my_clean($location->city);
    $country2 =	$location->countryCode;
    $region = my_clean($location->region);
	
	ifEmpty($country, 'XX');
	
	$q->set_query("UPDATE login SET country_check='$country', country_check2='$country2', city_check='$city', region_check='$region' WHERE ip='$ip'");	
}

echo '<br />... Done';

$q = new myQuery('SELECT COUNT(DISTINCT ip) as c FROM login WHERE country_check IS NULL');
echo '<br />' . $q->get_one() . ' IPs left to process';

?>
