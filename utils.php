<?php

function build_settings_dialog($nagios_hosts, $unwanted_hosts) {
    // Creates a modal dialog which allows the user to select which instances they want to display on this copy of Nagdash.
    // $unwanted_hosts is an array that is retrieved from a cookie set if the user has already selected some.
    $html = '<div id="settings_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">';
    $html .= '<div class="modal-header">';
    $html .= '  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</a></button>';
    $html .= '  <h3>Nagdash Settings</h3>';
    $html .= '</div>';
    $html .= '<div class="modal-body">';
    $html .= '<form id="settings-form" action="do_settings.php" method="post"><fieldset>';
    $html .= '<legend>Instances to show</legend>';
    foreach ($nagios_hosts as $host) {
        $checked = (!in_array($host['tag'], $unwanted_hosts)) ? "checked" : "";
        $html .= '<label class="checkbox inline tag_label tag_' . $host['tag'] . '">';
        $html .= '<input type="checkbox" name="' . $host['tag'] . '" value="' . $host['tag'] . '"' . $checked . '>' . $host['tag'];
        $html .= '</label>';
    }
    $html .= '</fieldset></form>';
    $html .= '</div>';
    $html .= '<div class="modal-footer">';
    $html .= '  <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>';
    $html .= '  <button class="btn btn-primary" onClick="$(\'#settings-form\').submit();">Save changes</button>';
    $html .= '</div>';
    $html .= "</div>";

    return $html;
}

function print_tag($host_or_service) {
    global $nagios_hosts;
    $tag_name = $host_or_service['tag'];

    # TODO instead of threading the url here, perhaps we should just look
    # it up in the config.  that requires modification of the config however
    $url      = $host_or_service['url'];  
    $elem     = "span";
    $attr     = "";

    if (count($nagios_hosts) <= 1) {
	return false;
    }

    if ($url) { 
	$elem = "a";
	$attr = "href='{$url}'";
    }

    return "<{$elem} {$attr} class='tag tag_{$tag_name}'>{$tag_name}</{$elem}>";
}

?>
