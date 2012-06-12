<?php
require_once('../../TlApi.php');
$_REQUEST['station'] = stripslashes($_REQUEST['station']);
?>

<div data-role="page" id="page-departures">

  <input type="hidden" name="line" id="input-line" value="<?php echo $_REQUEST['line'] ?>"/>
  <input type="hidden" name="station" id="input-station" value="<?php echo $_REQUEST['station'] ?>"/>

  <div data-role="header">
    <h1>Departures</h1>
  </div>

  <h3 style="margin:10px; border-bottom:1px solid gray;">
    <?php echo "{$_REQUEST['line']}, {$_REQUEST['station']}"?>
  </h3>

  <div data-role="content" style="padding-top:0" id="content-departures">
    <?php include ('_departures.php') ?>
  </div>

  <a href="javascript:update_departures()" data-role="button" data-icon="refresh" data-theme="e" style="text-align:left">
    <span style="margin-left:10px">Refresh</span>
  </a>

</div>