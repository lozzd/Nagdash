/**
 * JS helper functions
 */

/**
 * Show a faded-in info window with the passed in data shown
 *
 * Parameter:
 *   show_data - data to show
 */
function showInfo(show_data) {
    $("#info-window").fadeIn("fast");
    $("#info-window-text").empty().append(show_data);

}

/**
 * Load nagios data
 *
 * Parameter:
 *   show_spinner - whether or not to show the ajax spinner
 *
 */
function load_nagios_data(show_spinner) {

  $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
  var refreshId = setInterval(function() {
      if (show_spinner) {
        $("#spinner").fadeIn("fast");
      }
      $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
  }, 20000);
  $.ajaxSetup({ cache: false });
}

/**
 * Tell nagios to do stuff
 *
 * Parameter:
 *   tag     - the tag of the element
 *   host    - host to apply the action to
 *   service - the service to apply the action to
 *   action  - the actual action to do
 *   minutes - minutes to downtime (only for downtime action)
 *
 */
function nagios_action(tag, host, service, action, minutes) {
  $.post('do_action.php', { nag_host: tag,
                            hostname: host,
                            service: service,
                            action: action,
                            duration: minutes}, function(data) { showInfo(data) } );
}



