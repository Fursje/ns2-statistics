<?php

require __DIR__ . '/../../steam-condenser-php/vendor/autoload.php';
use SteamCondenser\Servers\MasterServer;
use SteamCondenser\Servers\SourceServer;

class serverstatistics {
	public $dev_mode = true;
	public  $module = "default";
	protected $public = array();
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

	protected $ServerCountryCount = array('total'=>array(), 'active_players'=>array(), 'player_count'=>array());
	protected $ServerOS = array();

	protected $masterlistQuery = "\\appid\\4920";
	#private $masterlistQuery = "\\appid\\4920\\empty\\1";



	public function __construct() {	
		#require_once(__DIR__."/grafana.class.php");
		#$this->gr = new grafana();
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

	// Server OS count
	protected function setServerOS($data) {
		if (array_key_exists('operatingSystem',$data['info']) && !empty($data['info']['operatingSystem'])) {
			switch ($data['info']['operatingSystem']) {
				case 'l':
					$os = 'Linux';
					break;
				case 'w':
					$os = 'Windows';
					break;
				default:
					$os = $data['info']['operatingSystem'];
			}
			if (!array_key_exists($os,$this->ServerOS)) { 
				$this->ServerOS[$os] = 0; 
			}
			$this->ServerOS[$os]++;
		}
	}
	protected function prepareServerOS() {
		foreach ($this->ServerOS as $k =>$v) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'server-os',$k , $v, $this->update_time);
		}
		$this->ServerOS = array();
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
		$this->ServerCountryCount = array('total'=>array(), 'active_players'=>array(), 'player_count'=>array());
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
		if (empty($data['info']['mapName'])) {
			return;
		}
		if ($data['info']['numberOfPlayers'] > 0) {
			if (!array_key_exists($data['info']['mapName'], $this->ServerMapCount)) { $this->ServerMapCount[$data['info']['mapName']] = 0; }
			$this->ServerMapCount[$data['info']['mapName']]+= 1;
		} else {
			if (!array_key_exists($data['info']['mapName'], $this->ServerMapCount)) { $this->ServerMapCount[$data['info']['mapName']] = 0; }
		}
	}
	protected function prepareServerMapCount() {
		foreach ($this->ServerMapCount as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'servermapcount',$sm , $pc, $this->update_time);

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
				$this->print_cli('info', 'Updating Masterlist');
				$this->getServers();
				$this->clearBlacklist();
				#$this->serverList = array(array('188.63.57.183','27016'));
				#$this->serverList = array(array('89.105.209.250','27021'),array('136.243.170.231','27036'));
				//85.14.226.223:27016
				#$this->serverList = array(array('136.243.170.231','27036'));
				#$this->serverList = array(array('85.14.226.223','27016'),array('89.105.209.250','27021'));
			}
			// Graph all servers
			foreach ($this->serverList as $tmp=>$srv) {
				
				if ($this->onBlacklist($srv[0],$srv[1])) { continue; }

				if (($serverDetails = $this->getDetails($srv[0],$srv[1])) !== FALSE) {
					$p_msg = sprintf("Found [%s:%s] [%s] [%s] [%d/%d]",$serverDetails['host'],$serverDetails['port'], $serverDetails['info']['serverName'], $serverDetails['info']['mapName'],$serverDetails['info']['numberOfPlayers'],$serverDetails['info']['maxPlayers']);
					$this->print_cli('info', $p_msg);
					
					// Direct graphable
					$this->prepareGraphdata($serverDetails);

					// Collect stats
					$this->setServerPlayerCount($serverDetails);
					$this->setServerMapCount($serverDetails);
					$this->setServerCountryCount($serverDetails);
					$this->setServerOS($serverDetails);

					$this->consoleStats($serverDetails);

					$this->setGameSpecificStats($serverDetails);
					$this->sendGraphdata();

				} else {
					$this->addBlacklist($srv[0],$srv[1]);
				}

				// Possible Monitoring
				$this->updateMonitoring($srv[0],$srv[1],$serverDetails);
			}
			// All stats gathered, prepare then send
	
			// Generic
			$this->prepareServerPlayerCount();
			$this->prepareServerMapCount();
			$this->prepareServerCountryCount();
			$this->prepareServerOS();


			// Game Specific
			$this->prepareGameSpecificStats();


			$this->sendGraphdata();

			$this->update_endTime = time();

			// End of our run, howlong do we need to sleep so we run every 5min.
			$time_taken = ($this->update_endTime - $this->update_startTime);

			// Stats thingies...
			$p_msg = sprintf("Stats: numberOfPlayers:[%d] maxPlayers[%d]", $this->consoleStats['numberOfPlayers'], $this->consoleStats['maxPlayers']);
			$this->print_cli('info', $p_msg);

			$p_msg = sprintf("update-run took: %d seconds.. TotalServers:[%d] Blacklisted:[%d]",$time_taken, count($this->serverList),count($this->serverBlackList));
			$this->print_cli('info', $p_msg);

			if ($time_taken >= $this->update_Interval) { $this->sleeptime = 0; }
			else {
				$this->sleeptime = ($this->update_Interval-$time_taken);
				$this->print_cli('info', "we need to sleep for: ".$this->sleeptime." seconds.");
			}
			
	}

	protected function updateMonitoring($host,$port,$details) {
		
	}

	protected function onBlacklist($host,$port) {
		$value = $host.":".$port;
		if (in_array($value,$this->serverBlackList)) {
			$this->print_cli('info', "skipping server $value because its on the blacklist");
			return True;
		} else { return False; }
	}		
	protected function addBlacklist($host,$port) {
		$value = $host.":".$port;
		if (!in_array($value,$this->serverBlackList)) {
			$this->serverBlackList[] = $value;
			$this->print_cli('info', "Server $value has been added to the blacklist");
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
			$this->print_cli('error', "Connection to graphite could not be established.");
		}

	}
	public function prepareGraphdata($data) {
		$host = str_replace(".","_",$data['host']);
		$port = $data['port'];
		$dtime = $this->update_time;

		/* Basic Server Info */
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'numberOfPlayers', $data['info']['numberOfPlayers'], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'maxPlayers', $data['info']['maxPlayers'], $dtime);

		// Not used atm
		#$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'botNumber', $data['info']['botNumber'], $dtime);
		#$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'networkVersion', $data['info']['networkVersion'], $dtime);
	
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
				$this->print_cli('error', "GetDetails() Caught exception: ".  $e->getMessage());
				$this->print_cli('error', "Retry count: ". $retry_count);
				usleep(200000);
				if ($retry_count >= $retry) {
					$this->print_cli('error', "Givingup on ".$host.":".$port);
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
			$this->print_cli('error', "GetDetails(Players) Caught exception: ".  $e->getMessage());
			$serverInfo['players'] = array();
		}

		$retry_count = 0;
		$retry+=2; // server is working.. so better try extra hard to get this value! :)
		if ($getRules == True) {
			while ($retry_count <= $retry) {
				try {
					$serverInfo['rules'] = $serverData->getRules();
					break;
				} catch (Exception $e) {
					$retry_count++;
					$this->print_cli('error', "GetDetails(rules) Caught exception: ". $e->getMessage());
					$this->print_cli('error', "Retry count: ". $retry_count);
					usleep(1000000);
					if ($retry_count >= $retry) {
						$serverInfo['rules'] = array();
						break;
					}
				}
			}
		}
		//debug
		#if ($host == '188.63.57.183' && $port == '27016') {
		#	print_r($serverInfo);
		#}		

		// simple error check
		if (empty($serverInfo['info']['mapName'])) {
			$this->print_cli('error', "empty map.. server in bogus state?");
			return False;
		}
		return $serverInfo;
	}
	
	public function getPlayers() {

	}

	protected function print_cli($severity, $message) {
		$message = sprintf("%s: %s\n",$severity,$message);
		print $message;
	}
}
?>
