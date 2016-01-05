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

class Sitemap extends Controller
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

		if (file_exists('site_data.json')) {
			$srv_data = json_decode(file_get_contents('site_data.json'),true);
			$data['data'] = $srv_data;
			$data['data']['last_update'] = date("c",strtotime($srv_data['last_update']));

			usort($data['data']['servers'],function ($a,$b) {
				return $b['serverName'] - $a['serverName'];
			});


		} else {
			$data['data']['servers'] = array();
		}

		#View::renderTemplate('header', $data);
		View::render('servers/sitemap', $data);
		#View::renderTemplate('footer', $data);
	}


}
