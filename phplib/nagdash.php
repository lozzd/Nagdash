<?php
error_reporting(E_ALL);
require_once '../config.php';
require_once 'utils.php';
require_once 'timeago.php';

// fall back to the nagios api
if (empty($CONFIG["nagiosapis"])) {
    $CONFIG["nagiosapis"] = ["NagiosApi"];
}

// require all the APIs
foreach ($CONFIG["nagiosapis"] as $api) {
    require_once "{$api}.php";
}

if (!function_exists('curl_init')) {
  die("ERROR: The PHP curl extension must be installed for Nagdash to function");
}

$nagios_host_status = array(0 => "UP", 1 => "DOWN", 2 => "UNREACHABLE");
$nagios_service_status
    = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN");
$nagios_host_status_colour
    = array(0 => "status_green", 1 => "status_red", 2 => "status_yellow");
$nagios_service_status_colour
    = array(0 => "status_green", 1 => "status_yellow", 2 => "status_red", 3 => "status_grey");

$nagios_toggle_status = array(0 => "disabled", 1 => "enabled");

$sort_by_time = ( isset($sort_by_time) && $sort_by_time ) ? true : false;

$errors = array();
$state = array();
$host_summary = array();
$service_summary = array();
$down_hosts = array();
$known_hosts = array();
$known_services = array();
$broken_services = array();
$curl_stats = array();

$api_cols = [];

// Function that does the dirty to connect to the Nagios API

function fetch_state($hostname, $port, $protocol, $api_type) {

    switch ($api_type) {
    case "livestatus":
        $nagios_api = new NagiosLivestatus($hostname, $port, $protocol);
        $ret = $nagios_api->getState();
        $state = $ret["details"];
        $curl_stats = $ret["curl_stats"];
        $mapping = $nagios_api->getColumnMapping();
        break;
    case "nagios-api":
        $nagios_api = new NagiosAPI($hostname, $port, $protocol);
        $ret = $nagios_api->getState();
        if ($ret["errors"] == true) {
            $state = $ret["details"];
        } else {
            $state = $ret["details"]["content"];
        }
        $curl_stats = $ret["curl_stats"];
        $mapping = $nagios_api->getColumnMapping();
        break;
    }

    return [$state, $mapping, $curl_stats];
}

// Check to see if the user has a cookie that disables some hosts
$unwanted_hosts = unserialize($_COOKIE['nagdash_unwanted_hosts']);
if (!is_array($unwanted_hosts)) $unwanted_hosts = array();

// Collect the API data from each Nagios host.
foreach ($nagios_hosts as $host) {
    // Check if the host has been disabled locally
    if (!in_array($host['tag'], $unwanted_hosts)) {
        list($host_state, $api_cols, $local_curl_stats) = fetch_state($host['hostname'],
            $host['port'], $host['protocol'], $api_type);
        $curl_stats = array_merge($curl_stats, $local_curl_stats);
        if (is_string($host_state)) {
            $errors[] = "Could not connect to API on host {$host['hostname']}, port {$host['port']}: {$host_state}";
        } else {
            foreach ($host_state as $this_host => $null) {
                $host_state[$this_host]['tag'] = $host['tag'];
            }
            $state += (array) $host_state;
        }
    }
}



if (isset($mock_state_file)) {
    $data = json_decode(file_get_contents($mock_state_file), true);
    $state = $data['content'];
}

// Sort the array alphabetically by hostname.
NagdashHelpers::deep_ksort($state);

