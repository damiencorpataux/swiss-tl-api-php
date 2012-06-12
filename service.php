<?php

require_once('TlApi.php');

$params = array_merge(
    //array('provider' => 'fuzzy'),
    $_REQUEST
);
$a = new TlApi($params);
print $a->get();