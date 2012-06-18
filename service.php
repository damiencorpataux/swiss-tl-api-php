<?php

require_once('TlApi.php');

$params = array_merge(
    //array('provider' => 'fuzzy'),
    $_REQUEST
);

try {
    $a = new TlApi($params);
    print $a->get();
} catch (Exception $e) {
    print json_encode(array(
        'error' => true,
        'message' => $e->getMessage()
    ));
}
