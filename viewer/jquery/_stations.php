<?php

require_once('../../TlApi.php');

$provider = new TlStationsProvider(array('line'=>$_REQUEST['line']));
$stations = $provider->get();
foreach ($stations as $station)
    echo "<li><a href=\"departures.php?station={$station['name']}&line={$_REQUEST['line']}\">{$station['name']}</a></li>";