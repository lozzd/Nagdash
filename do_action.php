<?php
error_reporting(!E_NOTICE);
require_once 'config.php';

if (!isset($_POST['nag_host'])) {
    echo "Are you calling this manually? This should be called by Nagdash only.";
} else {
    $nagios_instance = $_POST['nag_host'];
    $hostname = $_POST['hostname'];
    $service = $_POST['service'];
    $action = $_POST['action'];

    switch ($action) {
    case "ack":
        $method = "acknowledge_problem";
        break;
    case "downtime":
        $method = "schedule_downtime";
        $duration = 60 * $_POST['duration'];
        break;
    case "enable":
        $method = "enable_notifications";
        break;
    case "disable":
        $method = "disable_notifications";
        break;
    }

    if (!$method) {
        echo "Nagios-api does not support this action ({$action}) yet. ";
    } else {
        foreach ($nagios_hosts as $host) {
            if ($host['tag'] == $nagios_instance) {
                $nagios_url = $host['protocol'] . "://" . $host['hostname'] . ":" . $host['port'] . "/" . $method;
            }
        }
        $payload = json_encode(array("host" => $hostname, "service" => $service, "comment" => "{$method} from Nagdash", "author" => "Nagdash", "duration" => $duration));
        $params = array('http' =>
            array(
                'method' => 'POST',
                'header' => "Content-type: application/json",
                'content' => $payload,
            )
        );
        $context = stream_context_create($params);
        if(!$result = file_get_contents($nagios_url, false, $context)) {
            $error = error_get_last();
            echo "Command {$method} failed! <pre>{$error}</pre>";
        } else {
            $return = json_decode($result); 
            if ($return->success) {
                echo "Command {$method} succeeded on {$hostname} -> {$service}";
            } else {
                echo "Command {$method} failed! <pre>{$return->content}</pre>";
            }
        }
    }
}


