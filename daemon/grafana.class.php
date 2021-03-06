<?php

#$gr = new grafana();

#$nsldata = $gr->prepareDashboardDefault('Natural Selection 2 - NSL','ns2-nsl');
#$gr->prepareDashboard($nsldata);
#$gr->sendDashboard($nsldata['meta']['slug'].".json");
#$gr->json2array('ns2.json');
#$gr->getDashboard("ns2-auto-gen","ns2-auto-gen-new.json");
// panels -> rows -> dashboard
class grafana {

	public $host = "";
	public $port = "";
	public $apiKey = "";
	public $bin_curl = "/usr/bin/curl";

	public function __construct() {
		if (file_exists(dirname(__FILE__).'/grafana.conf.php')) {
			require_once(dirname(__FILE__).'/grafana.conf.php');
		}
	}
	public function prepareDashboard($data) {
		$json = json_encode($data);
		file_put_contents($data['meta']['slug'].".json",$json);
		#print_r($json);
	}

	public function getDashboard($dashboard,$saveFile) {
		// /usr/bin/curl -H "Authorization: Bearer <api_key>" -X GET 'http://10.120.34.18:3000/api/dashboards/db/natural-selection-2'
		$curl_url = sprintf("http://%s:%s/grafana/api/dashboards/db/%s",$this->host,$this->port,$dashboard);
		$curl_cmd = sprintf("%s -H 'Authorization: Bearer %s' -X GET %s -o %s",$this->bin_curl,$this->apiKey,$curl_url, $saveFile);
		exec($curl_cmd);
	}
	
