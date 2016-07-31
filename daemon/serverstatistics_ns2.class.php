<?php

require __DIR__. '/serverstatistics.class.php';

class serverstatistics_ns2 extends serverstatistics {

	public  $module = "ns2";

	protected $consoleStats = array();

	protected $ServerModCount = array();
	protected $ServerVersionCount = array();
	protected $serverByCategory = array();
	protected $serversModded = array('Modded'=>0,'Vanilla'=>0);
	protected $serverPingData = array();
	protected $UWEMods = array();
	protected $UWEMods_lastcheck = 0;
	protected $SkillbyServerSize = array();

	public $masterlistQuery = "\\appid\\4920";
	#private $masterlistQuery = "\\appid\\4920\\empty\\1";

	protected $monitoring = False;
	protected $jsonData = array('servers'=>array(),'hosts'=>array(),'last_update'=>0);
	protected $grafana = False;

	protected function setGameSpecificStats($data) {
		$this->prepareNS2ServerSpecific($data);
		$this->setServerModCount($data);
		$this->setServerVersionCount($data);
		$this->sortServerbyCategory($data);
		$this->setServersModded($data);
		$this->setServerHiveSkillAvg($data);

		$this->gatherData($data);
	}
	
	protected function prepareGameSpecificStats() {
		$this->prepareServerModCount();
		$this->prepareServerVersionCount();
		$this->prepareServerbyCategory();
		$this->prepareServersModded();
		$this->prepareServerHiveSkillAvg();

		$this->prepareUweForcedMods();

		$this->createPingStatistics();

		$this->saveData();

	}

	protected function saveData() {
		$this->createDashboard();
		$this->jsonData['last_update'] = date("r",$this->update_time);
		$jsonData = json_encode($this->jsonData);
		file_put_contents("site_data.json",$jsonData);
		$this->jsonData = array('servers'=>array(),'hosts'=>array(),'last_update'=>0);
	}

	protected function createDashboard() {
		if (!is_object($this->grafana)) {
			require_once(__DIR__.'/grafana.class.php');
			$this->grafana = new grafana();
		}		


		// Create Info/Perf Dashboard
		$id_counter = 1;
		$rows = array();
		foreach ($this->jsonData['servers'] as $host => $value) {
			$panels = array();

			$this->jsonData['servers'][$host]['graphs']['info_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_ServerInfo($value['serverName'],$value['host'],$value['port'],$id_counter );
			$id_counter++;

			$this->jsonData['servers'][$host]['graphs']['perf_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_ServerPerformance($value['serverName'],$value['host'],$value['port'],$id_counter);
			$id_counter++;

			$rows[] = $this->grafana->createRow($host, 250, $panels);
		}

		$dashboard_info = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Servers (autogen)','natural-selection-2-servers-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_info);

		// Create Dashboard players by IP
		$id_counter = 1;
		$rows = array();
		foreach ($this->jsonData['servers'] as $host => $value) {
			if (array_key_exists($value['host'],$this->jsonData['hosts'])) { continue; }
			$panels = array();

			$this->jsonData['hosts'][$value['host']]['graphs']['players_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_HostPlayersTimeShift($value['host'],$value['host'],$id_counter );
			$id_counter++;

			$rows[] = $this->grafana->createRow($value['host'], 250, $panels);
		}

		$dashboard_players = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Server - Players (autogen)','natural-selection-2-server-players-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_players);

		// Create Dashboard players by IP & Port
		$id_counter = 1;
		$rows = array();
		foreach ($this->jsonData['servers'] as $host => $value) {
			$panels = array();

			$this->jsonData['hosts'][$value['host']]['graphs']['playersport_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_HostPlayersTimeShift($value['host'],$value['host'],$id_counter,$value['port']);
			$id_counter++;

			$rows[] = $this->grafana->createRow($value['host'], 250, $panels);
		}

		$dashboard_playersport = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Server - Playersport (autogen)','natural-selection-2-server-playersport-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_playersport);

		// Create Dashboard Smokeping
		$id_counter = 1;
		$rows = array();
		foreach ($this->jsonData['servers'] as $host => $value) {
			if ( array_key_exists($value['host'],$this->jsonData['hosts']) && array_key_exists('smokeping_id',$this->jsonData['hosts'][$value['host']]['graphs']) ) { continue; }
			$panels = array();

			$this->jsonData['hosts'][$value['host']]['graphs']['smokeping_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_Smokeping($value['host'],$value['host'],$id_counter );
			$id_counter++;

			$rows[] = $this->grafana->createRow($value['host'], 250, $panels);
		}

		$dashboard_smokeping = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Server - Smokeping (autogen)','natural-selection-2-server-smokeping-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_smokeping);

		// Create Dashboard Server Hivescore
		$id_counter = 1;
		$rows = array();
		foreach ($this->jsonData['servers'] as $host => $value) {
			$panels = array();

			$this->jsonData['servers'][$host]['graphs']['hivescore_id'] = $id_counter;
			$panels[] = $this->grafana->createPanel_ServerHivescore($value['serverName'],$value['host'],$value['port'],$id_counter );
			$id_counter++;

			$rows[] = $this->grafana->createRow($host, 250, $panels);
		}

		$dashboard_serverhivescore = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Server - Hivescore (autogen)','natural-selection-2-server-hivescore-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_serverhivescore);


		// some sort of change check so we dont upload a dash every 5min :P
		if ($this->dev_mode == False) {
			$this->grafana->sendDashboard($dashboard_info['meta']['slug'].".json");
			$this->grafana->sendDashboard($dashboard_players['meta']['slug'].".json");
			$this->grafana->sendDashboard($dashboard_playersport['meta']['slug'].".json");
			$this->grafana->sendDashboard($dashboard_smokeping['meta']['slug'].".json");
			$this->grafana->sendDashboard($dashboard_serverhivescore['meta']['slug'].".json");
		}
	}

