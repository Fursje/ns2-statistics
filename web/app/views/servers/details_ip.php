<?php
/**
 * Detailed Server info layout
 */

use Core\Language;



if (count($data['servers_found']) >= 1) {
	print "<div>\n";
	print $data['player_panel'];
	print "</div>\n";

	foreach ($data['servers_found'] as $k => $v) {
		print '<div>';
		foreach ($v['panels'] as $panel_frame) {
			print $panel_frame;;
		}
		print '<br>';
		print '</div>';
	}
	print "<div>\n";
	print $data['smokeping_panel'];
	print "<center><small>Smokeping measures the network latency to the servers and show the min/max/avg/loss in 1 graph.</small></center>";
	print "</div>\n";
	print "<br>\n";
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