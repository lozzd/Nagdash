<div id="settings_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</a></button>
  <h3>Nagdash Settings</h3>
</div>
<div class="modal-body">
<form id="settings-form" action="do_settings.php" method="post"><fieldset>
<legend>Instances to show</legend>
<?php
    foreach ($nagios_hosts as $host) {
        $checked = (!in_array($host['tag'], $unwanted_hosts)) ? "checked" : "";
        echo '<label class="checkbox inline tag_label tag_' . $host['tag'] . '">';
        echo '<input type="checkbox" name="' . $host['tag'] . '" value="' . $host['tag'] . '"' . $checked . '>';
        echo $host['tag'];
        echo '</label>';
    }
?>
</fieldset></form>
</div>
<div class="modal-footer">
  <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
  <button class="btn btn-primary" onClick="$('#settings-form').submit();">Save changes</button>
</div>
</div>
