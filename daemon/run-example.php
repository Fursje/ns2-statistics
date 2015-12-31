<?php

require __DIR__. '/serverstatistics_ns2.class.php';

$ns2 = new serverstatistics_ns2();
$ns2->dev_mode = false;

$ns2->run_daemon();
#$ns2->run_once();


?>
