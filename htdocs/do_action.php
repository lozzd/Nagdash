<?php
error_reporting(E_ALL ^ E_NOTICE);
require_once '../config.php';
require_once '../phplib/NagiosApi.php';
require_once '../phplib/NagiosLivestatus.php';
require_once '../phplib/utils.php';

$supported_methods = ["ack", "downtime", "enable", "disable"];

if (!isset($_POST['nag_host'])) {
    echo "Are you calling this manually? This should be called by Nagdash only.";
} else {
    $nagios_instance = $_POST['nag_host'];
    $action = $_POST['action'];
    $comment = $action == "ack" && isset($_POST['attribute']) ? $_POST['attribute'] : "{$action} from Nagdash";
    $details = [
            "host" => $_POST['hostname'],
            "service" => ($_POST['service']) ? $_POST['service'] : null,
            "author" => function_exists("nagdash_get_user") ? nagdash_get_user() : "Nagdash",
            "duration" => ($_POST['attribute']) ? ($_POST['attribute'] * 60) : null,
            "comment" => $comment
            ];


    if (!in_array($action, $supported_methods)) {
        echo "Nagios-api does not support this action ({$action}) yet. ";
    } else {

        foreach ($nagios_hosts as $host) {
            if ($host['tag'] == $nagios_instance) {
                $nagios_api = NagdashHelpers::get_nagios_api_object($api_type,
                    $host["hostname"], $host["port"], $host["protocol"], $host["url"]);
            }
        }

        switch ($action) {
        case "ack":
            $ret = $nagios_api->acknowledge($details);
            break;
        case "downtime":
            $ret =  $nagios_api->setDowntime($details);
            break;
        case "enable":
            $ret = $nagios_api->enableNotifications($details);
            break;
        case "disable":
            $ret = $nagios_api->disableNotifications($details);
            break;
        }

        echo $ret["details"];

    }
}


