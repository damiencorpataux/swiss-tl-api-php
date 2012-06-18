<?php

require_once('../../TlApi.php');

function jlist($departures, $direction) {
    $direction = utf8_encode($direction); // This feels freaky
    if (!$departures) {
        $departures = array();
        $html_list = "<li style=\"height:30px\">No&nbsp;service</li>";
    }
    foreach ($departures as $departure) {
        $time = ($departure['time']=='0 min') ? 'now' : $departure['time'];
        $time = str_replace(' ', '&nbsp;', $time);
        @$html_list .= "<li>{$time}</li>";
    }
    $html_direction = ($direction) ? "Direction<br/>{$direction}" : '<br/><br/>';
    echo implode('', array(
        "<h5>{$html_direction}</h5>",
        '<ul data-role="listview" data-inset="true" data-theme="d" class="list-departures">',
        $html_list,
        '</ul>'
    ));
}

$provider = new TlDeparturesProvider(array(
    'station' => stripslashes($_REQUEST['station']),
    'line'=> $_REQUEST['line']
));
$departures = $provider->get();
foreach ($departures as $d) $directions[$d['direction']][] = $d;

?>

    <style> h5 { margin:5px 10px 0px 15px } </style>
    <div class="ui-grid-a">
      <div class="ui-block-a">
        <?php jlist($directions['A'], @$directions['A'][0]['destination']); ?>
      </div>
      <div class="ui-block-b">
        <?php jlist($directions['R'], @$directions['R'][0]['destination']); ?>
      </div>
    </div>

<?php echo '<div style="text-align:right;font-size:10px">Updated at '.date('H:i:s').'</div>' ?>
