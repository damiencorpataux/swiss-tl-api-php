<?php

require_once('TlApi.php');

$c = new TlStationsCollector();
$r = $c->collect();
file_put_contents('collector.stations.dump.php', var_export($r, true));