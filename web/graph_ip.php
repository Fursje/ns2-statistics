<?php

if (isset($_GET['who']) && is_string($_GET['who'])) { $who = $_GET['who']; } else { $who = False; }

$server_found = False;
$server_data = array();

$panel_url = '<p><iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="350" frameborder="0" scrolling="no"></iframe></p>';


if ($who != False && file_exists(dirname(__FILE__).'/site_data.json')) {
	$data = json_decode(file_get_contents('site_data.json'),true);
	if (array_key_exists($who, $data)) { 
		$server_found = True; 
		$server_data = $data[$who]; 
	}
}

if ($server_found) {
	$i_idf = sprintf($panel_url,$server_data['graphs']['info_id']);
	$p_idf = sprintf($panel_url,$server_data['graphs']['perf_id']);

	print '<div class=container>';
	print $i_idf;
	print "<br>";
	print $p_idf;
	print "</div>";
} Else {
	?>
	<div class="alert alert-danger" role="alert">
	  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
	  <span class="sr-only">Error:</span>
	  Server not found, sure it exists? ;)
	</div>
	<?php
}




?>
