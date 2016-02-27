<?php
/**
 * Servers controller
 *
 * @author Furs
 * @version 0.1
 * @date 01/01/2016
 * @date updated: x
 */

namespace Controllers;

use Core\View;
use Core\Controller;

class Servers extends Controller
{

	/**
	 * Call the parent construct
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$data['title'] = 'Server Browser';

		if (file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);
			$data['servers'] = $srv_data['servers'];
			$data['last_update'] = $srv_data['last_update'];

			usort($data['servers'],function ($a,$b) {
				return $b['numberOfPlayers'] - $a['numberOfPlayers'];
			});


		} else {
			$data['servers'] = array();
			$data['last_update'] = 0;
		}

		View::renderTemplate('header', $data);
		View::render('servers/browser', $data);
		View::renderTemplate('footer', $data);
	}

	/**
	 * Define Server Detail page title and load template files
	 */
	public function details($host,$port)
	{
		//$data['welcome_message'] = $this->language->get('subpage_message');
		$data['host'] = $host;
		$data['port'] = $port;

		$panel_url = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="250" frameborder="0" scrolling="no"></iframe>';
		$panel_smokeping = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-server-smokeping-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="250" frameborder="0" scrolling="no"></iframe>';

		$server = sprintf("%s:%d",$host,$port);
		if (file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);
			if (array_key_exists($server, $srv_data['servers'])) {
				$data['server_details'] = $srv_data['servers'][$server];
				$data['title'] = sprintf("Server Details of %s",$data['server_details']['serverName']);
				$data['description'] = "Natural Selection II - ".$data['title'];
				$data['panels']['info'] = sprintf($panel_url,$data['server_details']['graphs']['info_id']);
				$data['panels']['perf'] = sprintf($panel_url,$data['server_details']['graphs']['perf_id']);
				} Else {
				$data['title'] = "Server Details: ?";
				$data['panels'] = array();
			}
			if (array_key_exists($host, $srv_data['hosts'])) {
				$data['smokeping_panel'] = sprintf($panel_smokeping,$srv_data['hosts'][$host]['graphs']['smokeping_id']);
			}
		}

		View::renderTemplate('header', $data);
		View::render('servers/details', $data);
		View::renderTemplate('footer', $data);
	}


	public function details_ip($host) {
		$found_servers = array();
		$panel_players = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-server-players-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="200" frameborder="0" scrolling="no"></iframe>';
		$panel_url = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 50%%;" height="200" frameborder="0" scrolling="no"></iframe>';
		$panel_smokeping = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-server-smokeping-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="300" frameborder="0" scrolling="no"></iframe>';

		if (filter_var($host, FILTER_VALIDATE_IP) && file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);

			$regex = sprintf("/%s\:\d+/",$host);
			foreach ($srv_data['servers'] as $k=>$v) {
				if (preg_match($regex,$k,$m)) {
					$tmp = array();
					$tmp['serverName'] = $srv_data['servers'][$k]['serverName'];
					$tmp['numberOfPlayers'] = $srv_data['servers'][$k]['numberOfPlayers'];
					$tmp['panels']['info'] = sprintf($panel_url,$srv_data['servers'][$k]['graphs']['info_id']);
					$tmp['panels']['perf'] = sprintf($panel_url,$srv_data['servers'][$k]['graphs']['perf_id']);
					$found_servers[] =  $tmp;
				}
			}
			if (array_key_exists($host, $srv_data['hosts'])) {
				$data['player_panel'] = sprintf($panel_players,$srv_data['hosts'][$host]['graphs']['players_id']);
				$data['smokeping_panel'] = sprintf($panel_smokeping,$srv_data['hosts'][$host]['graphs']['smokeping_id']);
			}
		}
		usort($found_servers,function ($a,$b) {
			return $b['numberOfPlayers'] - $a['numberOfPlayers'];
		});


		$data['servers_found'] = $found_servers;
	
		$data['title'] = "Server Details: $host";
		$data['description'] = "Natural Selection II - Statistics of all servers running on: $host";

		View::renderTemplate('header', $data);
		View::render('servers/details_ip', $data);
		View::renderTemplate('footer', $data);
	}

	public function workshop() {
		$data['title'] = "Workshop Backup Server";
		$data['description'] = "Natural Selection II - running a workshop backup server";

		View::renderTemplate('header', $data);
		View::render('servers/workshop', $data);
		View::renderTemplate('footer', $data);
	}

	public function contact() {
		$data['title'] = "Contact";
		$data['description'] = "Natural Selection II - Contact the site maintainer";

		View::renderTemplate('header', $data);
		View::render('servers/contact', $data);
		View::renderTemplate('footer', $data);
	}
}
