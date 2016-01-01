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

    /**
     * Define Index page title and load template files
     */
    public function index()
    {
        $data['title'] = 'Server Browser';
        #$data['welcome_message'] = $this->language->get('welcome_message');

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
	
	$panel_url = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="350" frameborder="0" scrolling="no"></iframe>';

	$server = sprintf("%s:%d",$host,$port);
	if (file_exists('site_data.json')) {
        	$srv_data = json_decode(file_get_contents('site_data.json'),true);
        	if (array_key_exists($server, $srv_data)) {
			$data['server_details'] = $srv_data[$server];
        		$data['title'] = sprintf("Server Details of %s",$data['server_details']['serverName']);
        		$data['panels']['info'] = sprintf($panel_url,$data['server_details']['graphs']['info_id']);
		        $data['panels']['perf'] = sprintf($panel_url,$data['server_details']['graphs']['perf_id']);
		} Else {
			$data['title'] = "Server Details: ?";
			$data['panels'] = array();
		}
	}

        View::renderTemplate('header', $data);
        View::render('servers/details', $data);
        View::renderTemplate('footer', $data);
    }


	public function _playerCompare($a,$b) {
		 return $a['numberOfPlayers'] - $b['numberOfPlayers'];
	}


	public function details_ip($host) {
		$found_servers = array();
		$panel_url = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 50%%;" height="200" frameborder="0" scrolling="no"></iframe>';
		if (filter_var($host, FILTER_VALIDATE_IP) && file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);
			
			$regex = sprintf("/%s\:\d+/",$host);
			foreach ($srv_data as $k=>$v) {
				if (preg_match($regex,$k,$m)) {
					$tmp = array();
					$tmp['serverName'] = $srv_data[$k]['serverName'];
					$tmp['numberOfPlayers'] = $srv_data[$k]['numberOfPlayers'];
        				$tmp['panels']['info'] = sprintf($panel_url,$srv_data[$k]['graphs']['info_id']);
		        		$tmp['panels']['perf'] = sprintf($panel_url,$srv_data[$k]['graphs']['perf_id']);
					$found_servers[] =  $tmp;
				}
			}
		}
		usort($found_servers,function ($a,$b) {
			return $b['numberOfPlayers'] - $a['numberOfPlayers'];
		});


		$data['servers_found'] = $found_servers;
	
		$data['title'] = "Server Details: $host";

		View::renderTemplate('header', $data);
		View::render('servers/details_ip', $data);
		View::renderTemplate('footer', $data);
	}

}
