<?php

require __DIR__ . '/vendor/autoload.php';
use SteamCondenser\Servers\MasterServer;
use SteamCondenser\Servers\SourceServer;

class serverstatistics {
	public $dev_mode = true;
	public  $module = "default";
	protected $serverList = array();
	protected $serverList_CacheTime = 0;
	protected $serverList_UpdateInterval = 1800;

	protected $serverBlackList = array();

	protected $graphite_data = array();
	protected $graphite_host = "localhost";
	protected $graphite_port = "2003";
	
	protected $update_time = 0;
	protected $sleeptime = 0;

	protected $update_startTime = 0;
	protected $update_endTime = 0;
	protected $update_Interval = 300;

	// Generic
	protected $ServerPlayerCount = array();
	protected $ServerMapCount = array();

	protected $ServerCountryCount = array();

	// ns2 specific
	#protected $consoleStats = array();
	#protected $ServerVersionCount = array();
	#protected $ServerModCount = array();


	public $masterlistQuery = "\\appid\\4920";
	#private $masterlistQuery = "\\appid\\4920\\empty\\1";



	public function __construct() {	
		require_once(__DIR__."/grafana.class.php");
		$this->gr = new grafana();
	}
	
	public function __destruct() { 
		print "-- shutting down --\n"; 
	}

	public function run_daemon() {
		while (true) {
			$this->run_main();
			sleep($this->sleeptime);
		}
	}

	public function run_once() {
		$this->run_main();
	}

	public function consoleStats($data) {
		if (count($this->consoleStats) == 0) {
			$this->consoleStats['numberOfPlayers'] = 0;
			$this->consoleStats['maxPlayers'] = 0;
			$this->consoleStats['mapName'] = array();
		}
		$this->consoleStats['numberOfPlayers'] += $data['info']['numberOfPlayers'];
		$this->consoleStats['maxPlayers'] += $data['info']['maxPlayers'];
		if ($data['info']['numberOfPlayers'] > 0) {
			@$this->consoleStats['mapName'][$data['info']['mapName']] += 1;
		}
	}

	// Servers by Country
	protected function setServerCountryCount($data) {
		$country_code = geoip_country_code3_by_name($data['host']);

		if (!array_key_exists($country_code, $this->ServerCountryCount['total'])) { $this->ServerCountryCount['total'][$country_code] = 0; }
		$this->ServerCountryCount['total'][$country_code]++;
		
		if ($data['info']['numberOfPlayers'] > 0) {
			if (!array_key_exists($country_code, $this->ServerCountryCount['active_players'])) { $this->ServerCountryCount['active_players'][$country_code] = 0; }
			$this->ServerCountryCount['active_players'][$country_code]++;
		}

		if ($data['info']['numberOfPlayers'] > 0) {
			if (!array_key_exists($country_code, $this->ServerCountryCount['player_count'])) { $this->ServerCountryCount['player_count'][$country_code] = 0; }
			$this->ServerCountryCount['player_count'][$country_code]+= $data['info']['numberOfPlayers'];
		}

	}

