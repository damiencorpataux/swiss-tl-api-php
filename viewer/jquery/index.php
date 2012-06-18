<?php
require_once('../../TlApi.php');
$_REQUEST['line'] = @$_REQUEST['line'] ? $_REQUEST['line'] : 4;
?>
<html>
  <head>
    <meta charset="utf-8">
    <title>Buses (jQuery)</title>
    <link rel="stylesheet" href="js/jquery-mobile/jquery.mobile-1.0a4.1.min.css" />
    <script src="js/jquery/jquery-1.5.2.min.js"></script>
    <script src="js/jquery-mobile/jquery.mobile-1.0a4.1.min.js"></script>
  </head>
  <body>
    <div data-role="page" id="page-stations">

      <div data-role="header">
        <h1>Select a Station</h1>
        <div style="text-align:center; font-size:10px; background-color:#ff0; color:black; text-shadow:none">
          Try the raw API
          <a href="/tl/api" target="_blank">here</a>
        </div>
      </div>

      <div data-role="content">
        <div data-role="fieldcontain" style="text-align:center;padding-top:0">
          <select name="select-line" id="select-line">
<?php
$provider = new TlLinesProvider();
$lines = $provider->get();
foreach ($lines as $line) {
    $selected = ($line['id']==$_REQUEST['line']) ? ' selected="selected"' : '';
    echo "<option value=\"{$line['id']}\"{$selected}>{$line['name']}</option>";
}
?>
          </select>
        </div>

        <ul data-role="listview" data-inset="true" data-filter="true" id="stations">
          <?php include('_stations.php'); ?>
        </ul>

      </div>
    </div>

    <script type="text/javascript">
        var stations_xhr = null;
        function update_stations() {
            var line = $('#select-line').val();
            var list = $('#stations');
            stations_xhr = $.ajax({
                url: "_stations.php",
                data: { line: line },
                async: true,
                beforeSend: function() {
                    list.html(null);
                    $.mobile.pageLoading()
                },
                complete: function() {$.mobile.pageLoading(true)},
                success: function(data) {
                    list.html(data);
                    list.listview("refresh");
                },
                error: function(response, error) {
                    list.html("Unable to load stations list. Please select another line.");
                },
            });
        }
        $('#select-line').live('change', function() {
            if (stations_xhr) stations_xhr.abort();
            update_stations();
        })
        // Departures
        function update_departures() {
            var content = $('#content-departures');
            var line = $('#input-line').val();
            var station = $('#input-station').val();
            departures_xhr = $.ajax({
                url: "_departures.php",
                data: {
                    line: line,
                    station: station,
                    _dc: new Date()
                },
                beforeSend: function() { $.mobile.pageLoading() },
                complete: function() {
                    $.mobile.pageLoading(true);
                },
                success: function(data) {
                    content.html(data);
                    $('.list-departures').listview();
                },
                error: function(response, error) {
                    list.html("Unable to load departures.");
                },
            });
        }
        function defer_update_departures() {
            timeout_departures = setTimeout(update_departures, 5000);
        }
        function clear_update_departures() {
            if (timeoute_departures) clearTimeout(timeout_departures);
        }
        //$('#page-departures').live('pageshow', defer_update_departures);
        //$('#page-departures').live('pagehide', clear_update_departures);
    </script>
  </body>
</html>
