<?php

require_once '../config.php';

if (!isset($_SERVER['HTTP_REFERER'])) {
    echo "Woah, what did you just try and do?";
} else {
    $return_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
}

foreach ($nagios_hosts as $host) {
    $hosts[] = $host['tag'];
}

$hostfilter = $_POST["hostfilter"];
unset($_POST["hostfilter"]);
setcookie('nagdash_hostfilter', $hostfilter, time()+60*60*24*365);
$select_last_state_change = $_POST['select_last_state_change'] ? $_POST['select_last_state_change'] : "0";
setcookie('select_last_state_change', $select_last_state_change, time()+60*60*24*365);
if (isset($_POST['sort_by_time'])) {
    setcookie('sort_by_time', '1', time()+60*60*24*365);
} else {
    setcookie('sort_by_time', '0', time()+60*60*24*365);
}
if (isset($_POST['sort_descending'])) {
    setcookie('sort_descending', '1', time()+60*60*24*365);
} else {
    setcookie('sort_descending', '0', time()+60*60*24*365);
}

if (isset($_POST['hide_ksps'])) {
    setcookie('hide_ksps', '1', time()+60*60*24*365);
} else {
    setcookie('hide_ksps', '0', time()+60*60*24*365);
}

$submitted_hosts = $_POST;
$unwanted_hosts = array_diff($hosts, array_keys($submitted_hosts));

setcookie('nagdash_unwanted_hosts', serialize($unwanted_hosts), time()+60*60*24*365);

Header("Location: {$return_path}");