// At this point, the data collection is completed.

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<div class='status_red'>{$error}</div>";
    }
}
foreach ($state as $hostname => $host_detail) {
    // Check if the host matches the filter
    if (preg_match("/$filter/", $hostname)) {
        // If the host is NOT OK...
        if ($host_detail[$api_cols['state']] != 0) {
            // Sort the host into the correct array. It's either a known issue or not.
            if ( ($host_detail[$api_cols['ack']] > 0) || ($host_detail['scheduled_downtime_depth'] > 0) || ($host_detail['notifications_enabled'] == 0) ) {
                $array_name = "known_hosts";
            } else {
                $array_name = "down_hosts";
            }

            // Populate the array.
            array_push($$array_name, array(
                "hostname" => $hostname,
                "host_state" => $host_detail{$api_cols['state']},
                "duration" => timeago($host_detail['last_state_change'], null, null, false),
                "detail" => $host_detail['plugin_output'],
                "current_attempt" => $host_detail['current_attempt'],
                "max_attempts" => $host_detail['max_attempts'],
                "tag" => $host_detail['tag'],
                "is_hard" => ($host_detail['current_attempt'] >= $host_detail['max_attempts']) ? true : false,
                "is_downtime" => ($host_detail['scheduled_downtime_depth'] > 0) ? true : false,
                "is_ack" => ($host_detail[$api_cols['ack']] > 0) ? true : false,
                "is_enabled" => ($host_detail['notifications_enabled'] > 0) ? true : false,
            ));
        }

        // In any case, increment the overall status counters.
        $host_summary[$host_detail[$api_cols['state']]]++;

        // Now parse the statuses for this host.
        foreach ($host_detail['services'] as $service_name => $service_detail) {

            // If the host is OK, AND the service is NOT OK.

            if ($service_detail[$api_cols['state']] != 0 && $host_detail[$api_cols['state']] == 0) {
                // Sort the service into the correct array. It's either a known issue or not.
                if ( ($service_detail[$api_cols['ack']] > 0)
                    || ($service_detail['scheduled_downtime_depth'] > 0)
                    || ($service_detail['notifications_enabled'] == 0 )
                    || ($host_detail['scheduled_downtime_depth'] > 0)
                ) {
                    $array_name = "known_services";
                } else {
                    $array_name = "broken_services";
                }
                $downtime_remaining = null;
                $downtimes = array_merge($service_detail['downtimes'], $host_detail['downtimes']);
                if ($host_detail['scheduled_downtime_depth'] > 0
                    || $service_detail['scheduled_downtime_depth'] > 0
                ) {
                    if (count($downtimes) > 0) {
                        $downtime_info = array_pop($downtimes);
                        $downtime_remaining = "- ". timeago($downtime_info['end_time'], null, null, false) . " left";
                    }
                }
                array_push($$array_name, array(
                    "hostname" => $hostname,
                    "service_name" => $service_name,
                    "service_state" => $service_detail[$api_cols['state']],
                    "duration" => timeago($service_detail['last_state_change'], null, null, false),
                    "last_state_change" => $service_detail['last_state_change'],
                    "detail" => $service_detail['plugin_output'],
                    "current_attempt" => $service_detail['current_attempt'],
                    "max_attempts" => $service_detail[$api_cols['max_attempts']],
                    "tag" => $host_detail['tag'],
                    "is_hard" => ($service_detail['current_attempt'] >= $service_detail[$api_cols['max_attempts']]) ? true : false,
                    "is_downtime" => ($service_detail['scheduled_downtime_depth'] > 0 || $host_detail['scheduled_downtime_depth'] > 0) ? true : false,
                    "downtime_remaining" => $downtime_remaining,
                    "is_ack" => ($service_detail[$api_cols['ack']] > 0) ? true : false,
                    "is_enabled" => ($service_detail['notifications_enabled'] > 0) ? true : false,
                ));
            }
            if ($host_detail['state'] == 0) {
                $service_summary[$service_detail[$api_cols['state']]]++;
            }
        }
    }
}
ksort($host_summary);
ksort($service_summary);
?>

<div id="info-window"><button class="close" onClick='$("#info-window").fadeOut("fast");'>&times;</button><div id="info-window-text"></div></div>
<div class="frame">
    <div class="section">
      <div class="header">
        <h3>Host status</h3>
        <p class="totals"><b>Total:</b> <?php foreach ($host_summary as $state => $count) { echo "<span class='{$nagios_host_status_colour[$state]}'>{$count}</span> "; } ?></p>
      </div>
