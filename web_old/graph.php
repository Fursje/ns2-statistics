<?php

#if (isset($_GET['i_id']) && is_numeric($_GET['i_id'])) { $i_id = $_GET['i_id']; } else { $i_id = 1; }
#if (isset($_GET['p_id']) && is_numeric($_GET['p_id'])) { $p_id = $_GET['p_id']; } else { $p_id = 2; }

#$panel_url = '<p><iframe src="/grafana/dashboard-solo/db/natural-selection-2-servers-autogen?panelId=%d&fullscreen&theme=light" style="width: 100%%;" height="350" frameborder="0" scrolling="no"></iframe></p>';

#$i_idf = sprintf($panel_url,$i_id);
#$p_idf = sprintf($panel_url,$p_id);

#print '<center>';
#print $i_idf;
#print "<br>";
#print $p_idf;
?>
	<div class="alert alert-danger" role="alert">
	  <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
	  <span class="sr-only">Error:</span>
	  Graphing URL has changed, please check the server list for the new link. That one is static and not dynamic like this one:)

	</div>
