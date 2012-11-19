<?php

error_reporting(!E_NOTICE);
require_once 'config.php';
require_once 'timeago.php';

$nagios_host_status = array(0 => "UP", 1 => "DOWN", 2 => "UNREACHABLE");
$nagios_service_status = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN");
$nagios_host_status_colour = array(0 => "status_green", 1 => "status_red", 2 => "status_yellow");
$nagios_service_status_colour = array(0 => "status_green", 1 => "status_yellow", 2 => "status_red", 3 => "status_grey");

$nagios_toggle_status = array(0 => "disabled", 1 => "enabled");

$errors = array();
$state = array();
$host_summary = array();
$service_summary = array();
$down_hosts = array();
$known_hosts = array();
$known_services = array();
$broken_services = array();

// Function that does the dirty to connect to the Nagios API
function connectHost($hostname, $port, $protocol) {

    if (!$json = file_get_contents("{$protocol}://{$hostname}:{$port}/state")) {
        $error = error_get_last();
        return "Attempt to hit API failed, sorry. <pre>{$error['message']}</pre>";
    }

    if (!$state = json_decode($json, true)) {
        return "Attempt to hit API failed, sorry (JSON decode failed)";
    }
    return $state['content'];
}

// Collect the API data from each Nagios host. 
foreach ($nagios_hosts as $host) {
    $host_state = connectHost($host['hostname'], $host['port'], $host['protocol']);
    if (is_string($host_state)) {
        $errors[] = "Could not connect to API on host {$host['hostname']}, port {$host['port']}: {$host_state}";
    } else {
        // Add the tag
        foreach ($host_state as $this_host => $null) {
            $host_state[$this_host]['tag'] = $host['tag'];
        }
        $state += (array) $host_state;
    }
}


// Sort the array alphabetically by hostname. 
deep_ksort($state);

