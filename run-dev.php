<?php

require __DIR__. '/serverstatistics.class.php';

$ns2 = new serverstatistics();
$ns2->dev_mode = true;
$ns2->module = "ns2";
$ns2->masterlistQuery = "\\appid\\4920";

#$ns2->run_daemon();
$ns2->run_once();


?>
