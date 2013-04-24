<?php

require_once 'config.php';

if (!isset($_SERVER['HTTP_REFERER'])) {
    echo "Woah, what did you just try and do?";
} else {
    $return_path = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
}

foreach ($nagios_hosts as $host) {
    $hosts[] = $host['tag'];
}

$submitted_hosts = $_POST;
$unwanted_hosts = array_diff($hosts, array_keys($submitted_hosts));

setcookie('nagdash_unwanted_hosts', serialize($unwanted_hosts), time()+60*60*24*365);
Header("Location: {$return_path}");
