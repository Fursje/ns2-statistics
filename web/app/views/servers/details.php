<?php
/**
 * Detailed Server info layout
 */

use Core\Language;

if (isset($data['server_details'])) {
	foreach ($data['panels'] as $panel_frame) {
		print '<p>'.$panel_frame.'</p><br>';
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
