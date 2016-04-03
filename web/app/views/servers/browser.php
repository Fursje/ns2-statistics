<?php
/**
 * Server Browser Layout
 */

use Core\Language;
use Helpers\Url;

?>
	<div>
		<p>
			<iframe src="/grafana/dashboard-solo/db/natural-selection-2?theme=light&panelId=2&fullscreen" style="width: 100%;" height="300" frameborder="0" scrolling="no"></iframe>
		</p>
		<div>
			<p style="text-align:right; font-size: 12px;"><b>Last update</b>: <?php print($data['last_update']); ?>&nbsp; <button class="btn btn-success btn-xsm" onclick="pageReload()">Reload</button></p>
			<table class="table table-condensed table-hover">
				<caption>Server List</caption>
				<thead>
					<tr>
						<th>Status</th>
						<th>Address</th>
						<th>Server Name</th>
						<th>Map</th>
						<th>Players</th>
						<th>Version</th>
						<th>Details</th>
					</tr>
				</thead>
				<tbody class="searchable">
					<?php
					foreach ($data['servers'] as $k=>$v) {
						if ($v['version'] >= $data['versions']['prod']) {
					?>
					<tr>
						<td><button type="button" class="btn btn-success btn-xsm">Working</button></td>
						<td style="white-space:nowrap;">
							<img src="<?php echo Url::templatePath()."blank.gif"; ?>" class="flag flag-<?php echo $v['country']; ?>" /> 
							<a class="ip-href" href="/server/details/<?php echo $v['host']; ?>"><?php echo $v['host']; ?></a>:<?php echo $v['port']; ?>
						</td>
						<td><?php echo $v['serverName']; ?></td>
						<td><?php echo $v['mapName']; ?></td>
						<td><?php echo $v['numberOfPlayers']." / ".$v['maxPlayers']; ?></td>
						<td><?php echo $v['version']; ?></td>
						<td><a class="btn btn-primary btn-xsm" href="/server/details/<?php echo $v['host']; ?>/<?php echo $v['port']; ?>" role="button">info</a></td>	
					</tr>
					<?php
						}
					}
					foreach ($data['servers'] as $k=>$v) {
						if ($v['version'] < $data['versions']['prod']) {
					?>
					<tr class="bg-danger">
						<td><button type="button" class="btn btn-warning btn-xsm" title="The Server is running a old version.">Outdated</button></td>
						<td style="white-space:nowrap;">
							<img src="<?php echo Url::templatePath()."blank.gif"; ?>" class="flag flag-<?php echo $v['country']; ?>" /> 
							<a class="ip-href" href="/server/details/<?php echo $v['host']; ?>"><?php echo $v['host']; ?></a>:<?php echo $v['port']; ?>
						</td>
						<td><?php echo $v['serverName']; ?></td>
						<td><?php echo $v['mapName']; ?></td>
						<td><?php echo $v['numberOfPlayers']." / ".$v['maxPlayers']; ?></td>
						<td class="text-warning"><?php echo $v['version']; ?></td>
						<td><a class="btn btn-primary btn-xsm" href="/server/details/<?php echo $v['host']; ?>/<?php echo $v['port']; ?>" role="button">info</a></td>	
					</tr>
					<?php

						}
					}				
					?>

				</tbody>
			</table>
		</div>
	</div>

<script>
function pageReload() {
    location.reload();
}
</script>	