// At this point, the data collection is completed. 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nagios Dashboard</title>
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
    <script src="http://code.jquery.com/jquery-1.3.2.min.js"></script>
    <script>
    function showInfo(show_data) {
        $("#info-window").fadeIn("fast");
        $("#info-window-text").empty().append(show_data);
    }
    </script>
    <style type="text/css">
    h3                  { margin-top: 3px; margin-bottom: 3px; font-size: 1.5em }
    body                { font-family: "HelveticaNeue-Medium", Helvetica, Arial, sans-serif; margin: 10px; margin-top: 0px }
    table,td            { border: none; padding: 2px; border-spacing: 2px; font-size: 1.1em }
    table               { border: 1px solid #c6c6c6; background-color: #F0F0F0; border-collapse: separate; 
                            *border-collapse: collapse; -webkit-border-radius: 4px;
                            -moz-border-radius: 4px; border-radius: 4px; }
    th                  { border: 1px black solid; background-color: #D8D8D8 }
    .widetable          { width: 99%; }
    .bold               { font-weight: bold; }
    .status_green       { background-color: #269926; color: white; padding: 3px }
    .status_red         { background-color: #FF4040; color: white; padding: 3px }
    .status_yellow      { background-color: #FFDE40; color: black; padding: 3px }
    .status_grey        { background-color: #444444; color: white; padding: 3px }
    .known_hosts        { background-color: lightgrey; color: black }
    .known_hosts_desc   { color: #686868 }
    .desc               { font-size: 0.8em }
    #info-window-text   { padding: 30px; vertical-align: middle }
<?php foreach ($nagios_hosts as $host) { echo ".tag_{$host['tag']}   { background-color: {$host['tagcolour']} }\n"; } ?>
    .tag                { font-size: 0.6em; color: white; padding: 4px; -webkit-border-radius: 5px; }
    .left               { float: left}
    .totals             { text-align: right; right: 10px; padding: 5px; border: 1px #848484 solid; position: absolute; background: #F0F0F0; 
                            -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; margin-top: 5px; margin-bottom: 5px; }
    table#broken_services tr td span.controls { display: none; float: right }
    table#broken_services tr:hover td span.controls { display:inline-block; }
    #info-window        { display: none; position: absolute; top: 50%; width: 400px; text-align: center; left: 50%; margin-left: -200px;
                          border: 1px #848484 solid; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; margin-top: -75px;
                          background: #F0F0F0; font-family: "HelveticaNeue-Medium", Helvetica, Arial, sans-serif; padding: 20px }
    .known_service      { font-size: 1em }
    </style>

</head>

<body>
<?php

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<div class='status_red'>{$error}</div>";
    }
}
foreach($state as $hostname => $host_detail) {
    // Check if the host matches the filter
    if (preg_match("/$filter/", $hostname)) {
        // If the host is NOT OK...
        if ($host_detail['current_state'] != 0) {
            // Sort the host into the correct array. It's either a known issue or not. 
            if ( ($host_detail['problem_has_been_acknowledged'] > 0) || ($host_detail['scheduled_downtime_depth'] > 0) || ($host_detail['notifications_enabled'] == 0) ) {
                $array_name = "known_hosts";
            } else {
                $array_name = "down_hosts";
            }

            // Populate the array. 
            array_push($$array_name, array(
                "hostname" => $hostname,
                "host_state" => $host_detail['current_state'],
                "duration" => timeago($host_detail['last_state_change'], null, null, false),
                "detail" => $host_detail['plugin_output'],
                "current_attempt" => $host_detail['current_attempt'],
                "max_attempts" => $host_detail['max_attempts'],
                "tag" => $host_detail['tag'],
                "is_hard" => ($host_detail['current_attempt'] >= $host_detail['max_attempts']) ? true : false,
                "is_downtime" => ($host_detail['scheduled_downtime_depth'] > 0) ? true : false,
                "is_ack" => ($host_detail['problem_has_been_acknowledged'] > 0) ? true : false,
                "is_enabled" => ($host_detail['notifications_enabled'] > 0) ? true : false,
            )); 
        }

        // In any case, increment the overall status counters.
        $host_summary[$host_detail['current_state']]++;

        // Now parse the statuses for this host. 
        foreach($host_detail['services'] as $service_name => $service_detail) {
            // If the host is OK, AND the service is NOT OK. 
            if ($service_detail['current_state'] != 0 && $host_detail['current_state'] == 0) {
                // Sort the service into the correct array. It's either a known issue or not. 
                if ( ($service_detail['problem_has_been_acknowledged'] > 0) || ($service_detail['scheduled_downtime_depth'] > 0) || ( $service_detail['notifications_enabled'] == 0 )) {
                    $array_name = "known_services";
                } else {
                    $array_name = "broken_services";
                }
                array_push($$array_name, array(
                    "hostname" => $hostname,
                    "service_name" => $service_name,
                    "service_state" => $service_detail['current_state'],
                    "duration" => timeago($service_detail['last_state_change'], null, null, false),
                    "detail" => $service_detail['plugin_output'],
                    "current_attempt" => $service_detail['current_attempt'],
                    "max_attempts" => $service_detail['max_attempts'],
                    "tag" => $host_detail['tag'],
                    "is_hard" => ($service_detail['current_attempt'] >= $service_detail['max_attempts']) ? true : false,
                    "is_downtime" => ($service_detail['scheduled_downtime_depth'] > 0) ? true : false,
                    "is_ack" => ($service_detail['problem_has_been_acknowledged'] > 0) ? true : false,
                    "is_enabled" => ($service_detail['notifications_enabled'] > 0) ? true : false,
                ));
            } 
            if ($host_detail['current_state'] == 0) {
                $service_summary[$service_detail['current_state']]++;
            }
        }
    } 
}
?>

<div id="info-window"><button class="close" onClick='$("#info-window").fadeOut("fast");'>&times;</button><div id="info-window-text"></div></div>
<div id="frame">
    <div class="section">
    <p class="totals"><b>Total:</b> <?php foreach($host_summary as $state => $count) { echo "<span class='{$nagios_host_status_colour[$state]}'>{$count}</span> "; } ?></p>
<?php if (count($down_hosts) > 0) { ?>
    <table id="broken_hosts" class="widetable">
    <tr><th>Hostname</th><th width="150px">State</th><th>Duration</th><th>Attempts</th><th>Detail</th></tr>
<?php
    foreach($down_hosts as $host) {
        echo "<tr id='host_row' class='{$nagios_host_status_colour[$host['host_state']]}'>";
        echo "<td>{$host['hostname']} <span class='tag tag_{$host['tag']}'>{$host['tag']}</span></td>";
        echo "<td>{$nagios_host_status[$host['host_state']]}</td>"; 
        echo "<td>{$host['duration']}</td>";
        echo "<td>{$host['current_attempt']}/{$host['max_attempts']}</td>";
        echo "<td class=\"desc\">{$host['detail']}</td>";
        echo "</tr>";
    }
?>
    </table>
<?php } else { ?>
    <table class="widetable status_green"><tr><td><b>All hosts OK</b></td></tr></table>
<?php 

}
if (count($known_hosts) > 0) {
    foreach ($known_hosts as $this_host) {
        if ($this_host['is_ack']) $status_text = "ack";
        if ($this_host['is_downtime']) $status_text = "downtime";
        if (!$this_host['is_enabled']) $status_text = "disabled";
        $known_host_list[] = "{$this_host['hostname']} <span class='tag tag_{$this_host['tag']}'>{$this_host['tag']}</span> <span class='known_hosts_desc'>({$status_text} - {$this_host['duration']})</span>";
    } 
    $known_host_list_complete = implode(" &bull; ", $known_host_list);
    echo "<table class='widetable known_hosts'><tr><td><b>Known Problem Hosts: </b> {$known_host_list_complete}</td></tr></table>";
}
?>

    </div>
</div>

<div id="frame">
    <div class="section">
    <h3 class='left'>Service problems</h3>
    <p class="totals"><b>Total:</b> <?php foreach($service_summary as $state => $count) { echo "<span class='{$nagios_service_status_colour[$state]}'>{$count}</span> "; } ?></p>
<?php if (count($broken_services) > 0) { ?>
    <table class="widetable" id="broken_services">
    <tr><th width="30%">Hostname</th><th width="40%">Service</th><th width="15%">State</th><th width="10%">Duration</th><th width="5%">Attempt</th></tr>
<?php
    foreach($broken_services as $service) {
        if ($service['is_hard']) { $soft_tag = ""; } else { $soft_tag = "(soft)"; }
            $controls = "<a href='#' onClick=\"$.post('do_action.php', { 
                                                    nag_host: '{$service['tag']}', 
                                                    hostname: '{$service['hostname']}',
                                                    service: '{$service['service_name']}', 
                                                    action: 'ack' }, 
                                                    function(data) { showInfo(data) } ); return false;\" class='btn'>
                                                    <i class='icon-check'></i> Ack </a>";
            $controls .="<a href='#' onClick=\"$.post('do_action.php', { 
                                                    nag_host: '{$service['tag']}', 
                                                    hostname: '{$service['hostname']}',
                                                    service: '{$service['service_name']}', 
                                                    action: 'downtime' }, 
                                                    function(data) { showInfo(data) } ); return false;\" class='btn'>
                                                    <i class='icon-time'></i> Downtime</a>";
        echo "<tr>";
        echo "<td>{$service['hostname']} <span class='tag tag_{$service['tag']}'>{$service['tag']}</span> <span class='controls'>{$controls}</span></td>";
        echo "<td class='bold {$nagios_service_status_colour[$service['service_state']]}'>{$service['service_name']}</td>";
        echo "<td class='{$nagios_service_status_colour[$service['service_state']]}'>{$nagios_service_status[$service['service_state']]} {$soft_tag}</td>";
        echo "<td>{$service['duration']}</td>";
        echo "<td>{$service['current_attempt']}/{$service['max_attempts']}</td>";
        echo "</tr>";
    }
?>
    </table>
<?php } else { ?>
    <table class="widetable status_green"><tr><td><b>All services OK</b></td></tr></table>
<?php } 

