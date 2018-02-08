<?php
/*
WHM Dynamic DNS Updater v2.3.0
By Silas Montgomery
Website: http://reticent.net
Email: nomsalis@reticent.net)
*/

class ZoneUpdater
{
	private $zones;
	private $username;
	private $password;
	private $url;
	private $ip;

	public function SetUsername($username)
	{
		$this->username = $username;
	}

	public function GetUsername()
	{
		return $this->username;
	}

	public function SetPassword($password)
	{
		$this->password = $password;
	}

	public function GetPassword()
	{
		return $this->password;
	}

	public function SetUrl($url)
	{
		$this->url = $url;
	}

	public function GetUrl()
	{
		return $this->url;
	}

	public function SetZones($zones)
	{
		$this->zones = $zones;
	}

	public function GetZones()
	{
		return $this->zones;
	}

	public function SetIp($ip)
	{
		$this->ip = $ip;
	}

	public function GetIp()
	{
		return $this->ip;
	}

	public function Update()
	{
		foreach($this->zones as $zone)
		{
			$host_ip = gethostbyname($this->FullRecordName($zone));
			if($host_ip != $this->ip) {
				$lines = $this->CheckZone($zone);

				if(!empty($lines))
				{
					$this->UpdateZone($zone, $lines);
				}
				else
				{
					$this->AddZone($zone);
				}
			} else {
				Logger::Write("Skipped ".$this->FullRecordName($zone)." as it's already pointing to ".$this->ip, LOG_LEVEL_INFO);
			}
		}
	}

	private function CheckZone($zone)
    {
		$lines = array();
		$query = "json-api/dumpzone?domain=".$zone['zone'];
		$result = $this->CurlRequest($query);

		if($result)
        {
			foreach($result['record'] AS $oneRecord)
            {
				if(isset($oneRecord['name']) && ($oneRecord['name'] == $this->FullRecordName($zone)."." && $oneRecord['type'] == "A"))
                {
					$lines[] = array('Line' => $oneRecord['Line'], 'IP' => $oneRecord['address'], 'TTL' => $oneRecord['ttl']);
				}
			}
		}

		return $lines;
	}

	private function AddZone($zone)
    {
		$query = "json-api/addzonerecord?zone=".$zone['zone']."&name=".(strlen($zone['name']) > 0 ? $zone['name'] : $zone['zone'].".")
			."&address=".$this->ip."&type=A&class=IN".($this->HasValidTTL($zone) ? "&ttl=".$zone['ttl'] : "");
		$result = $this->CurlRequest($query);

        $updateResult = "Added ";
		$logLevel = LOG_LEVEL_CHANGE;
        if($result['status'] != 1) {
            $updateResult = "Problem updating";
			$logLevel = LOG_LEVEL_ERROR;
		}

        Logger::Write($updateResult.$this->FullRecordName($zone)." pointing to ".$this->ip.($this->HasValidTTL($zone) ? " (TTL: ".$zone['ttl'].")" : ""), $logLevel);
	}

	private function UpdateZone($zone, $lines)
    {
		foreach($lines as $line)
        {
			if($this->ip != $line['IP'] || (isset($zone['ttl']) && $zone['ttl'] != $line['TTL']))
            {
				$query = "json-api/editzonerecord?domain=".$zone['zone']."&Line=".$line['Line']."&address=".$this->ip.
					($this->HasValidTTL($zone) ? "&ttl=".$zone['ttl'] : "");
				$result = $this->CurlRequest($query);

                $updateResult = "Updated ";
				$logLevel = LOG_LEVEL_CHANGE;
				if($result['status'] != 1) {
                    $updateResult = "Problem updating";
					$logLevel = LOG_LEVEL_ERROR;
				}

                Logger::Write($updateResult.$this->FullRecordName($zone)." pointing to ".$this->ip.($this->HasValidTTL($zone) ? " (TTL: ".$zone['ttl'].")" : ""), $logLevel);
			}
            else
            {
				Logger::Write("Skipped ".$this->FullRecordName($zone)." as it's already pointing to ".$this->ip." (TTL: ".$line['TTL'].")", LOG_LEVEL_INFO);
			}
		}
	}

	private function CurlRequest($query)
    {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_HEADER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$header[0] = "Authorization: Basic " . base64_encode($this->username.":".$this->password) . "\n\r";
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl, CURLOPT_URL, $this->url.$query);
		$response = curl_exec($curl);
		curl_close($curl);

		if(!$response)
        {
            $results = array("status" => 0, "statusmsg" => curl_error($curl));
		}
        else
        {
            $response = json_decode($response, true);
            $results = $response['result'][0];
        }

		return $results;
	}

	private function HasValidTTL($zone)
    {
		$maxTTL = 2147483647; // According to RFC2181 http://www.rfc-editor.org/rfc/rfc2181.txt
		if(isset($zone['ttl']))
        {
			$ttl = $zone['ttl'];
			if(is_numeric($ttl) && $ttl > 0 && $ttl <= $maxTTL)
				return true;
		}

		return false;
	}

	private function FullRecordName($zone)
    {
		return (isset($zone['name']) && strlen($zone['name']) > 0 ? $zone['name']."." : "").$zone['zone'];
	}
}
?>
