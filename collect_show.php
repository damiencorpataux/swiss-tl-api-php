<?php

$s = file_get_contents('collector.stations.dump.php');

eval("\$data = $s;");

usort($data, 'namesort');
function namesort($a, $b) {
    return strcasecmp($a['name'], $b['name']);
}

foreach($data as $item) {
    echo "{$item['name']}\t\t({$item['A']}, {$item['R']})\t\t";
    foreach ($item['_lines'] as $line)
        echo "{$line['name']}, ";
    echo "\n\n";
}
//var_dump($data);