<?php

namespace Helpers;

class ns2 {

	public static function currentVersion($data) {
		$counters = array();
		$total = 0;
		$dev_version = 0;
		$prod_version = 0;
		// highest version with atleast 10% of all servers is the live version.. right? ;x
		foreach ($data as $k => $v) {
			@$counters[$v['version']]++;
			$total++;
		}
		$min_required = ($total / 100) * 10;
		krsort($counters);
		foreach ($counters as $k => $v) {
			if ($v >= $min_required) {
				$dev_version = $k+1;
				$prod_version = $k;
				break;
			}
		}
		return array('dev'=>$dev_version,'prod'=>$prod_version);
	}
}

?>