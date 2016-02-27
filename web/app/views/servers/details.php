<?php
/**
 * Detailed Server info layout
 */

use Core\Language;

if (isset($data['server_details'])) {
	foreach ($data['panels'] as $panel_frame) {
		print '<div>'.$panel_frame.'</div>';
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