if (count($known_services) > 0) { ?>
    <h4>Known Service Problems</h4>
    <table class="widetable known_service" id="known_services">
    <tr><th width="30%">Hostname</th><th width="37%">Service</th><th width="18%">State</th><th width="10%">Duration</th><th width="5%">Attempt</th></tr>
<?php 
    foreach($known_services as $service) {
        if ($service['is_ack']) $status_text = "ack";
        if ($service['is_downtime']) $status_text = "downtime";
        if (!$service['is_enabled']) $status_text = "disabled";
        echo "<tr class='known_service'>";
        echo "<td>{$service['hostname']} <span class='tag tag_{$service['tag']}'>{$service['tag']}</td>";
        echo "<td>{$service['service_name']}</td>";
        echo "<td class='{$nagios_service_status_colour[$service['service_state']]}'>{$nagios_service_status[$service['service_state']]} ({$status_text})</td>";
        echo "<td>{$service['duration']}</td>";
        echo "<td>{$service['current_attempt']}/{$service['max_attempts']}</td>";
        echo "</tr>";
    }
?>

    </table>
<?php } ?>

    </div>
</div>


</body>
</html>

<?php


// Utility function to sort the aggregated array by keys. 
function deep_ksort(&$arr) { 
    ksort($arr); 
    foreach ($arr as &$a) { 
        if (is_array($a) && !empty($a)) { 
            deep_ksort($a); 
        } 
    } 
} 

?>
