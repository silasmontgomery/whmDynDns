<?php

# WHM Dynamic DNS Update Script v1
# By Silas Montgomery
# Website: http://slimtechnologies.com
# Email: silas@slimtechnologies.com)

# Configuration Options

# Set this to your WHM Admin login
$username = "YourUsername";

# Set this to your WHM Admin password
$password = "YourPassword";

# Set this to your WHM Login URL (2087 is the default WHM SSL port)
$whmUrl = "https://yourwebsite.com:2087/";

# Add one more more websites to scrape our IP from (one or more as array)
$Websites[] = "http://www.yourwebsite.com/ip.php";
$Websites[] = "http://www.ipchicken.com";

# Add your host names here (one or more as array). 'name' is the subdomain and 'zone' is the domain
$Zones[] = array('name' => 'sub1', 'zone' => 'yourzone.com');
$Zones[] = array('name' => 'sub2', 'zone' => 'yourzone.com');

# Email address to send errors to (leave blank if you don't want emails)
$Email = "you@yourwebsite.com";

# Set your TimeZone
date_default_timezone_set('America/New_York');

# Program Logic
# Do not edit below this line unless you know what you're doing

if(!$ip=scrapeIP($Websites)) {
	doLog("Problem scraping IP.. ending script.");
	exit;
}

foreach($Zones as $Zone) {
	if(!$lines = checkZone($Zone)) {
		addZone($Zone);
	} else {
		updateZone($Zone, $lines);
	}
}

# Script Functions
function checkZone($zone) {

	global $username, $password, $whmUrl;
	
	# Setup this zone query
	$CheckQuery = "json-api/dumpzone?domain=".$zone['zone'];

	# Create Curl Object
	$curl = curl_init();		
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($curl, CURLOPT_HEADER,0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
	$header[0] = "Authorization: Basic " . base64_encode($username.":".$password) . "\n\r";
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_URL, $whmUrl.$CheckQuery);
	$result = curl_exec($curl);
	curl_close($curl);

	if($result == false) {
		doLog("Curl_Exec threw error \"".curl_error($curl)."\" for ".$whmUrl.$CheckQuery);
	} else {
		$results = json_decode($result, true);
		foreach($results['result'][0]['record'] AS $onerecord) {
			if($onerecord['name'] == $zone['name'].".".$zone['zone'].".") {
				$lines[] = array('Line' => $onerecord['Line'], 'IP' => $onerecord['address']);
			}
		}
		if(isset($lines)) {
			return $lines;
		} else {
			return NULL;
		}
	}
}

function addZone($zone) {

	global $username, $password, $whmUrl, $ip;

	# Setup this zone query
	$DnsQuery = "json-api/addzonerecord?zone=".$zone['zone']."&name=".$zone['name']."&address=".$ip."&type=A&class=IN";

	# Create Curl Object
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($curl, CURLOPT_HEADER,0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
	$header[0] = "Authorization: Basic " . base64_encode($username.":".$password) . "\n\r";
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_URL, $whmUrl.$DnsQuery);
	$result = curl_exec($curl);
	curl_close($curl);

	if ($result == false) {
		doLog("Curl_exec threw error \"" . curl_error($curl) . "\" for ".$whmUrl.$DnsQuery);
	} else {
		doLog("Added ".$zone['name'].".".$zone['zone']." pointing to ".$ip."\n");
	}

}

function updateZone($zone, $lines) {
	
	global $username, $password, $whmUrl, $ip;
	
	foreach($lines as $line) {
		
		# Is update required?
		if($ip != $line['IP']) {
		
			# Setup this zone query
			$UpdateQuery = "json-api/editzonerecord?domain=".$zone['zone']."&Line=".$line['Line']."&address=".$ip;
	
			# Create Curl Object
			$curl = curl_init();		
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_HEADER,0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
			$header[0] = "Authorization: Basic " . base64_encode($username.":".$password) . "\n\r";
			curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
			curl_setopt($curl, CURLOPT_URL, $whmUrl.$UpdateQuery);
			$result = curl_exec($curl);
			curl_close($curl);

			if($result == false) {
				doLog("Curl_Exec threw error \"".curl_error($curl)."\" for ".$whmUrl.$UpdateQuery);
			} else {
				doLog("Updated ".$zone['name'].".".$zone['zone']." pointing to ".$ip."\n");
			}
		
		} else {
				doLog("Skipped ".$zone['name'].".".$zone['zone']." as it's already pointing to ".$ip."\n");
		}
		
	}
	
}

function scrapeIP($urls) {
	
	$pattern = "/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/";
	
	if(is_array($urls)) {
		foreach($urls as $one) {
			preg_match($pattern, file_get_contents($one), $matches);
			if(count($matches) > 0) {
				return $matches[0];
			}
		}
	} else {
		preg_match($pattern, file_get_contents($urls), $matches);
		if(count($matches) > 0) {
			return $matches[0];
		}
	}
	return NULL;
	
}

function doLog($msg) {
	echo date("m/d/Y g:iA")." - ".$msg;
}
?>
