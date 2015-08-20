<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once('../config.php');
require_once('../phplib/utils.php');

if (array_key_exists('nagdash_unwanted_hosts', $_COOKIE)) {
    $unwanted_hosts = unserialize($_COOKIE['nagdash_unwanted_hosts']);
} else {
    $unwanted_hosts = array();
}

if (!is_array($unwanted_hosts)) $unwanted_hosts = array();

?>
<html>
<head>
<title>Nagios Dashboard</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
<script src="js/nagdash.js"></script>
<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/blinkftw.css">
<link rel="stylesheet" href="css/main.css">
<style type="text/css">
  <?php foreach ($nagios_hosts as $host) {
        echo ".tag_{$host['tag']}   { background-color: {$host['tagcolour']} }\n";
  } ?>
</style>
</head>
<body>
  <div id="spinner"><h3><img src="images/ajax-loader.gif" align="absmiddle"> Refreshing...</h3></div>
  <div id="nagioscontainer"></div>
  <?php NagdashHelpers::render("settings_dialog.php", ["nagios_hosts" => $nagios_hosts,
                                                       "unwanted_hosts" => $unwanted_hosts]);?>


<script>
    $(document).keypress(function(e) {
        if (e.which == 115) { // "s"
            $("#settings_modal").modal();
        }
    });
    $(document).ready(load_nagios_data(<?php echo ($show_refresh_spinner === true)?>));
</script>
</body>
</html>
