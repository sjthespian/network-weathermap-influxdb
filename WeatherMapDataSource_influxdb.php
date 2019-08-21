<?php
// Datasource plugin for influxdb, target is defined as:
// influxdb:host:database:username:password:seriesin:seriesout:from:whereclause
// NOTE: due to config file limitations, spaces in the whereclause must be
//       escaped with %20, for example:
//       TARGET 8*influxdb:localhost:telegraf:::ifHCInOctets:ifHCOutOctets:ifXTable:"hostname"='myhost'%20AND%20"ifAlias"='{link:this:ifAlias}'
// 
// Derived from https://github.com/guequierre/php-weathermap-influxdb
//

class WeatherMapDataSource_influxdb extends WeatherMapDataSource {
	
	private $regex_pattern = "/^influxdb:([^:]*):([^:]*):([^:]*):([^:]*):([^:]*):([^:]*):([^:]*):(.*)$/";

	function Init(&$map)
	{
                if(function_exists('curl_init')) { return(TRUE); }
                wm_debug("INFLUXDB DS: curl_init() not found. Do you have the PHP CURL module?\n");

		return(TRUE);
	}


	function Recognise($targetstring)
	{
		if(preg_match($this->regex_pattern,$targetstring,$matches))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function ReadData($targetstring, &$map, &$item)
	{
		$data[IN] = NULL;
		$data[OUT] = NULL;
		$data_time = time();

		if(preg_match($this->regex_pattern,$targetstring,$matches))
		{
			$host = $matches[1];
			$database = $matches[2];
			$username = urlencode($matches[3]);
			$password = urlencode($matches[4]);
			$seriesin = $matches[5];
			$seriesout = $matches[6];
			$from = $matches[7];
			$where = $matches[8];

			# Fix whitespace in where
			$where = str_replace("%20", " ", $where);

			# Don't use derivitive for fOperStatus
			if ($seriesin == "ifOperStatus") {
				$query = "select mean($seriesin) AS \"In\",mean($seriesout) as \"Out\" from $from where ($where) and time > now() - 5m";
			} else {
				$query = "select derivative(mean($seriesin), 1s) AS \"In\",derivative(mean($seriesout), 1s) as \"Out\" from $from where ($where) and time > now() - 5m GROUP BY time(5m)";
			}

			# InfluxDB API: https://docs.influxdata.com/influxdb/v1.7/guides/querying_data/
			$url = "http://$host:8086/query?db=$database&epoch=s&q=" . urlencode($query);
			$ch = curl_init($url);
			curl_setopt_array($ch, array(
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_TIMEOUT => 3,
                        ));
			if ($username and $password) {
				curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			}
			$buffer = curl_exec($ch);
                        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                        if ($status != 200) {
                                wm_debug("INFLUXDB DS: Got HTTP code $status from InfluxDB\n");
				curl_close($ch);
                                return;
                        }
			curl_close($ch);

			$decoded = json_decode($buffer);
			# If no results returned, return NULL
			if (array_key_exists('series', $decoded->results[0])) {
				$data[IN] = round($decoded->results[0]->series[0]->values[0][1]);
				$data[OUT] = round($decoded->results[0]->series[0]->values[0][2]);
			} else {
				$data[IN] = NULL;
				$data[OUT] = NULL;
			}
		}
		return( array($data[IN], $data[OUT], $data_time) );
	}
}
?>
