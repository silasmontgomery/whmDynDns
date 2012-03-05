<?php

# WHM Dynamic DNS Update Script v2
# By Silas Montgomery
# Website: http://slimtechnologies.com
# Email: silas@slimtechnologies.com)

# Configuration
require_once("whmDynDns.config.php");

# Program Logic
# Do not edit below this line unless you know what you're doing

if(!$ip=scrapeIP($Websites)) {
	doLog("Problem scraping IP.. ending script");
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
	# Setup this zone query
	$query = "json-api/dumpzone?domain=".$zone['zone'];
	$result = CurlRequest($query);

	if($result != false) {
		$results = json_decode($result, true);
		foreach($results['result'][0]['record'] AS $onerecord) {
			if($onerecord['name'] == $zone['name'].".".$zone['zone']."." &&
				$onerecord['type'] == "A") {
				$lines[] = array('Line' => $onerecord['Line'], 'IP' => $onerecord['address'], 'TTL' => $onerecord['ttl']);
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
	global $ip;

	# Setup this zone query
	$query = "json-api/addzonerecord?zone=".$zone['zone']."&name=".$zone['name']."&address=".$ip."&type=A&class=IN".
		(hasValidTTL($zone) ? "&ttl=".$zone['ttl'] : "");
	$result = CurlRequest($query);

	if ($result != false) {
		doLog("Added ".$zone['name'].".".$zone['zone']." pointing to ".$ip.(hasValidTTL($zone) ? " (TTL: ".$zone['ttl'].")" : ""));
	}
}

function updateZone($zone, $lines) {
	global $ip;

	foreach($lines as $line) {
		# Is update required?
		if($ip != $line['IP'] || (isset($zone['ttl']) && $zone['ttl'] != $line['TTL'])) {
		
			# Setup this zone query
			$query = "json-api/editzonerecord?domain=".$zone['zone']."&Line=".$line['Line']."&address=".$ip.
				(hasValidTTL($zone) ? "&ttl=".$zone['ttl'] : "");
			$result = CurlRequest($query);

			if($result != false) {
				doLog("Updated ".$zone['name'].".".$zone['zone']." pointing to ".$ip.(hasValidTTL($zone) ? " (TTL: ".$zone['ttl'].")" : ""));
			}
		
		} else {
			doLog("Skipped ".$zone['name'].".".$zone['zone']." as it's already pointing to ".$ip." (TTL: ".$line['TTL'].")");
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

function CurlRequest($query) {
	global $username, $password, $whmUrl;
	
	# Create Curl Object
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($curl, CURLOPT_HEADER,0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
	$header[0] = "Authorization: Basic " . base64_encode($username.":".$password) . "\n\r";
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_URL, $whmUrl.$query);
	$result = curl_exec($curl);
	curl_close($curl);
	
	if($result == false) {
		doLog("Curl_Exec threw error \"".curl_error($curl)."\" for ".$whmUrl.$query);
	}
	
	return $result;
}

function hasValidTTL($zone) {
	$maxTTL = 2147483647; // According to RFC2181 http://www.rfc-editor.org/rfc/rfc2181.txt
	if(isset($zone['ttl'])) {
		$ttl = $zone['ttl'];
		if(is_numeric($ttl) && $ttl > 0 && $ttl <= $maxTTL) {
			return true;
		}
	}
	return false;
}

function doLog($msg) {
	echo date("m/d/Y g:iA")." - ".$msg."\n";
}
?>