	protected function prepareServerCountryCount() {
		foreach ($this->ServerCountryCount['total'] as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'servercountrycount',$sm , $pc, $this->update_time);
		}
		foreach ($this->ServerCountryCount['active_players'] as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'servercountryactivecount',$sm , $pc, $this->update_time);
		}
		foreach ($this->ServerCountryCount['player_count'] as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'servercountryplayercount',$sm , $pc, $this->update_time);
		}
		$this->ServerCountryCount = array();
	}

	// Server Player Count
	protected function setServerPlayerCount($data) {
		if (!array_key_exists($data['info']['maxPlayers'], $this->ServerPlayerCount)) { $this->ServerPlayerCount[$data['info']['maxPlayers']] = 0; }
		$this->ServerPlayerCount[$data['info']['maxPlayers']]+= $data['info']['numberOfPlayers'];
	}
	protected function prepareServerPlayerCount() {
		foreach ($this->ServerPlayerCount as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.slot_%d %d %d",$this->module,'serverplayercount',$sm , $pc, $this->update_time);

		}
		$this->ServerPlayerCount = array();
	}

	// Server Map Count
	protected function setServerMapCount($data) {
		if ($data['info']['numberOfPlayers'] > 0) {
			if (!array_key_exists($data['info']['mapName'], $this->ServerMapCount)) { $this->ServerMapCount[$data['info']['mapName']] = 0; }
			$this->ServerMapCount[$data['info']['mapName']]+= 1;
		} else {
			if (!array_key_exists($data['info']['mapName'], $this->ServerMapCount)) { $this->ServerMapCount[$data['info']['mapName']] = 0; }
		}
	}
	protected function prepareServerMapCount() {
		foreach ($this->ServerMapCount as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.map_%s %d %d",$this->module,'servermapcount',$sm , $pc, $this->update_time);

		}
		$this->ServerMapCount = array();
	}

	protected function setGameSpecificStats($data) {}
	
	protected function prepareGameSpecificStats() {}

	public function run_main() {
			$this->update_startTime = $this->update_time = time();
			$this->consoleStats = array();

			// Update master list
			if ((time() - $this->serverList_CacheTime) > $this->serverList_UpdateInterval) {
				print "Debug: Updating masterlist\n";
				$this->getServers();
				$this->clearBlacklist();
			}
			// $this->serverList[] = array('89.105.209.250','27021');

			// Graph all servers
			foreach ($this->serverList as $tmp=>$srv) {
				
				if ($this->onBlacklist($srv[0],$srv[1])) { continue; }

				if (($serverDetails = $this->getDetails($srv[0],$srv[1])) !== FALSE) {
					print sprintf("Debug: Found [%s] [%s] [%d/%d]\n",$serverDetails['info']['serverName'], $serverDetails['info']['mapName'],$serverDetails['info']['numberOfPlayers'],$serverDetails['info']['maxPlayers']);
					
					// Direct graphable
					$this->prepareGraphdata($serverDetails);

					// Collect stats
					$this->setServerPlayerCount($serverDetails);
					$this->setServerMapCount($serverDetails);
					$this->setServerCountryCount($serverDetails);
					$this->consoleStats($serverDetails);

					$this->setGameSpecificStats($serverDetails);
					$this->sendGraphdata();

				} else {
					$this->addBlacklist($srv[0],$srv[1]);
				}
			}
			// All stats gathered, prepare then send
	
			// Generic
			$this->prepareServerPlayerCount();
			$this->prepareServerMapCount();
			$this->prepareServerCountryCount();

			// Game Specific
			$this->prepareGameSpecificStats();


			$this->sendGraphdata();

			$this->update_endTime = time();

			// End of our run, howlong do we need to sleep so we run every 5min.
			$time_taken = ($this->update_endTime - $this->update_startTime);

			// Stats thingies...
			print sprintf("Debug: Stats: numberOfPlayers:[%d] maxPlayers[%d]\n", $this->consoleStats['numberOfPlayers'], $this->consoleStats['maxPlayers']);
			#print sprintf("Debug: Current Maps: \n");
			#foreach ($this->consoleStats['mapName'] as $map=>$players) {
			#	print "Debug: $map => $players\n";
			#}
			print sprintf("Debug: update-run took: %d seconds.. TotalServers:[%d] Blacklisted:[%d]\n",$time_taken, count($this->serverList),count($this->serverBlackList));

			if ($time_taken >= $this->update_Interval) { $this->sleeptime = 0; }
			else {
				$this->sleeptime = ($this->update_Interval-$time_taken);
				print "Debug: we need to sleep for: ".$this->sleeptime." sec\n\n";
			}
			
	}



	protected function onBlacklist($host,$port) {
		$value = $host.":".$port;
		if (in_array($value,$this->serverBlackList)) {
			print "Debug: skipping server $value because its on the blacklist\n";
			return True;
		} else { return False; }
	}		
	protected function addBlacklist($host,$port) {
		$value = $host.":".$port;
		if (!in_array($value,$this->serverBlackList)) {
			$this->serverBlackList[] = $value;
			print "Debug: Server $value has been added to the blacklist\n";
		}
	}
	protected function clearBlacklist() {
		$this->serverBlackList = array();
	}

	public function sendGraphdata() {
		if ($this->dev_mode == True) {
			print_r($this->graphite_data);
			$this->graphite_data = array();
			return;
		}

		if (($conn = fsockopen($this->graphite_host, $this->graphite_port)) !== FALSE) {
			foreach ($this->graphite_data as $line) {
				fwrite($conn, $line."\n");
			}
			fclose($conn);
			$this->graphite_data = array();
		} else {
			print "Debug: Connection error..\n";
		}

	}
	public function prepareGraphdata($data) {
		$host = str_replace(".","_",$data['host']);
		$port = $data['port'];
		$dtime = $this->update_time;

		/* Basic Server Info */
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'networkVersion', $data['info']['networkVersion'], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'numberOfPlayers', $data['info']['numberOfPlayers'], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'maxPlayers', $data['info']['maxPlayers'], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'botNumber', $data['info']['botNumber'], $dtime);
	
	}
	
	public function getServers() {
		$master = new MasterServer(MasterServer::SOURCE_MASTER_SERVER);
		$this->serverList = $servers = $master->getServers(MasterServer::REGION_ALL,$this->masterlistQuery , true );
		$this->serverList_CacheTime = time();
	}

	public function getDetails($host,$port,$retry = 3, $getPlayers = False, $getRules = True) {
		$serverInfo = array();
		$serverInfo['host'] = $host;
		$serverInfo['port'] = $port;
		$serverData = new SourceServer($host,$port);
		$retry_count = 0;

		while ( $retry_count <= $retry) {
			try {
				$serverInfo['info'] = $serverData->getServerInfo();
				break;

			} catch (Exception $e) {
				$retry_count++;
				echo 'Caught exception: ',  $e->getMessage(), "\n";
				echo 'Retry count: ', $retry_count."\n";
				usleep(200000);
				if ($retry_count >= $retry) {
					echo 'Debug: Givingup on '.$host.":".$port."\n";
					return False; 
				}
			}
		}
		try {
			if ($getPlayers == True) {
				$tmp_players = $serverData->getPlayers();
				foreach ($tmp_players as $player) {
					$pd = array();
					$pd['nick'] = $player->getName();
					$pd['score'] = $player->getScore();
					$pd['connection_time'] = round($player->getConnectTime(),0);
					$serverInfo['players'][] = $pd;
				}
			}
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			$serverInfo['players'] = array();
		}

		try {
			if ($getRules == True) {
				$serverInfo['rules'] = $serverData->getRules();
			}	
		} catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			$serverInfo['rules'] = array();
		}


		return $serverInfo;
	}
	
	public function getPlayers() {

	}

}
?>
