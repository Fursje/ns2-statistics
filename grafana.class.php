<?php

#$gr = new grafana();

#$nsldata = $gr->prepareDashboardDefault('Natural Selection 2 - NSL','ns2-nsl');
#$gr->prepareDashboard($nsldata);
#$gr->sendDashboard($nsldata['meta']['slug'].".json");
#$gr->json2array('ns2.json');
#$gr->getDashboard("ns2-auto-gen","ns2-auto-gen-new.json");
// panels -> rows -> dashboard
class grafana {

	public $host = "10.120.34.18";
	public $port = 3000;
	public $apiKey = "yJrIjoiNGR4THkzWHlHbm9FdWU2Tlc2OEFRckNnVWwwTkswNlUiLCJuIjoidGVzdGluZyIsImlkIjoxfQ==";
	public $bin_curl = "/usr/bin/curl";


	public function prepareDashboard($data) {
		$json = json_encode($data);
		file_put_contents($data['meta']['slug'].".json",$json);
		#print_r($json);
	}

	public function getDashboard($dashboard,$saveFile) {
		// /usr/bin/curl -H "Authorization: Bearer eyJrIjoiNGR4THkzWHlHbm9FdWU2Tlc2OEFRckNnVWwwTkswNlUiLCJuIjoidGVzdGluZyIsImlkIjoxfQ==" -X GET 'http://10.120.34.18:3000/api/dashboards/db/natural-selection-2'
		$curl_url = sprintf("http://%s:%s/api/dashboards/db/%s",$this->host,$this->port,$dashboard);
		$curl_cmd = sprintf("%s -H 'Authorization: Bearer %s' -X GET %s -o %s",$this->bin_curl,$this->apiKey,$curl_url, $saveFile);
		exec($curl_cmd);
	}
	
	public function sendDashboard($uploadFile) {
		$curl_url = sprintf("http://%s:%s/api/dashboards/db",$this->host,$this->port);
		$curl_cmd = sprintf("%s -H 'Accept: application/json' -H 'Content-Type: application/json' -H 'Authorization: Bearer %s' -X POST %s -d @%s",$this->bin_curl,$this->apiKey,$curl_url, $uploadFile);
		print "debug: $curl_cmd\n";
		exec($curl_cmd);
		
	}
	public function json2array($file) {
		$a = file_get_contents($file);
		$b = json_decode($a,true);
		print_r($b);
	}

	public function prepareDashboardDefault($name,$slug,$rows, $id = null) {
		$data = array();
		$data['meta'] = array(
			'type' => 'db',
			'canSave' => true,
			'canEdit' => true,
			'canStar' => true,
			'slug' => $slug,
			'expires' => '0001-01-01T00:00:00Z',
			'created' => '0001-01-01T00:00:00Z'
		);
		$data['dashboard'] = array(
			'annotations' => array(
				'list' => array(),
			),
			'editable' => true,
			'hideControls' => false,
			'id' => $id,
			'links' => array(),
			'originalTitle' => $name,
			'refresh' => '5m',
			'rows' => $rows, // will be filled by another function
			'schemaVersion' => 7,
			'sharedCrosshair' => false,
			'style' => 'dark',
			'tags' => array(),
			'templating' => array(
				'list' => array(),
			),
			'time' => array(
				'from' => 'now-7d',
				'to' => 'now',
			),
			'timepicker' => array(
				'now' => false,
				'refresh_intervals' => array(
					'5s','10s','30s','1m','5m','15m','30m','1h','2h','1d'
				),
				'time_options' => array(
					'5m','15m','1h','6h','12h','24h','2d','7d','30d'
				),
			),
			'timezone' => 'browser',
			'title' => $name,
			'version' => 0,
		);
		$data['overwrite'] = true;

		return $data;
	}
	
	public static function createRow($title = 'rowName', $height = 250, $panels = array()) {
		$data = array(
			'editable' => true,
			'height' => $height.'px',
			'panels' => $panels,
			'title' => $title
		);
		return $data;
	}
	public static function ip2field($ip) {
		return str_replace(".","_",$ip);
	}
	public static function createPanel_ServerInfo($name,$host,$port, $id = null) {
		$host = grafana::ip2field($host);
		$data = array(
			'editable' => true,
			'fill' => 1,
			'grid' => array(
				'leftLogBase' => 2,
				'rightLogBase' => 1,
				'threshold1Color' => 'rgba(216, 200, 27, 0.27)',
				'threshold2Color' => 'rgba(234, 112, 112, 0.22)',
			),
			'id' => $id,
			'legend' => array(
				'show' => false,
			),
			'lines' => true,
			'linewidth' => 2,
			'nullPointMode' => 'null as zero',
			'pointradius' => 5,
			'renderer' => 'flot',
			'span' => 6,
			'targets' => array(
				
			),
			'title' => $name,
			'tooltip' => array(
				'value_type' => 'cumulative',
			),
			'type' => 'graph',
			'x-axis' => true,
			'y-axis' => true,
			'y_formats' => array('short','short'),
			'steppedLine' => true,
		);
		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("alias(server.ns2.%s.%d.ent_count, 'entities')",$host,$port),
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("alias(server.ns2.%s.%d.numberOfPlayers, 'Players')",$host,$port),
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("alias(server.ns2.%s.%d.tickrate, 'tickrate')",$host,$port),
		);
		$data['targets'] = $targets;
		return $data;
	}

	public static function createPanel_ServerPerformance($name,$host,$port,$id=null) {
		$host = grafana::ip2field($host);
		$data = array(
			'editable' => true,
			'fill' => 1,
			'grid' => array(
				'leftLogBase' => 2,
				'rightLogBase' => 1,
				'threshold1Color' => 'rgba(216, 200, 27, 0.27)',
				'threshold2Color' => 'rgba(234, 112, 112, 0.22)',
			),
			'id' => $id,
			'legend' => array(
				'show' => false,
			),
			'lines' => true,
			'linewidth' => 2,
			'nullPointMode' => 'null as zero',
			'pointradius' => 5,
			'renderer' => 'flot',
			'span' => 6,
			'targets' => array(
				
			),
			'title' => $name." - Performance",
			'tooltip' => array(
				'value_type' => 'cumulative',
			),
			'type' => 'graph',
			'x-axis' => true,
			'y-axis' => true,
			'y_formats' => array('short','short'),
			'steppedLine' => true,
		);	
		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.PerfScorewithQuality)",$host,$port),
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.currentPerfScore)",$host,$port),
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.perfQuality)",$host,$port),
		);
		$targets[] = array(
			'refId' => 'D',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.real_tickrate)",$host,$port),
		);

		$data['targets'] = $targets;
		return $data;
	}	
	
}


?>