	protected function gatherData($data) {
		$key = sprintf("%s:%s",$data['host'],$data['port']);
		$tags = explode("|",$data['info']['serverTags']);
		$this->jsonData['servers'][$key] = array(
			'host' => $data['host'],
			'port' => (int) $data['port'],
			'serverName' => utf8_encode($data['info']['serverName']),
			'mapName' => $data['info']['mapName'],
			'maxPlayers' => (int) $data['info']['maxPlayers'],
			'numberOfPlayers' => (int) $data['info']['numberOfPlayers'],
			'dedicated' => $data['info']['dedicated'],
			'serverPort' => (int) $data['info']['serverPort'],
			'version' => (int) $tags[0],
			'graphs' => array('info_id'=>0,'perf_id'=>0),
			'country' => strtolower(geoip_country_code_by_name($data['host'])),
		);
	}


	/* Monitoring */
	protected function createPingStatistics() {
		$this->serverPingData = array();

		//  fping -C 20 -q -B1 -r1 -i10 -f /tmp/srv_list
		$uniq_ips = array();
		$tmp_list = "";
		foreach ($this->serverList as $tmp_k => $tmp_v) {
			if (!in_array($tmp_v[0],$uniq_ips)) { $uniq_ips[] = $tmp_v[0]; }
		}
		$ns2servers_ping_file = dirname(__FILE__)."/ns2servers_ping.list";
		$servers = implode("\n",$uniq_ips);
		file_put_contents($ns2servers_ping_file,$servers);
		$cli_cmd = sprintf("fping -C 10 -q -B1 -r1 -i10 -f %s 2>&1",$ns2servers_ping_file);
		exec($cli_cmd, $return_data, $return_code);
		$this->print_cli("debug", "getPingStatistics: return_code[".$return_code."]");
		#if ($return_code == 0) {
			foreach ($return_data as $line) {
				$tmp2 = explode(":",$line);
				$tmp3 = explode(" ",trim($tmp2[1]));
				
				$ip = trim($tmp2[0]);
				$data = $this->_parsePingResults($tmp3);
				$this->serverPingData[$ip] = $data;
			}
		#}

		foreach ($this->serverPingData as $ipa => $value) {
			$ip = str_replace(".","_",$ipa);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %.2f %d",$this->module,'smokeping',$ip , "min", $value['min'], $this->update_time);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %.2f %d",$this->module,'smokeping',$ip , "max", $value['max'], $this->update_time);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %.2f %d",$this->module,'smokeping',$ip , "avg", $value['avg'], $this->update_time);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,'smokeping',$ip , "loss", $value['loss'], $this->update_time);
		}

	}
	protected function _parsePingResults($data) {
		$return = array(
			"min" => 0,
			"max" => 0,
			"avg" => 0,
			"loss" => 0
		);
		$count = count($data);
		$total = 0;
		$loss = 0;
		foreach ($data as $item) {
			if (is_numeric($item)) {
				if ($return['min'] == 0) { $return['min'] = $item; }
				if ($item < $return['min']) { $return['min'] = $item; }
				if ($item > $return['max']) { $return['max'] = $item; }
				$total += $item;
			} else {
				$loss++;
			}
		}
		if ($total > 0) {
			$return['avg'] = $total / ($count-$loss);
		}
		$return['loss'] = 100 / $count * $loss;
		return $return;
	}
	/*
	private function _loadMonitoring() {
		if (!is_object($this->monitoring)) {
			require_once(__DIR__.'/monitoring_ns2.class.php');
			$this->monitoring = new monitoring_ns2();
		}
	}

	protected function updateMonitoring($host,$port,$details) {
		$this->_loadMonitoring();
		$monitoring->update($host,$port,$details,$this->update_time);
	}	
	*/

	protected function getHiveWhitelist() {
		// http://hive.naturalselection2.com/api/get/whitelistedServers/
		$tdiff = time() - $this->UWEMods_lastcheck;
		if ($tdiff > 1800) {
			$this->print_cli("info","getHiveWhitelist: checking for new forced mods.");
			// URL: http://skymarshal.naturalselection2.com/mods/
			if (FALSE !== ($tmp_m = file_get_contents("http://hive.naturalselection2.com/api/get/whitelistedServers/"))) {
				$tmp1 = trim($tmp_m);
				$mods = explode(" ",$tmp1);
				if (count($mods) > 0) {
					$this->UweMods = $mods;
					$this->UWEMods_lastcheck = time();
					$this->print_cli("info","getHiveWhitelist: found ".count($mods)." mods.");
				}
			} else {
				$this->UWEMods = array();
				$this->print_cli("info","getHiveWhitelist: something went wrong; clearing UWEMods.");
			}
		}
	}

	/* Game Specific Stats Functions */

	// Modded vs unmodded
	protected function getUweForcedMods() {
		// refresh every 30min
		$tdiff = time() - $this->UWEMods_lastcheck;
		if ($tdiff > 1800) {
			$this->print_cli("info","getUweForcedMods: checking for new forced mods.");
			// URL: http://skymarshal.naturalselection2.com/mods/
			if (FALSE !== ($tmp_m = file_get_contents("http://skymarshal.naturalselection2.com/mods/"))) {
				$tmp1 = trim($tmp_m);
				$mods = explode(" ",$tmp1);
				if (count($mods) > 0) { 
					$this->UweMods = $mods;
					$this->UWEMods_lastcheck = time();
					$this->print_cli("info","getUweForcedMods: found ".count($mods)." mods.");
				}
			} else {
				$this->UWEMods = array();
				$this->print_cli("info","getUweForcedMods: something went wrong; clearing UWEMods.");
			}
		}
	}
	protected function setServersModded($data) {
		$this->getUweForcedMods();
		$found_mods = (int) 0;
		foreach ($data['rules'] as $k => $v) {
			if (preg_match("/^mods\[[\d]{1,}\]$/",$k,$m)) {
				$smod = preg_split("/[\s]/",$v);
				foreach ($smod as $modid) {
					if (!in_array($modid,$this->UweMods)) {
						$found_mods++;
					} else {
						$this->print_cli("debug","setServersModded: UWEMod found.. skipping $modid.");
					}
				}
			}
		}
		if ($found_mods > 0) {
			$this->serversModded['Modded']++;
		} else {
			$this->serversModded['Vanilla']++;
		}
	}
	protected function prepareUweForcedMods() {
		foreach ($this->UweMods as $modid) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'UweForcedMods', $modid , '1', $this->update_time);
		}
	}
	protected function prepareServersModded() {
		foreach ($this->serversModded as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'serversmodded',$sm , $pc, $this->update_time);
		}
		$this->serversModded = array('Modded'=>0,'Vanilla'=>0);
	}
	
	// Server Popular mods
	protected function setServerModCount($data) {
		$this->getUweForcedMods();
		foreach ($data['rules'] as $k => $v) {
			if (preg_match("/^mods\[[\d]{1,}\]$/",$k,$m)) {
				$smod = preg_split("/[\s]/",$v);
				foreach ($smod as $modid) {
					// Exclude UWE mods from the mod statistics as they are basically game changes and not custom mods.
					if (!in_array($modid,$this->UweMods)) {
						if (!array_key_exists($modid, $this->ServerModCount)) { $this->ServerModCount[$modid] = 0; }
						$this->ServerModCount[$modid]++;
					} else {
						$this->print_cli("debug","setServerModCount: UWEMod found.. skipping $modid.");
					}
				}
			}
		}
	}
	protected function prepareServerModCount() {
		foreach ($this->ServerModCount as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'servermodcount',$sm , $pc, $this->update_time);
		}
		$this->ServerModCount = array();
	}	

	// Server Verion Count
	protected function setServerVersionCount($data) {
		$tags = explode("|",$data['info']['serverTags']);
		if ($tags[0] > 1) {
			if (!array_key_exists($tags[0], $this->ServerVersionCount)) { $this->ServerVersionCount[$tags[0]] = 0; }
			$this->ServerVersionCount[$tags[0]]++;
		}
	}
	protected function prepareServerVersionCount() {
		foreach ($this->ServerVersionCount as $sm => $pc) {
			$this->graphite_data[] = sprintf("server.%s.%s.%d %d %d",$this->module,'serverversioncount',$sm , $pc, $this->update_time);

		}
		$this->ServerVersionCount = array();
	}
	
	protected function prepareNS2ServerSpecific($data) {
		$host = str_replace(".","_",$data['host']);
		$port = $data['port'];
		$dtime = $this->update_time;

		if (array_key_exists('ent_count',$data['rules'])) {
			$ent_count = (int) str_replace(",","",$data['rules']['ent_count']);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'ent_count', $ent_count, $dtime);
		}
		if (array_key_exists('tickrate',$data['rules'])) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'tickrate', $data['rules']['tickrate'], $dtime);
		}

		/* Define serverTags */
		$tags = explode("|",$data['info']['serverTags']);

		// Not used
		#$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'version', $tags[0], $dtime);

		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'real_tickrate', $tags[3], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'currentPerfScore', $tags[4], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'PerfScorewithQuality', $tags[5], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'perfQuality', $tags[6], $dtime);

	}


	public function setServerHiveSkillAvg($data) {
		if ($data['info']['maxPlayers'] >= 12 ) {
			$host = str_replace(".","_",$data['host']);
			$port = $data['port'];
			$tags = explode("|",$data['info']['serverTags']);

			// Server avg player skill
			foreach ($tags as $k=>$v) {
				if (preg_match("/P_S(\d+)/",$v,$m)) {
					$category = self::_getServerCategory($data);
					// Server Specific
					$this->graphite_data[] = sprintf("server.%s.hosts.%s.%s.%s %d %d",$this->module,$host,$port,'playerskill', $m[1], $this->update_time);

					// Global average by slot size.
					if ($data['info']['numberOfPlayers'] > 4) {
						if (!@array_key_exists($data['info']['maxPlayers'],$this->SkillbyServerSize[$category])) {
							$this->SkillbyServerSize[$category][$data['info']['maxPlayers']] = array();
						}
						$this->SkillbyServerSize[$category][$data['info']['maxPlayers']][] = $m[1];
					}

					break;
				}
			}
		}
	}
	public function prepareServerHiveSkillAvg() {
		foreach ($this->SkillbyServerSize as $serverCategory => $cdata) {
			foreach ($cdata as $serverSize => $data) {
				$serverScore = array_sum($data) / count($data);
				$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,'playerskillbyserversize',$serverCategory , $serverSize ,$serverScore, $this->update_time);
			}
		}

		$this->SkillbyServerSize = array();
	}

	public static function _getServerCategory($data) {
		$tags = explode("|",$data['info']['serverTags']);
		$category = "_none_";
		$valid_cats = array(
			'/^(nsl)$/',
			'/^(last stand)$/',
			'/^(siege)$/',
			'/^(defense)$/',
			'/^(faded).*$/',
			'/^(rookie_only)$/',
			'/^(rookie)$/'

		);
		foreach ($tags as $tagU) {
			$tag = (string) strtolower($tagU);
			foreach ($valid_cats as $regex) {
				if (preg_match($regex,$tag,$m)) {
					$category = str_replace(" ","_",$m[1]);
					break 2;
				}
			}
		}
		if ($category == '_none_') { $category = 'normal'; }
		return $category;
	}
	public function sortServerbyCategory($data) {
		$category = self::_getServerCategory($data);
		$this->serverByCategory[$category][] = $data;
	}
	protected function prepareServerbyCategory() {
		// Todo: generate dashboard by type
		// Todo: graph type counts
		// foreach category we want to plot the slots/players/servers in a graphs.
		foreach ($this->serverByCategory as $cat => $val) {
			$server[$cat]['server_count'] = (int) count($this->serverByCategory[$cat]);
			$server[$cat]['maxPlayers'] = (int) 0;
			$server[$cat]['numberOfPlayers'] = (int) 0;
			foreach ($val as $k =>$v) {
				$server[$cat]['numberOfPlayers']+= $v['info']['numberOfPlayers'];
				$server[$cat]['maxPlayers']+= $v['info']['maxPlayers'];
			}
		}
		foreach ($server as $cat => $val) {
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,'statsbycategory', $cat, 'numberOfPlayers', $val['numberOfPlayers'], $this->update_time);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,'statsbycategory', $cat, 'maxPlayers', $val['maxPlayers'], $this->update_time);
			$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,'statsbycategory', $cat, 'server_count', $val['server_count'], $this->update_time);
		}

		$this->clearServerbyCategory();
	}
	public function clearServerbyCategory() {
		$this->serverByCategory = array();
	}




}

?>