<?php if (count($down_hosts) > 0) { ?>
    <table id="broken_hosts" class="widetable">
    <tr><th>Hostname</th><th width="150px">State</th><th>Duration</th><th>Attempts</th><th>Detail</th></tr>
<?php
    foreach ($down_hosts as $host) {
        echo "<tr id='host_row' class='{$nagios_host_status_colour[$host['host_state']]}'>";
        $tag = NagdashHelpers::print_tag($host['tag'], count($nagios_hosts));
        echo "<td>{$host['hostname']} " . $tag . " <span class='controls'>";
        NagdashHelpers::render('controls.php',[ "tag" => $host['tag'],
                                            "host" => $host['hostname'],
                                            "service" => '']);
        echo "</span></td>";
        echo "<td><blink>{$nagios_host_status[$host['host_state']]}</blink></td>";
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
        $tag = NagdashHelpers::print_tag($this_host['tag'], count($nagios_hosts));
        $known_host_list[] = "{$this_host['hostname']} " . $tag . " <span class='known_hosts_desc'>({$status_text} - {$this_host['duration']})</span>";
    }
    $known_host_list_complete = implode(" &bull; ", $known_host_list);
    echo "<table class='widetable known_hosts'><tr><td><b>Known Problem Hosts: </b> {$known_host_list_complete}</td></tr></table>";
}
?>

    </div>
</div>

<div class="frame">
    <div class="section">
      <div class="header">
        <h3>Service status</h3>
        <p class="totals"><b>Total:</b> <?php foreach($service_summary as $state => $count) { echo "<span class='{$nagios_service_status_colour[$state]}'>{$count}</span> "; } ?></p>
    </div>
<?php if (count($broken_services) > 0) { ?>
    <table class="widetable" id="broken_services">
    <tr><th width="30%">Hostname</th><th width="50%">Service</th><th width="10%">Duration</th><th width="5%">Attempt</th></tr>
<?php
    if ($sort_by_time) {
        usort($broken_services,'NagdashHelpers::cmp_last_state_change');
    }
    foreach($broken_services as $service) {
        $soft_style = ($service['is_hard']) ? "" : "status_soft";
        $blink_tag = ($service['is_hard'] && $enable_blinking) ? "<blink>" : "";
        $tag = NagdashHelpers::print_tag($service['tag'], count($nagios_hosts));
        echo "<tr>";
        echo "<td>{$service['hostname']} " . $tag . " <span class='controls'>";
        NagdashHelpers::render('controls.php', ["tag" => $service['tag'],
                                                "host" => $service['hostname'],
                                                "service" => $service['service_name']]);
        echo "</span></td>";
        echo "<td class='bold {$nagios_service_status_colour[$service['service_state']]} {$soft_style}'>{$blink_tag}{$service['service_name']}<span class='detail'>{$service['detail']}</span></td>";
        echo "<td>{$service['duration']}</td>";
        echo "<td>{$service['current_attempt']}/{$service['max_attempts']}</td>";
        echo "</tr>";
    }
?>
    </table>
<?php } else { ?>
    <table class="widetable status_green"><tr><td><b>All services OK</b></td></tr></table>
<?php }

if ($sort_by_time) {
    usort($known_services,'NagdashHelpers::cmp_last_state_change');
}

if (count($known_services) > 0) { ?>
    <h4>Known Service Problems</h4>
    <table class="widetable known_service" id="known_services">
    <tr><th width="30%">Hostname</th><th width="37%">Service</th><th width="18%">State</th><th width="10%">Duration</th><th width="5%">Attempt</th></tr>
<?php

    foreach($known_services as $service) {
        if ($service['is_ack']) $status_text = "ack";
        if ($service['is_downtime']) $status_text = "downtime {$service['downtime_remaining']}";
        if (!$service['is_enabled']) $status_text = "disabled";
        echo "<tr class='known_service'>";
        $tag = NagdashHelpers::print_tag($service['tag'], count($nagios_hosts));
        echo "<td>{$service['hostname']} " . $tag . "</td>";
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

<?php

echo "<!-- nagios-api server status: -->";
foreach ($curl_stats as $server => $server_stats) {
    echo "<!-- {$server_stats['url']} returned code {$server_stats['http_code']}, {$server_stats['size_download']} bytes ";
    echo "in {$server_stats['total_time']} seconds (first byte: {$server_stats['starttransfer_time']}). JSON parsed {$server_stats['objects']} hosts -->\n";
}

?>
