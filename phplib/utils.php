<?php

class NagdashHelpers {

    static function print_tag($tag_name, $nagios_hostcount) {
        if (($nagios_hostcount) > 1) {
            return "<span class='tag tag_{$tag_name}'>{$tag_name}</span>";
        } else {
            return false;
        }
    }

    /**
     * Fetch JSON data from an HTTP endpoint
     *
     * Parameters
     *  $hostname - the hostname of the endpoint
     *  $port     - the port to connect to
     *  $protocol - the protocol used (http or https)
     *  $url      - the endpoint url on the host
     *
     *  Return an array of the form
     *  [ "errors" => true/false, "details" => "json_decoded data",
     *    "curl_stats" => "stats from the curl call"]
     */
    static function fetch_json($hostname,$port,$protocol,$url) {

        $ch = curl_init("$protocol://$hostname:$port$url");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $json = curl_exec($ch);

        $info = curl_getinfo($ch);

        $ret = ["errors" => false];
        if (curl_errno($ch)) {
            $errmsg = "Attempt to hit API failed, sorry. ";
            $errmsg .= "Curl said: " . curl_error($ch);
            return ["errors" => true,
                    "details" => $errmsg ];

        } elseif ($info['http_code'] != 200) {
            $errmsg = "Attempt to hit API failed, sorry. ";
            $errmsg .= "Curl said: HTTP Status {$info['http_code']}";
            return ["errors" => true,
                    "details" => $errmsg ];
        } else {
            $ret["curl_stats"] = ["$hostname:$port" => curl_getinfo($ch)];
            $ret["details"] = json_decode($json, true);
        }

        curl_close($ch);
        return $ret;
    }


    static function deep_ksort(&$arr) {
        if (isset($_COOKIE['sort_descending'])) {
            $filter_sort_descending = (int) $_COOKIE['sort_descending'];
        } else {
            $filter_sort_descending = false;
        }
        if ($filter_sort_descending) {
            krsort($arr);
            foreach ($arr as &$a) {
                if (is_array($a) && !empty($a)) {
                    NagdashHelpers::deep_ksort($a);
                }
            }
        } else {
            ksort($arr);
            foreach ($arr as &$a) {
                if (is_array($a) && !empty($a)) {
                    NagdashHelpers::deep_ksort($a);
                }
            }
        }
    }

    /**
     * stupid template rendering function. This basically just works around
     * the whole global variables thing and gives you a way to pass variables
     * to a PHP rendered template.
     *
     * Parameters:
     *   $template - path to the template to render (relative to callsite)
     *   $vars     - array of variables used for rendering
     *
     * Returns nothing but renders the template in place
     */
    static function render($template, $vars = []) {
        extract($vars);
        include $template;
    }

    /**
     * helper function to compare last state change
     *
     * Parameter:
     *   $a - first state
     *   $b - second state
     *
     * Returns -1, 0 or 1 depending on state comparison
     */
    static function cmp_last_state_change($a,$b) {
        if (isset($_COOKIE['sort_descending'])) {
            $filter_sort_descending = (int) $_COOKIE['sort_descending'];
        } else {
            $filter_sort_descending = false;
        }
        if ($filter_sort_descending) {
            if ($a['last_state_change'] == $b['last_state_change']) return 0;
            return ($b['last_state_change'] > $a['last_state_change']) ? -1 : 1;
        } else {
            if ($a['last_state_change'] == $b['last_state_change']) return 0;
            return ($a['last_state_change'] > $b['last_state_change']) ? -1 : 1;
        }
    }

