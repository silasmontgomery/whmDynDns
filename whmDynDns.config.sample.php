<?php
/*
WHM Dynamic DNS Update Script v2.3.0
By Silas Montgomery
Website: http://reticent.net
Email: nomsalis@reticent.net)
*/

// Update this file with your own configuration and rename it to whmDynDns.config.php

// Set Log Level (0 = errors only, 1 = IP changes only, 2 = Everything)
const LOG_LEVEL =  1;

// Set your TimeZone
date_default_timezone_set('America/New_York');

// Set this to your WHM Admin login
$username = 'YourUsername';

// Set this to your WHM Admin password
$password = 'YourPassword';

// Set this to your WHM Login URL (2087 is the default WHM SSL port)
$whmUrl = 'https://yourwhmwebsite.com:2087/';

// Add one or more websites to scrape the public IP from (array)
$websites[] = 'http://www.differentwebsite.com/ip.php';
$websites[] = 'http://ipchicken.com';

/*
Add your host names here (one or more as array).
'name' is the subdomain, if left empty, it will add/update a record for the root domain itself (ex, yourdomain.com.)
'zone' is the domain
'ttl' is the time to live of the record, if left empty, iit will be set as the DNS server default
*/
$zones[] = array('name' => 'sub1', 'zone' => 'yourdomain.com');
$zones[] = array('name' => 'sub2', 'zone' => 'yourdomain.com', 'ttl' => 900);
$zones[] = array('name' => '', 'zone' => 'yourdomain.com');
?>
