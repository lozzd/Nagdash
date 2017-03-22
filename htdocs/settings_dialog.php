<?php require '../config.php'; ?>
<div id="settings_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-hidden="true">
<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</a></button>
  <h3>Nagdash Settings</h3>
</div>
<div class="modal-body">
<form id="settings-form" action="do_settings.php" method="post">
<fieldset>
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
</fieldset>
<fieldset>
<legend>Hostname regex</legend>
<input type="input" name="hostfilter" value="<?php echo $_COOKIE['nagdash_hostfilter']; ?>">
</fieldset>
<fieldset>
<legend>Last state change</legend>
<div class="settings_group">
<span class="settings_element">
<select name="select_last_state_change" style="width:110px;">
<?php
foreach ($select_last_state_change_options as $time_in_seconds => $time_in_english) {
    $selected_last_state_change_option = "";
    if (isset($_COOKIE['select_last_state_change']) && $_COOKIE['select_last_state_change'] == $time_in_seconds) {
        $selected_last_state_change_option = "selected";
    }
    echo "<option value=" . $time_in_seconds . " " . $selected_last_state_change_option . ">" . $time_in_english . "</option>";
}
?>
</select>
</span>
<span class="settings_element"> ago</span>
</div>
</fieldset>
<fieldset>
<legend>Sort options</legend>
<?php
// If the config option 'sort_by_time' is true, check if the user is overriding it.
// If not, let the config option take effect.
$checked_sort_by_time = "";
$checked_sort_descending = "";
if ($sort_by_time) {
    if (isset($_COOKIE['sort_by_time']) && $_COOKIE['sort_by_time'] == '0') {
        $checked_sort_by_time = "";
    } else {
        $checked_sort_by_time = "checked";
    }
} elseif (isset($_COOKIE['sort_by_time']) && $_COOKIE['sort_by_time'] == '1') {
    $checked_sort_by_time = "checked";
}
// Does the user want to sort in descending order?
if (isset($_COOKIE['sort_descending']) && $_COOKIE['sort_descending'] == '1') {
        $checked_sort_descending = "checked";
}
?>
<span class="settings_element">
Sort by time?
<label class="checkbox inline tag_label">
<?php echo '<input type="checkbox" name="sort_by_time"' . $checked_sort_by_time . '>'; ?>
</label>
</span>
<span class="settings_element">
Descending?
<label class="checkbox inline tag_label">
<?php echo '<input type="checkbox" name="sort_descending"' . $checked_sort_descending . '>'; ?>
</label>
</span>
</fieldset>
</form>
</div>
<div class="modal-footer">
  <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
  <button class="btn btn-primary" onClick="$('#settings-form').submit();">Save changes</button>
</div>
</div>
