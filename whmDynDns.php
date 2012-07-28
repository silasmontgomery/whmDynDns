<?php
/*
WHM Dynamic DNS Updater v2.1.0
By Silas Montgomery
Website: http://reticent.net
Email: nomsalis@reticent.net)
*/

// Classes
require_once("class.ZoneUpdater.php");
require_once("class.Scraper.php");
require_once("class.Logger.php");

// Configuration
require_once("whmDynDns.config.php");

// Logic
try
{
	$scraper = new Scraper();
	$scraper->SetUrls($websites);
	if($ip = $scraper->GetIp())
	{
		$updater = new ZoneUpdater();
        $updater->SetUsername($username);
        $updater->SetPassword($password);
        $updater->SetUrl($whmUrl);
		$updater->SetIp($ip);
		$updater->SetZones($zones);
		$updater->Update();
	}
}
catch(exception $e)
{
	Logger::Write($e);
}
?>
