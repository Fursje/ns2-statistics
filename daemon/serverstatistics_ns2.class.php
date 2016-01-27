<?php

require __DIR__. '/serverstatistics.class.php';

class serverstatistics_ns2 extends serverstatistics {

	public  $module = "ns2";

	protected $consoleStats = array();

	protected $ServerModCount = array();
	protected $ServerVersionCount = array();
	protected $serverByCategory = array();
	protected $serversModded = array('Modded'=>0,'Vanilla'=>0);

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

		$this->gatherData($data);
	}
	
	protected function prepareGameSpecificStats() {
		$this->prepareServerModCount();
		$this->prepareServerVersionCount();
		$this->prepareServerbyCategory();
		$this->prepareServersModded();

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
			$panels[] = $this->grafana->createPanel_HostPlayers($value['host'],$value['host'],$id_counter );
			$id_counter++;

			$rows[] = $this->grafana->createRow($value['host'], 250, $panels);
		}

		$dashboard_players = $this->grafana->prepareDashboardDefault('Natural Selection 2 - Server - Players (autogen)','natural-selection-2-server-players-autogen',$rows);
		$this->grafana->prepareDashboard($dashboard_players);


		// some sort of change check so we dont upload a dash every 5min :P
		if ($this->dev_mode == False) {
			$this->grafana->sendDashboard($dashboard_info['meta']['slug'].".json");
			$this->grafana->sendDashboard($dashboard_players['meta']['slug'].".json");
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



	/* Game Specific Stats Functions */

	// Modded vs unmodded
	protected function setServersModded($data) {
		if (array_key_exists('serverTags', $data['info'])) {
			$tags = explode("|",$data['info']['serverTags']);
			switch($tags[2]) {
				case 'M':
					$this->serversModded['Modded']++;
					break;
				case 'V':
					$this->serversModded['Vanilla']++;
					break;
				default:
					// should never happen..
					break;
			}
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
		foreach ($data['rules'] as $k => $v) {
			if (preg_match("/^mods\[[\d]{1,}\]$/",$k,$m)) {
				$smod = preg_split("/[\s]/",$v);
				foreach ($smod as $modid) {
					if (!array_key_exists($modid, $this->ServerModCount)) { $this->ServerModCount[$modid] = 0; }
					$this->ServerModCount[$modid]++;
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
		#print_r($tags);
		/*
			Array
			(
			    [0] => 277
			    [1] => ns2
			    [2] => M
			    [3] => 149
			    [4] => 34
			    [5] => 34
			    [6] => 32
			    [7] => CHUD_0x0
			    [8] => P_S2592
			    [9] => shine
			    [10] => NSL
			    [11] => ServerTickrate30
			    [12] => tickrate_29
			)
			modded?|tickrate|currentPerfScore|PerfScorewithQuality|perfQuality
		*/
		
		// Not used
		#$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'version', $tags[0], $dtime);

		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'real_tickrate', $tags[3], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'currentPerfScore', $tags[4], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'PerfScorewithQuality', $tags[5], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'perfQuality', $tags[6], $dtime);

	}

	public function sortServerbyCategory($data) {
		$tags = explode("|",$data['info']['serverTags']);
		$this->print_cli('DEBUG-TAG', $data['info']['serverTags']);
		$category = "_none_";
		foreach ($tags as $tagU) {
			$tag = strtolower($tagU);
			switch($tag) {
				case 'nsl':
					$category = 'nsl';
					break;
				case 'siege':
					$category = 'siege';
					break;
				case 'rookie_only':
					$category = 'rookie_only';
					break;
				case 'rookie':
					$category = 'rookie';
					break;

				#default:
				#	$category = 'normal';
			}
			if ($category != '_none_') { continue; }
		}
		if ($category == '_none_') { $category = 'normal'; }

		$this->serverByCategory[$category][] = $data;
	}
	protected function prepareServerbyCategory() {
		// Todo: generate dashboard by type
		// Todo: graph type counts
		// foreach category we want to plot the slots/players/servers in a graphs.
		foreach ($this->serverByCategory as $cat => $val) {
			$server[$cat]['server_count'] = count($this->serverByCategory[$cat]);
			$server[$cat]['maxPlayers'] = 0;
			$server[$cat]['numberOfPlayers'] = 0;
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