	public function sendDashboard($uploadFile) {
		$curl_url = sprintf("http://%s:%s/grafana/api/dashboards/db",$this->host,$this->port);
		$curl_cmd = sprintf("%s -H 'Accept: application/json' -H 'Content-Type: application/json' -H 'Authorization: Bearer %s' -X POST %s -d @%s",$this->bin_curl,$this->apiKey,$curl_url, $uploadFile);
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
			'schemaVersion' => 12,
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

	public static function createPanel_ServerHivescore($name,$host,$port,$id = null) {
		$host = grafana::ip2field($host);
		$data = array(
		  "aliasColors"=> array(),
		  "bars"=> false,
		  "datasource"=> null,
		  "editable"=> true,
		  "error"=> false,
		  "fill"=> 1,
		  "grid"=> array(
			"threshold1"=> null,
			"threshold1Color"=> "rgba(216, 200, 27, 0.27)",
			"threshold2"=> null,
			"threshold2Color"=> "rgba(234, 112, 112, 0.22)"
		  ),
		  "id"=> $id,
		  "isNew"=> true,
		  "legend"=> array(
			"alignAsTable"=> true,
			"avg"=> true,
			"current"=> true,
			"hideEmpty"=> true,
			"hideZero"=> true,
			"max"=> true,
			"min"=> true,
			"rightSide"=> false,
			"show"=> true,
			"total"=> false,
			"values"=> true
		  ),
		  "lines"=> true,
		  "linewidth"=> 1,
		  "links"=> array(),
		  "nullPointMode"=> "null",
		  "percentage"=> false,
		  "pointradius"=> 1,
		  "points"=> true,
		  "renderer"=> "flot",
		  "seriesOverrides"=> array(),
		  "span"=> 12,
		  "stack"=> false,
		  "steppedLine"=> false,
		  "targets"=> array(),
		  "timeFrom"=> null,
		  "timeShift"=> null,
		  "title"=> $name. " - Hivescore",
		  "tooltip"=> array(
			"msResolution"=> false,
			"shared"=> true,
			"value_type"=> "cumulative"
		  ),
		  "type"=> "graph",
		  "xaxis"=> array(
			"show"=> true
		  ),
		  "yaxes"=> array(
			array(
			  "format"=> "none",
			  "label"=> "HiveScore",
			  "logBase"=> 1,
			  "max"=> null,
			  "min"=> null,
			  "show"=> true
			),
			array(
			  "format"=> "short",
			  "label"=> "",
			  "logBase"=> 1,
			  "max"=> null,
			  "min"=> null,
			  "show"=> true
			)
		  )
		);
		$targets[] = array(
			'refId' => 'D',
			'target' => sprintf("alias(keepLastValue(server.ns2.hosts.%s.%s.playerskill, 1), 'HiveScore')",$host,$port),
		);

		$data['targets'] = $targets;
		return $data;
	}
	public static function createPanel_Smokeping($name,$host, $id = null) {
		$host = grafana::ip2field($host);
		$data = array(
			'editable' => true,
			'fill' => 0,
			'grid' => array(
				"leftLogBase"=> 1,
				"leftMax"=> null,
				"leftMin"=> null,
				"rightLogBase"=> 1,
				"rightMax"=> 100,
				"rightMin"=> 0,
				"threshold1"=> null,
				"threshold1Color"=> "rgba(216, 200, 27, 0.27)",
				"threshold2"=> null,
				"threshold2Color"=> "rgba(234, 112, 112, 0.22)",
			),
			'id' => $id,
			'legend' => array(
				"alignAsTable"=> true,
				"avg"=> true,
				"current"=> true,
				"max"=> true,
				"min"=> true,
				"rightSide"=> false,
				"show"=> true,
				"total"=> false,
				"values"=> true,
			),
			'lines' => true,
			'linewidth' => 1,
			'nullPointMode' => 'connected',
			'pointradius' => 5,
			'renderer' => 'flot',
			"rightYAxisLabel" => "packetloss",
			'span' => 12,

			"seriesOverrides" => array(
				array(
					"alias"=> "loss",
					"bars"=> true,
					"color"=> "#BF1B00",
					"lines"=> false,
					"pointradius"=> 1,
					"yaxis"=> 2
				),
				array(
					"alias"=> "max",
					"color"=> "#0A437C",
					"fillBelowTo"=> "min",
					"lines"=> false
				),
				array(
					"alias"=> "min",
					"color"=> "#7EB26D",
					"lines"=> false
			  ),
				array(
					"alias"=> "avg",
					"color"=> "#EAB839",
					"fillBelowTo"=> "min"
				),
			),

			'targets' => array(),
			'title' => 'Smokeping ('.$name.')',
			'tooltip' => array(
				'value_type' => 'cumulative',
			),
			'type' => 'graph',
			'x-axis' => true,
			'y-axis' => true,
			'y_formats' => array('ms','percent'),

			'steppedLine' => false,
			"stack" => false,
		);
		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("aliasByNode(keepLastValue(server.ns2.smokeping.%s.min, 1), 4)",$host),
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("aliasByNode(keepLastValue(server.ns2.smokeping.%s.max, 1), 4)",$host),
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("aliasByNode(keepLastValue(server.ns2.smokeping.%s.avg, 1), 4)",$host),
		);
		$targets[] = array(
			'refId' => 'D',
			'target' => sprintf("aliasByNode(keepLastValue(asPercent(server.ns2.smokeping.%s.loss, 100), 1), 4)",$host),
		);

		$data['targets'] = $targets;
		return $data;
	}
	public static function createPanel_HostPlayers($name,$host, $id = null, $port = "*") {
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
			'span' => 12,
			'targets' => array(),
			'title' => 'Players on '.$name,
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
			'target' => sprintf("alias(keepLastValue(sumSeries(server.ns2.%s.%s.maxPlayers), 2), 'Total Slots')",$host,$port),
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("alias(keepLastValue(sumSeries(server.ns2.%s.%s.numberOfPlayers), 2), 'Total Players')",$host,$port),
		);

