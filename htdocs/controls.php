<div class="btn-group">';
<a href='#' onClick="$.post('do_action.php', {
    nag_host: '{$tag}', hostname: '{$host}', service: '{$service}', action: 'ack' }, function(data) { showInfo(data) } ); return false;\" class='btn btn-mini'>
        <i class='icon-check'></i> Ack </a>";
<?php
$action = (!isset($service['is_enabled'])) ? "disable" : "enable";
$text   = (!isset($service['is_enabled'])) ? "Silence" : "Unsilence";
$control = "<a href='#' onClick=\"nagios_action('{$tag}', '{$host}', '{$service}', '{$action}'); return false;\" class='btn btn-mini'>";
$control .= "<i class='icon-volume-off'></i> {$text}</a>";
echo $control;
?>
<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">
<i class="icon-time"></i> Downtime <span class="caret"></span></a>
<ul class="dropdown-menu pull-right">';
<?php
 $timespans = array("10 minutes" => 10, "30 minutes" => 30, "60 minutes" => 60, "2 hours" => 120, "12 hours" => 720, "1 day" => 1440, "7 days" => 10080);
    foreach ($timespans as $name => $minutes) {
        $control = "<li><a href='#' onClick=\"nagios_action('{$tag}', '{$host}', '{$service}', 'downtime', '{$minutes}'); return false;\" class='btn btn-mini'>{$name}</a></li>";
    }
echo $control;
?>
    </ul>
</div>
