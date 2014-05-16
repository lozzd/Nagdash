<?php

require_once "NagiosConnection.php";
require_once "utils.php";

class NagiosAPI implements iNagiosConnection {

    function __construct($hostname, $port=6315, $protocol="https",
                         $url = null) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->protocol = $protocol;
        $this->url = empty($url) ? "/state" : $url;
    }

    public function getState() {
        $response =  NagdashHelpers::fetch_json($this->hostname, $this->port,
                                $this->protocol, $this->url);
        return $response;
    }

    /**
     * acknowledge a problem
     *
     * Parameter
     *  $details - array with problem meta data like
     *             [
     *              "hostname" => $host,
     *              "service" => $service,
     *              "comment" => $comment,
     *              "author" => $author,
     *              "duration" => $duration
     *              ]
     *
     * Returns an array of the form
     *  ["errors" => true/false, "details" => "message"]
     */
    public function acknowledge($details) {
        return $this->post_to_api("/acknowledge_problem", $details);

    }
    public function enableNotifications($details) {
        return $this->post_to_api("/enable_notifications", $details);
    }
    public function disableNotifications($details) {
        return $this->post_to_api("/disable_notifications", $details);
    }
    public function setDowntime($details) {
        return $this->post_to_api("/schedule_downtime", $details);
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

    /**
     * send an action to the api
     *
     * Parameters:
     *  $method  - endpoint to POST to
     *  $details - details about hostname, service, etc
     *  $payload - the payload to send
     *
     * Returns ["errors" => true/false, "details" => "details"]
     */
    public function post_to_api($method, $details) {
        $payload = json_encode($details);
        $params = array('http' =>
            array(
                'method' => 'POST',
                'header' => "Content-type: application/json",
                'content' => $payload,
            )
        );
        $service = $details["service"];
        $hostname = $details["host"];
        $context = stream_context_create($params);
        $nagios_url = "{$this->protocol}://{$this->hostname}:{$this->port}/{$method}";
        if(!$result = file_get_contents($nagios_url, false, $context)) {
            $error = error_get_last();
            return ["errors" => true,
                    "details" => "Command {$method} failed! <pre>{$error}</pre>"];
        } else {
            $return = json_decode($result);
            if ($return->success) {
                $service = (isset($service)) ? "-> {$service}" : null;
                return ["errors" => true,
                        "details" => "Command {$method} succeeded on {$hostname} {$service}"];
            } else {
                return ["errors" => true,
                        "details" => "Command {$method} failed! <pre>{$return->content}</pre>"];
            }
        }
    }

}
