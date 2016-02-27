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

class Smokeping extends Controller
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
		$data['title'] = 'Smokeping';
		$data['description'] = "Natural Selection II - Smokeping what is it?";
		$panel_smokeping = '<iframe src="/grafana/dashboard-solo/db/natural-selection-2-server-smokeping-autogen?panelId=%d&fullscreen&theme=light" style="width: 50%%;" height="200" frameborder="0" scrolling="no"></iframe>';
		$data['smokeping_panels'] = array();

		if (file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);
			$host_total = count($srv_data['hosts']);
			shuffle($srv_data['hosts']);

			$counter = 0;
			foreach ($srv_data['hosts'] as $key => $value) {
				$data['smokeping_panels'][] = sprintf($panel_smokeping,$srv_data['hosts'][$key]['graphs']['smokeping_id']);
				$counter++;
				if ($counter >=6) { break; }
			}

		} else {

		}

		View::renderTemplate('header', $data);
		View::render('servers/smokeping', $data);
		View::renderTemplate('footer', $data);
	}


}
