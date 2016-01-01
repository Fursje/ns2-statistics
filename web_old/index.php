<?php

$valid_hosts = array('ns2servers.net', 'www.ns2servers.net');
if (!in_array($_SERVER['HTTP_HOST'], $valid_hosts)) {
	$location = sprintf("http://ns2servers.net%s",$_SERVER['REQUEST_URI']);
	header("Location: $location",true,301);
	exit;
}
require_once("header.tmpl");

if (isset($_GET['page'])) { $page = $_GET['page']; }
else $page = "";

switch($page) {
	case 'servers':
		require_once("servers.tmpl");
		break;
	case 'graph':
		require_once("graph.php");
		break;
	case 'graphserver':
		require_once("graph_ip.php");
		break;

	default:
		require_once("servers.tmpl");
		break;
}


require_once("footer.tmpl");

?>
