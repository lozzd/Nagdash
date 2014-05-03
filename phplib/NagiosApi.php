<?php

require_once "NagiosConnection.php";
require_once "utils.php";

class NagiosAPI implements iNagiosConnection {

    function __construct($hostname, $port=6315, $protocol="https",
                         $url = "/state") {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->protocol = $protocol;
        $this->url = $url;
    }

    public function getState() {
        $response =  NagdashHelpers::fetch_json($this->hostname, $this->port,
                                $this->protocol, $this->url);
        return $response;
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
            'state' => 'current_state',
            'ack' => 'problem_has_been_acknowledged',
            'max_attempts' => 'max_attempts',
            'service_name' => 'service_name',
            'host_name' => 'name',
        ];
    }

}
