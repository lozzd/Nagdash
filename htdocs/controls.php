<div class="btn-group">
	<div class="btn-group">
	<a href='#' class='btn btn-mini dropdown-toggle' data-toggle='dropdown' > <i class='icon-comment'></i> Ack </a>
	<ul class="dropdown-menu pull-left" >
	  <li id='ack-comment-container'>
	  	<input type="text" class="form-control" placeholder="Ack Comment" id="ack-comment">
			<?php
				echo "<a href='#' class='btn btn-mini' onClick=\"nagios_action('{$tag}', '{$host}', '{$service}', 'ack', $(this).siblings('#ack-comment').val()); $(this).siblings('#ack-comment').val(''); return false;\" >  <i class='icon-check'></i></a>";
			?>
	  </li>
	</ul>
</div>
<?php
$action = (!isset($service['is_enabled'])) ? "disable" : "enable";
$text   = (!isset($service['is_enabled'])) ? "Silence" : "Unsilence";
$control = "<a href='#' onClick=\"nagios_action('{$tag}', '{$host}', '{$service}', '{$action}'); return false;\" class='btn btn-mini'>";
$control .= "<i class='icon-volume-off'></i> {$text}</a>";
echo $control;
?>
<div class="btn-group">
<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">
<i class="icon-time"></i> Downtime <span class="caret"></span></a>
<ul class="dropdown-menu pull-right">
<?php
$control = "";
 $timespans = array("10 minutes" => 10, "30 minutes" => 30, "60 minutes" => 60, "2 hours" => 120, "12 hours" => 720, "1 day" => 1440, "7 days" => 10080);
    foreach ($timespans as $name => $minutes) {
        $control .= "<li><a href='#' onClick=\"nagios_action('{$tag}', '{$host}', '{$service}', 'downtime', '{$minutes}'); return false;\" >{$name}</a></li>";
    }
echo $control;
?>
    </ul>
</div>
</div>
<script>
	// This has to be done on each Ajax refresh.
    $(function() {
      // Setup drop down menu
      $('.dropdown-toggle').dropdown();
      // Fix input element click problem
      $('.dropdown-menu').click(function(e) {
        e.stopPropagation();
      });
    });
</script>