<?php

# WHM Dynamic DNS Update Script v2
# By Silas Montgomery
# Website: http://slimtechnologies.com
# Email: silas@slimtechnologies.com)

# Update this file with your own configuration and rename it to whmDynDns.config.php

# Set this to your WHM Admin login
$username = "YourUsername";

# Set this to your WHM Admin password
$password = "YourPassword";

# Set this to your WHM Login URL (2087 is the default WHM SSL port)
$whmUrl = "https://yourwebsite.com:2087/";

# Add one or more websites to scrape the public IP from (one or more as array)
$Websites[] = "http://www.yourwebsite.com/ip.php";
$Websites[] = "http://www.ipchicken.com";

# Add your host names here (one or more as array).
# 'name' is the subdomain
# 'zone' is the domain
# 'ttl' is the time to live of the record (if left empty, iit will be set as the DNS server default)
$Zones[] = array('name' => 'sub1', 'zone' => 'yourzone.com');
$Zones[] = array('name' => 'sub2', 'zone' => 'yourzone.com', 'ttl' => 300);

# Set your TimeZone
date_default_timezone_set('America/New_York');

?>