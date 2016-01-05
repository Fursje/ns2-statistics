<?php
/**
 * Detailed Server info layout
 */

use Core\Language;

define('BASE_URL', 'http://ns2servers.net/');
header("Content-type: text/xml");

?>
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc>http://ns2servers.net/</loc>
		<lastmod><?php print($data['data']['last_update']); ?></lastmod>
		<changefreq>hourly</changefreq>
		<priority>0.6</priority>
	</url>
	<url>
		<loc>http://ns2servers.net/grafana/dashboard/db/natural-selection-2?theme=light</loc>
		<lastmod><?php print($data['data']['last_update']); ?></lastmod>
		<changefreq>weekly</changefreq>
		<priority>0.4</priority>
	</url>

<?php

if (isset($data['data'])) {
	foreach ($data['data']['servers'] as $server =>$sval) {
		print "\t<url>\n";
		print sprintf("\t\t<loc>%sserver/details/%s/%s</loc>\n",BASE_URL,$sval['host'],$sval['port']);
		print sprintf("\t\t<lastmod>%s</lastmod>\n",$data['data']['last_update']);
		print sprintf("\t\t<changefreq>%s</changefreq>\n","daily");
		print sprintf("\t\t<priority>%s</priority>\n","0.4");
		print "\t</url>\n";
	}
	foreach ($data['data']['hosts'] as $server =>$sval) {
		print "\t<url>\n";
		print sprintf("\t\t<loc>%sserver/details/%s</loc>\n",BASE_URL,$server);
		print sprintf("\t\t<lastmod>%s</lastmod>\n",$data['data']['last_update']);
		print sprintf("\t\t<changefreq>%s</changefreq>\n","daily");
		print sprintf("\t\t<priority>%s</priority>\n","0.4");
		print "\t</url>\n";
	}

}

print "</urlset>\n";

?>
