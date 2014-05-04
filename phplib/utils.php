<?php

class NagdashHelpers {

    static function print_tag($tag_name) {
        global $nagios_hosts;
        if (count($nagios_hosts) > 1) {
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
        ksort($arr);
        foreach ($arr as &$a) {
            if (is_array($a) && !empty($a)) {
                NagdashHelpers::deep_ksort($a);
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
        if ($a['last_state_change'] == $b['last_state_change']) return 0;
        return ($a['last_state_change'] > $b['last_state_change']) ? -1 : 1;
    }

}

?>
