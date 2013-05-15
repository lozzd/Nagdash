<html>
<head>
<title>Nagios Dashboard</title>
<style>
    #spinner    { position: absolute; top: 10px; width: 300px; text-align: center; left: 50%; margin-left: -150px;
                  border: 1px #848484 solid; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
                  background: #F0F0F0; font-family: "HelveticaNeue-Medium", Helvetica, Arial, sans-serif; }
</style>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/blinkftw.css">
<link rel="stylesheet" href="css/main.css">
<script>
  function showInfo(show_data) {
      $("#info-window").fadeIn("fast");
      $("#info-window-text").empty().append(show_data);
  }
$(document).ready(function() {
    $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
    var refreshId = setInterval(function() {
        $("#spinner").fadeIn("fast");
        $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
    }, 20000);
    $.ajaxSetup({ cache: false });
});
$(document).keypress("s", function(e) {
    $("#settings_modal").modal();
});
</script>
</head>
<body>
<div id="spinner"><h3><img src="ajax-loader.gif" align="absmiddle"> Refreshing...</h3></div>
<div id="nagioscontainer"></div>
<?php
error_reporting(E_ALL ^ E_NOTICE);
include_once('config.php');
$unwanted_hosts = unserialize($_COOKIE['nagdash_unwanted_hosts']);
if (!is_array($unwanted_hosts)) $unwanted_hosts = array();
echo build_settings_dialog($nagios_hosts, $unwanted_hosts);

?>
</body>
</html>


<?php

function build_settings_dialog($nagios_hosts, $unwanted_hosts) {
    // Creates a modal dialog which allows the user to select which instances they want to display on this copy of Nagdash.
    // $unwanted_hosts is an array that is retrieved from a cookie set if the user has already selected some.
    $html = '<div id="settings_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">';
    $html .= '<div class="modal-header">';
    $html .= '  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</a></button>';
    $html .= '  <h3>Nagdash Settings</h3>';
    $html .= '</div>';
    $html .= '<div class="modal-body">';
    $html .= '<form id="settings-form" action="do_settings.php" method="post"><fieldset>';
    $html .= '<legend>Instances to show</legend>';
    foreach ($nagios_hosts as $host) {
        $checked = (!in_array($host['tag'], $unwanted_hosts)) ? "checked" : "";
        $html .= '<label class="checkbox inline tag_label tag_' . $host['tag'] . '">';
        $html .= '<input type="checkbox" name="' . $host['tag'] . '" value="' . $host['tag'] . '"' . $checked . '>' . $host['tag'];
        $html .= '</label>';
    }
    $html .= '</fieldset></form>';
    $html .= '</div>';
    $html .= '<div class="modal-footer">';
    $html .= '  <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>';
    $html .= '  <button class="btn btn-primary" onClick="$(\'#settings-form\').submit();">Save changes</button>';
    $html .= '</div>';
    $html .= "</div>";

    return $html;
}

?>
