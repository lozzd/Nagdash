<?php

require_once "NagiosConnection.php";
require_once "utils.php";

class NagiosLivestatus implements iNagiosConnection {

    function __construct($hostname, $port=6315, $protocol="https",
                         $url = "/state") {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->protocol = $protocol;
        $this->url = $url;
    }

    public function getState() {
        $hostname = $this->hostname;
        $port = $this->port;
        $protocol = $this->protocol;

        $ret = NagdashHelpers::fetch_json(
            $hostname, $port, $protocol,
            "/nagios/livestatus/index.php/hosts?" .
            "Columns=name,state,acknowledged,last_state_change,downtimes"
        );

        if ($ret["errors"] == true){
            return $ret["details"];
        }
        $state = $ret["details"];
        $curl_stats = $ret["curl_stats"];

        $curl_stats["$hostname:$port"]['objects'] = count($state);
        $munge = [];

        foreach ($state as $host) {
            $host['services'] = [];
            $munge[$host['name']] = $host;
        }
        $state = $munge;

        $ret = NagdashHelpers::fetch_json(
            $hostname, $port, $protocol,
            "/nagios/livestatus/index.php/services?" .
            "Columns=description,host_name,plugin_output,notifications_enabled," .
            "downtimes,scheduled_downtime_depth,state,last_state_change," .
            "current_attempt,max_check_attempts,acknowledged"
        );

        $services = $ret["details"];

        foreach ($services as $service) {
            $hostname = $service['host_name'];
            if ($state[$hostname]) {
                $state[$hostname]['services'][$service['description']] = $service;
            }
        }

        return ["errors" => false,
                "details" => $state,
                "curl_stats" => $curl_stats
            ];
    }

    public function acknowledge($problem) {
    }
    public function enableNotifications($target) {
    }
    public function disableNotifications($target) {
    }
    public function setDowntime($target, $duration) {
    }
    public function getColumnMapping() {
        return [
            'state' => 'state',
            'ack' => 'acknowledged',
            'max_attempts' => 'max_check_attempts',
            'service_name' => 'description',
            'host_name' => 'host_name',
        ];
    }

}
