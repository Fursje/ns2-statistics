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


	protected function setGameSpecificStats($data) {
		$this->prepareNS2ServerSpecific($data);
		$this->setServerModCount($data);
		$this->setServerVersionCount($data);
		$this->sortServerbyCategory($data);
		$this->setServersModded($data);
	}
	
	protected function prepareGameSpecificStats() {
		$this->prepareServerModCount();
		$this->prepareServerVersionCount();
		$this->prepareServerbyCategory();
		$this->prepareServersModded();
	}

	/* Game Specific Functions */

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
			$this->graphite_data[] = sprintf("server.%s.%s.mod_%s %d %d",$this->module,'servermodcount',$sm , $pc, $this->update_time);
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
			$this->graphite_data[] = sprintf("server.%s.%s.version_%d %d %d",$this->module,'serverversioncount',$sm , $pc, $this->update_time);

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
		
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'version', $tags[0], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'real_tickrate', $tags[3], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'currentPerfScore', $tags[4], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'PerfScorewithQuality', $tags[5], $dtime);
		$this->graphite_data[] = sprintf("server.%s.%s.%s.%s %d %d",$this->module,$host,$port,'perfQuality', $tags[6], $dtime);

	}

	public function sortServerbyCategory($data) {
		$tags = explode("|",$data['info']['serverTags']);
		#$this->print_cli('DEBUG-TAG', $data['info']['serverTags']);
		$category = "_none_";
		foreach ($tags as $tag) {
			switch($tag) {
				case 'NSL':
					$category = 'nsl';
					break;
				case 'Siege':
					$category = 'siege';
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

		$this->serverByCategory[$category][] = array('name'=>$data['info']['serverName'],'host'=>$data['host'],'port'=>$data['port']);
	}
	protected function prepareServerbyCategory() {
		// Todo: generate dashboard by type
		// Todo: graph type counts
		foreach ($this->serverByCategory as $cat => $val) {
			$amount = count($this->serverByCategory[$cat]);
			$this->graphite_data[] = sprintf("server.%s.%s.%s %d %d",$this->module,'serverbycategory', $cat, $amount, $this->update_time);
		}

		$this->clearServerbyCategory();
	}
	public function clearServerbyCategory() {
		$this->serverByCategory = array();
	}

}

?>