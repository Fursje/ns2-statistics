<?php
/**
 * Workshop Servers
 */

use Core\Language;
use Helpers\Url;

?>
	<div>
		<div class="col-md-8">
		<h3 id="header-color">Smokeping :: What is it?</h3>
		<br>
		Smokeping measures the network latency to the servers and show the min/max/avg/loss in one graph.<br>
		Currently only 1 node exists to measure, maybe in the future I will add the posibility to have multiple endpoints.
		<br>
		<br>

		Below are 6 random smokeping graphs, incase you see the same spikes on all 6 of them, that means that the monitor node is having a lag spike instead of the remote host. :)
		<br><br>
		</div>

		<div class="col-md-12">
		<?php
			$counter = 0;
			print "<div>";
			foreach ($data['smokeping_panels'] as $panel) {
				print $panel;
				if ($counter % 2) {
					print "</div><div>\n";
				}
				$counter++;
			}
			print "</div>";
		?>
		<br><br>
		</div>
	</div>

