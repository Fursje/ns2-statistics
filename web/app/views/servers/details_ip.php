<?php
/**
 * Detailed Server info layout
 */

use Core\Language;

#print_r($data);
if (isset($data['servers_found'])) {
	foreach ($data['servers_found'] as $k => $v) {
		print '<div>';
		foreach ($v['panels'] as $panel_frame) {
			print $panel_frame;;
		}
		print '<br>';
		print '</div>';
	}
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
