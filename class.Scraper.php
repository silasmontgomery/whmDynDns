<?php
/*
WHM Dynamic DNS Updater v2.1.0
By Silas Montgomery
Website: http://reticent.net
Email: nomsalis@reticent.net)
*/

class Scraper
{
	private $urls = array();

	public function SetUrls($url)
	{
		$this->urls = $url;
	}

	public function GetUrls()
	{
		return $this->urls;
	}

	public function GetIp()
	{
		return $this->ScrapePage();
	}

	private function ScrapePage()
	{
		$pattern = "/(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/";

		if(count($this->urls) > 0) 
		{
            $matches = array();
			foreach($this->urls as $oneurl)
			{
				preg_match($pattern, file_get_contents($oneurl), $matches);
				if(count($matches) > 0)
				{
					return $matches[0];
				}
			}
            Logger::Write("Failed to scape IP.");
            return false;
		}
		else
		{
			Logger::Write("Need at least one URL in order to scrape IP.");
			return false;
		}
	}
}
?>