    /**
     * get the correct state data based on the api type
     *
     * Parameters:
     *  $hostname - hostname of the nagios instance
     *  $port     - port the nagios api instance is listening on
     *  $protocol - the protocol to use for the transport (http/s)
     *  $api_type - the type of API to use (nagiosapi, livestatus, ...)
     *
     * Returns an array of [$state, $mapping, $curl_stats]
     */
    static function fetch_state($hostname, $port, $protocol, $url, $api_type) {

        $nagios_api = NagdashHelpers::get_nagios_api_object($api_type, $hostname,
            $port, $protocol, $url);
        // TODO: fix up the API implementations so they return the same
        // formatted data. There is no real need to have this switch case here
        switch ($api_type) {
        case "livestatus":
            $ret = $nagios_api->getState();
            $state = $ret["details"];
            $curl_stats = $ret["curl_stats"];
            $mapping = $nagios_api->getColumnMapping();
            break;
        case "nagios-api":
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

    /**
     * get the host data from all nagios instances
     *
     * Parameters:
     *  $nagios_hosts   - nagios hosts configuration array
     *  $unwanted_hosts - list of unwanted tags for the user
     *  $api_type       - API type to use
     *
     *  Returns [$state, $api_cols, $errors, $curl_stats]
     */
    static function get_nagios_host_data($nagios_hosts, $unwanted_hosts, $api_type) {
        $state  = [];
        $errors = [];
        $curl_stats = [];
        $api_cols = [];
        foreach ($nagios_hosts as $host) {
            // Check if the host has been disabled locally
            if (!in_array($host['tag'], $unwanted_hosts)) {
                list($host_state, $api_cols, $local_curl_stats) = NagdashHelpers::fetch_state($host['hostname'],
                    $host['port'], $host['protocol'], isset($host['url']) ? $host['url'] : null, $api_type);
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

        return [$state, $api_cols, $errors, $curl_stats];
    }

    /**
     * parse the state array into a format that we can easily display
     *
     * Parameters:
     *  $state  - the array of states from all nagios instances
     *  $filter - the regex to filter out hosts
     *  $api_cols - API column mapping from the nagios API
     *  $filter_select_last_state_change - A numeric string we'll use to filter by last state change
     *
     *  Returns [$host_summary, $service_summary, $down_hosts, $known_hosts, $known_services, $broken_services];
     */
    static function parse_nagios_host_data($state, $filter, $api_cols, $filter_select_last_state_change) {

        $host_summary = array();
        $service_summary = array();
        $down_hosts = array();
        $known_hosts = array();
        $known_services = array();
        $broken_services = array();

        // The user may filter by last state change, to limit how much output to review.
        $state_change_backstop = 0; # Default to all objects.
        if ($filter_select_last_state_change > 0) {
            $state_change_backstop = time() - $filter_select_last_state_change;
        }
        foreach ($state as $hostname => $host_detail) {
            // Check if the host matches the filter
            if (preg_match("/$filter/", $hostname)) {
                // If the host is NOT OK...
                if ($host_detail[$api_cols['state']] != 0) {
                    // Sort the host into the correct array. It's either a known issue or not.
                    if ( ($host_detail[$api_cols['ack']] > 0) || ((isset($host_detail['scheduled_downtime_depth']) && $host_detail['scheduled_downtime_depth'] > 0)) || ($host_detail['notifications_enabled'] == 0) ) {
                        $array_name = "known_hosts";
                    } else {
                        $array_name = "down_hosts";
                    }
                    // Populate the array.
                    if ($host_detail['last_state_change'] >= $state_change_backstop) {
                        array_push($$array_name, array(
                            "hostname" => $hostname,
                            "host_state" => $host_detail{$api_cols['state']},
                            "duration" => timeago($host_detail['last_state_change']),
                            "detail" => $host_detail['plugin_output'],
                            "current_attempt" => $host_detail['current_attempt'],
                            "max_check_attempts" => $host_detail['max_check_attempts'],
                            "tag" => $host_detail['tag'],
                            "is_hard" => ($host_detail['current_attempt'] >= $host_detail['max_check_attempts']) ? true : false,
                            "is_downtime" => (isset($host_detail['scheduled_downtime_depth']) && $host_detail['scheduled_downtime_depth'] > 0) ? true : false,
                            "is_ack" => ($host_detail[$api_cols['ack']] > 0) ? true : false,
                            "is_enabled" => ($host_detail['notifications_enabled'] > 0) ? true : false,
                        ));
                    }
                }

                // In any case, increment the overall status counters.
                if (isset($host_summary[$host_detail[$api_cols['state']]])) {
                    $host_summary[$host_detail[$api_cols['state']]]++;
                } else {
                    $host_summary[$host_detail[$api_cols['state']]] = 1;
                }

                // Now parse the statuses for this host.
                foreach ($host_detail['services'] as $service_name => $service_detail) {

                    // If the host is OK, AND the service is NOT OK.

                    if ($service_detail[$api_cols['state']] != 0 && $host_detail[$api_cols['state']] == 0) {
                        // Sort the service into the correct array. It's either a known issue or not.
                        if ( ($service_detail[$api_cols['ack']] > 0)
                            || ($service_detail['scheduled_downtime_depth'] > 0)
                            || ($service_detail['notifications_enabled'] == 0 )
                            || ((isset($host_detail['scheduled_downtime_depth']) && $host_detail['scheduled_downtime_depth'] > 0))
                        ) {
                            $array_name = "known_services";
                        } else {
                            $array_name = "broken_services";
                        }
                        $downtime_remaining = null;
                        $downtimes = array_merge($service_detail['downtimes'], $host_detail['downtimes']);
                        if ((isset($host_detail['scheduled_downtime_depth']) && $host_detail['scheduled_downtime_depth'] > 0)
                            || (isset($service_detail['scheduled_downtime_depth']) && $service_detail['scheduled_downtime_depth'] > 0)
                        ) {
                            if (count($downtimes) > 0) {
                                $downtime_info = array_pop($downtimes);
                                $downtime_remaining = "- ". timeago($downtime_info['end_time']) . " left";
                            }
                        }
                        if ($service_detail['last_state_change'] >= $state_change_backstop) {
                            array_push($$array_name, array(
                                "hostname" => $hostname,
                                "service_name" => $service_name,
                                "service_state" => $service_detail[$api_cols['state']],
                                "duration" => timeago($service_detail['last_state_change']),
                                "last_state_change" => $service_detail['last_state_change'],
                                "detail" => $service_detail['plugin_output'],
                                "current_attempt" => $service_detail['current_attempt'],
                                "max_attempts" => $service_detail[$api_cols['max_attempts']],
                                "tag" => $host_detail['tag'],
                                "is_hard" => ($service_detail['current_attempt'] >= $service_detail[$api_cols['max_attempts']]) ? true : false,
                                "is_downtime" => ((isset($service_detail['scheduled_downtime_depth']) && $service_detail['scheduled_downtime_depth'] > 0) || (isset($host_detail['scheduled_downtime_depth']) && $host_detail['scheduled_downtime_depth'] > 0)) ? true : false,
                                "downtime_remaining" => $downtime_remaining,
                                "is_ack" => ($service_detail[$api_cols['ack']] > 0) ? true : false,
                                "is_enabled" => ($service_detail['notifications_enabled'] > 0) ? true : false,
                            ));
                        }
                    }
                    if ($host_detail[$api_cols['state']] == 0) {
                        if (isset($service_summary[$service_detail[$api_cols['state']]])) {
                            $service_summary[$service_detail[$api_cols['state']]]++;
                        } else {
                            $service_summary[$service_detail[$api_cols['state']]] = 1;
                        }
                    }
                }
            }
        }
        ksort($host_summary);
        ksort($service_summary);

        return [$host_summary, $service_summary, $down_hosts, $known_hosts, $known_services, $broken_services];

    }


    /**
     * this is basically a factory function to give you back the proper nagios
     * API object based on the api type
     */
    static function get_nagios_api_object($api_type, $hostname, $port=null,
                                          $protocol=null, $url=null) {
        switch ($api_type) {
        case "livestatus":
            $nagios_api = new NagiosLivestatus($hostname, $port, $protocol, $url);
            break;
        case "nagios-api":
            $nagios_api = new NagiosAPI($hostname, $port, $protocol, $url);
            break;
        }

        return $nagios_api;
    }

}

?>