		$data['targets'] = $targets;
		return $data;
	}
	public static function createPanel_HostPlayersTimeShift($name,$host, $id = null, $port = "*") {
		$host = grafana::ip2field($host);
		$data = array(
			"aliasColors"=> array(),
			"bars"=> false,
			"datasource"=> null,
			"editable"=> true,
			"error"=> false,
			"fill"=> 1,
			"grid"=> array(
				'leftLogBase' => 2,
				'rightLogBase' => 1,
				"threshold1"=> null,
				"threshold1Color"=> "rgba(216, 200, 27, 0.27)",
				"threshold2"=> null,
				"threshold2Color"=> "rgba(234, 112, 112, 0.22)"
			),
			"id"=> $id,
			"isNew"=> true,
			"legend"=> array(
				"avg"=> true,
				"current"=> true,
				"hideEmpty"=> true,
				"hideZero"=> true,
				"max"=> true,
				"min"=> true,
				"show"=> false,
				"total"=> false,
				"values"=> true,
				"alignAsTable" => true,
				"rightSide" => false
			),
			"lines"=> true,
			"linewidth"=> 2,
			"links"=> array(),
			"nullPointMode"=> "null as zero",
			"percentage"=> false,
			"pointradius"=> 5,
			"points"=> false,
			"renderer"=> "flot",
			"seriesOverrides"=> array(
				array(
					"alias" => "Total Players - Last week",
					"fillBelowTo" => "Total Players",
					"lines" => true,
					"fill" => 0,
					"color" => "#1F78C1",
					"linewidth" => 1
				),
				array(
					"alias"=> "Total Slots",
					"fill"=> 0
				),
			),
			"span"=> 12,
			"stack"=> false,
			"steppedLine"=> false,
			"targets"=> array(),
			"timeFrom"=> "7d",
			"timeShift"=> null,
			"title" => $name. ' - Players',
			"tooltip"=> array(
				"msResolution"=> false,
				"shared"=> true,
				"value_type"=> "cumulative"
			),
			"type"=> "graph",
			"xaxis"=> array(
				"show"=> true
			),
			"yaxes"=> array(
				array(
					"format"=> "none",
					"label"=> null,
					"logBase"=> 1,
					"max"=> null,
					"min"=> null,
					"show"=> true
				),
				array(
					"format"=> "short",
					"label"=> null,
					"logBase"=> 1,
					"max"=> null,
					"min"=> null,
					"show"=> false
			))
		);
		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("alias(keepLastValue(sumSeries(server.ns2.%s.%s.maxPlayers), 2), 'Total Slots')",$host,$port),
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("alias(keepLastValue(sumSeries(server.ns2.%s.%s.numberOfPlayers), 2), 'Total Players')",$host, $port),
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("alias(timeShift(keepLastValue(sumSeries(server.ns2.%s.%s.numberOfPlayers), 2), '7d'), 'Total Players - Last week')",$host, $port),
		);

		$data['targets'] = $targets;
		return $data;
	}
	public static function createPanel_ServerInfo($name,$host,$port, $id = null) {
		$host = grafana::ip2field($host);
		$data = array(
			"aliasColors"=> array(),
			"bars"=> false,
			"datasource"=> null,
			"editable"=> true,
			"error"=> false,
			"fill"=> 1,
			"grid"=> array(
				"threshold1"=> null,
				"threshold1Color"=> "rgba(216, 200, 27, 0.27)",
				"threshold2"=> null,
				"threshold2Color"=> "rgba(234, 112, 112, 0.22)"
			),
			"id"=> $id,
			"isNew"=> true,
			"legend"=> array(
				"avg"=> true,
				"current"=> true,
				"hideEmpty"=> true,
				"hideZero"=> true,
				"max"=> true,
				"min"=> true,
				"show"=> false,
				"total"=> false,
				"values"=> true,
				"alignAsTable" => true,
				"rightSide" => false
			),
			"lines"=> true,
			"linewidth"=> 2,
			"links"=> array(),
			"nullPointMode"=> "null",
			"percentage"=> false,
			"pointradius"=> 5,
			"points"=> false,
			"renderer"=> "flot",
			"seriesOverrides"=> array(),
			"span"=> 6,
			"stack"=> false,
			"steppedLine"=> true,
			"targets"=> array(),
			"timeFrom"=> null,
			"timeShift"=> null,
			"title" => $name,
			"tooltip"=> array(
				"msResolution"=> false,
				"shared"=> true,
				"value_type"=> "cumulative"
			),
			"type"=> "graph",
			"xaxis"=> array(
				"show"=> true
			),
			"yaxes"=> array(
				array(
					"format"=> "none",
					"label"=> null,
					"logBase"=> 2,
					"max"=> null,
					"min"=> null,
					"show"=> true
				),
				array(
					"format"=> "short",
					"label"=> null,
					"logBase"=> 1,
					"max"=> null,
					"min"=> null,
					"show"=> true
			))
		);

		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("alias(server.ns2.%s.%d.ent_count, 'entities')",$host,$port),
			"textEditor"=> true
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("alias(server.ns2.%s.%d.numberOfPlayers, 'Players')",$host,$port),
			"textEditor"=> true
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("alias(server.ns2.%s.%d.tickrate, 'tickrate')",$host,$port),
			"textEditor"=> true
		);
		$targets[] = array(
			'refId' => 'D',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.real_tickrate)",$host,$port),
			"textEditor"=> true
		);
		$data['targets'] = $targets;
		return $data;
	}

	public static function createPanel_ServerPerformance($name,$host,$port,$id=null) {
		$host = grafana::ip2field($host);
		$data = array(
			"aliasColors"=> array(),
			"bars"=> false,
			"datasource"=> null,
			"editable"=> true,
			"error"=> false,
			"fill"=> 1,
			"grid"=> array(
				"threshold1"=> null,
				"threshold1Color"=> "rgba(216, 200, 27, 0.27)",
				"threshold2"=> null,
				"threshold2Color"=> "rgba(234, 112, 112, 0.22)"
			),
			"id"=> $id,
			"isNew"=> true,
			"legend"=> array(
				"avg"=> true,
				"current"=> true,
				"hideEmpty"=> true,
				"hideZero"=> true,
				"max"=> true,
				"min"=> true,
				"show"=> false,
				"total"=> false,
				"values"=> true,
				"alignAsTable" => true,
				"rightSide" => false
			),
			"lines"=> true,
			"linewidth"=> 2,
			"links"=> array(),
			"nullPointMode"=> "null",
			"percentage"=> false,
			"pointradius"=> 5,
			"points"=> false,
			"renderer"=> "flot",
			"seriesOverrides"=> array(),
			"span"=> 6,
			"stack"=> false,
			"steppedLine"=> true,
			"targets"=> array(),
			"timeFrom"=> null,
			"timeShift"=> null,
			"title"=> $name." - Performance",
			"tooltip"=> array(
				"msResolution"=> false,
				"shared"=> true,
				"value_type"=> "cumulative"
			),
			"type"=> "graph",
			"xaxis"=> array(
				"show"=> true
			),
			"yaxes"=> array(
				array(
					"format"=> "none",
					"label"=> null,
					"logBase"=> 1,
					"max"=> 100,
					"min"=> null,
					"show"=> true
				),
				array(
					"format"=> "short",
					"label"=> null,
					"logBase"=> 1,
					"max"=> null,
					"min"=> null,
					"show"=> true
				)
			)
		);

		$targets[] = array(
			'refId' => 'A',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.PerfScorewithQuality)",$host,$port),
			"textEditor"=> true
		);
		$targets[] = array(
			'refId' => 'B',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.currentPerfScore)",$host,$port),
			"textEditor"=> true
		);
		$targets[] = array(
			'refId' => 'C',
			'target' => sprintf("aliasByMetric(server.ns2.%s.%d.perfQuality)",$host,$port),
			"textEditor"=> true
		);


		$data['targets'] = $targets;
		return $data;
	}
	
}


?>
