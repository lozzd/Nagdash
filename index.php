<html>
<head>
<title>Nagios Dashboard</title>
<style>
    #spinner    { position: absolute; top: 10px; width: 300px; text-align: center; left: 50%; margin-left: -150px;
                  border: 1px #848484 solid; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; 
                  background: #F0F0F0; font-family: "HelveticaNeue-Medium", Helvetica, Arial, sans-serif; }
</style>
<script src="http://code.jquery.com/jquery-1.3.2.min.js"></script>
<script>
$(document).ready(function() {
    $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
    var refreshId = setInterval(function() {
        $("#spinner").fadeIn("fast");
        $("#nagioscontainer").load("nagdash.php", function() { $("#spinner").fadeOut("fast"); });
    }, 20000);
    $.ajaxSetup({ cache: false });
});
</script>
</head>
<body>
<div id="spinner"><h3><img src="ajax-loader.gif" align="absmiddle"> Refreshing...</h3></div>
<div id="nagioscontainer"></div>
</body>
