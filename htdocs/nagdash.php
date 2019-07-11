<?php
error_reporting(E_ALL);
require_once '../config.php';
require_once '../phplib/utils.php';
require_once '../phplib/timeago.php';
require_once '../phplib/NagiosApi.php';
require_once '../phplib/NagiosLivestatus.php';


if (!function_exists('curl_init')) {
  die("ERROR: The PHP curl extension must be installed for Nagdash to function");
}

$nagios_host_status = array(0 => "UP", 1 => "DOWN", 2 => "UNREACHABLE");
$nagios_service_status = array(0 => "OK", 1 => "WARNING", 2 => "CRITICAL", 3 => "UNKNOWN");
$nagios_host_status_colour = array(0 => "status_green", 1 => "status_red", 2 => "status_yellow");
$nagios_service_status_colour = array(0 => "status_green", 1 => "status_yellow", 2 => "status_red", 3 => "status_grey");

$nagios_toggle_status = array(0 => "disabled", 1 => "enabled");

$sort_by_time = ( isset($sort_by_time) && $sort_by_time ) ? true : false;

// Check to see if the user has a cookie disable some nagios instances
if (array_key_exists('nagdash_unwanted_hosts', $_COOKIE)) {
    $unwanted_hosts = unserialize($_COOKIE['nagdash_unwanted_hosts']);
} else {
    $unwanted_hosts = array();
}

if (!is_array($unwanted_hosts)) $unwanted_hosts = array();


// Check to see if the user has a cookie to filter out unrequired hosts
if (array_key_exists('nagdash_hostfilter', $_COOKIE)) {
    $cookie_filter = $_COOKIE['nagdash_hostfilter'];
}

if (!empty($cookie_filter)) {
    $filter = $cookie_filter;
}

// If the user wants to filter by last state change, grab the filter value.
// Else default to '0' (we won't filter by last state change).
// IDEA: Perhaps we create a $filter array tp pass to
// NagdashHelpers::parse_nagios_host_data() that contains the values of 'hostfilter',
// 'select_last_state_change', 'sort_by_time', and 'sort_descending'.
if (isset($_COOKIE['select_last_state_change'])) {
    $filter_select_last_state_change = (int) $_COOKIE['select_last_state_change'];
} else {
    $filter_select_last_state_change = 0;
}

// If 1, the user wants to sort by time; 0 if not.
if (isset($_COOKIE['sort_by_time'])) {
    $filter_sort_by_time = (int) $_COOKIE['sort_by_time'];
}

// If 1, the user wants to sort descending; 0 if not.
if (isset($_COOKIE['sort_descending'])) {
    $filter_sort_descending = (int) $_COOKIE['sort_descending'];
}

// Collect the API data from each Nagios host.

if (isset($mock_state_file)) {
    $data = json_decode(file_get_contents($mock_state_file), true);
    $state = $data['content'];
    $errors = [];
    $curl_stats = [];
    $api_cols = [];
} else {
    list($state, $api_cols, $errors, $curl_stats) = NagdashHelpers::get_nagios_host_data($nagios_hosts,
        $unwanted_hosts, $api_type);
}

// Sort the array alphabetically by hostname.
NagdashHelpers::deep_ksort($state);

// At this point, the data collection is completed.

if (count($errors) > 0) {
    foreach ($errors as $error) {
        echo "<div class='status_red'>{$error}</div>";
    }
}
list($host_summary, $service_summary, $down_hosts, $known_hosts, $known_services, $broken_services) = NagdashHelpers::parse_nagios_host_data($state, $filter, $api_cols, $filter_select_last_state_change);
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
        echo "<td>{$host['current_attempt']}/{$host['max_check_attempts']}</td>";
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
    // Check for the presence of the 'sort_by_time' cookie, then the static config value.
    if ((isset($filter_sort_by_time) && $filter_sort_by_time == 1) || $sort_by_time) {
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

// Check for the presence of the 'sort_by_time' cookie, then the static config value.
if ((isset($filter_sort_by_time) && $filter_sort_by_time == 1) || $sort_by_time) {
    usort($known_services,'NagdashHelpers::cmp_last_state_change');
}

if (count($known_services) > 0) { ?>
    <h4>Known Service Problems</h4>
    <table class="widetable known_service" id="known_services">
    <tr><th width="25%">Hostname</th><th width="20%">Service</th><th width="15%">Comment</th><th width="18%">State</th><th width="10%">Duration</th><th width="5%">Attempt</th></tr>
<?php

    foreach($known_services as $service) {
        if ($service['is_ack']) $status_text = "ack";
        if ($service['is_downtime']) $status_text = "downtime {$service['downtime_remaining']}";
        if (!$service['is_enabled']) $status_text = "disabled";
        echo "<tr class='known_service'>";
        $tag = NagdashHelpers::print_tag($service['tag'], count($nagios_hosts));
		$service_comment = '';
		if(!empty($service['comments'])) {
			foreach($service['comments'] as $comment) {
                $service_comment .= "<li>".$comment['comment_data']."</li>";
			}
		}
		
        echo "<td>{$service['hostname']} " . $tag . "</td>";
        echo "<td>{$service['service_name']}</td>";
		echo "<td>{$service_comment}</td>";
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
<?php echo "Generated @ ".date("H:i:s"); ?>

<?php

echo "<!-- nagios-api server status: -->";
foreach ($curl_stats as $server => $server_stats) {
    echo "<!-- {$server_stats['url']} returned code {$server_stats['http_code']}, {$server_stats['size_download']} bytes ";
    echo "in {$server_stats['total_time']} seconds (first byte: {$server_stats['starttransfer_time']}). JSON parsed " . (isset($server_stats['objects']) ? $server_stats['objects'] : null) . " hosts -->\n";
}

?>
