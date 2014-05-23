# Nagdash

## What is Nagdash?

Nagdash is the long awaited replacement of [Naglite2](http://github.com/lozzd/naglite2).

Written in PHP, it uses the [Nagios-api](https://github.com/xb95/nagios-api), PHP and a sprinkling of jQuery and Bootstrap to provide a full screen, clean Nagios experience which is suitable either for a Dashboard/NOC screen, or simply a simple view to replace the Nagios UI. 

## Features

Naglite2, the first iteration, was dependant on parsing the status.dat file that Nagios writes, so it had to live on your local machine. The code was also mostly borrowed from Naglite, and was poorly written and fairly unmaintainable. This project seeks to fix those two issues, primarily. 

* **Simple configuration**: 1 line to add a new Nagios instance to pull results from. 
* **Instance tags**: Every host and service is tagged with a small but effective icon that indicates which instance that host or service is pulled from.  This icon can be linked to the instance.
* **Deployment flexibility**: Because Nagdash uses the nagios-api REST-like JSON interface, you no longer have to situation it on the same machine as your Nagios instance(s). As long as it can hit the API, it's good to go. 
* **Clear, simple UI**: Designed to provide an at-a-glance overview of just how much is broken in you infrastructure. The more red, the more you need to panic. Services and hosts that are acknowledged, in downtime, or silenced are hidden away, but still available to avoid attracting the eye.
* **Know about problems before they happen**: Soft alerts are shown clearly with their attempt number so you get a heads up before Nagios even tells you. 
* **Automatic refresh**: The screen refreshes via Ajax to provide a unobstrusive live update of the overall status aggregated from all your Nagios instances. 
* **Two-way interface**: Unlike Naglite2, some core operations are available directly from Nagdash. If using on your desktop/laptop, there are buttons that allow you to Acknowledge problems, Schedule Downtime or enable/disable notifications with a single click without leaving Nagdash

## Screenshots

![On a monitor](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/01_on-a-monitor.png) 

On a monitor

![Easy to read tags](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/02_easy-to-read-tags.png)

Easy to read tags

![Service states](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/03_service-states.png)

Service states

![Easy to read duration/attempts](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/04_easy-to-read-duration-attempts.png)

Easy to read duration/attempts

![Reminder of all the 'known' issues](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/05_reminder-of-all-the-known-issues.png)

Reminder of all the 'known' issues

![Hover over the row for one click service actions](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/06_click-service-actions.png)

Hover next to a service for one click service actions. Schedule downtime (choose length), enable/disable notifications, and acknowledge service problems directly from Nagdash.

![Easy config](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/07_easy-configuration.png)

Easy configuration

![Choose your instances](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/08_live-settings-screen.png)

Live settings screen (accessed using the 's' key) lets you choose which Nagios instances are important to you on this copy of Nagdash. 


## Prerequisites
* A webserver
* PHP 4 (or higher), including the curl extensions for PHP, and short_open_tags enabled
* [Nagois-api](https://github.com/xb95/nagios-api) running on one or more Nagios servers
* A big screen to show Nagdash off

## Installation/configuration
1. Download/clone the repo into an appropriate folder either in your webservers directory or symlinked to it
2. Move config.php.example to config.php, edit with your favourite editor and add your Nagios host(s) to the Nagios hosts array. (see screenshot above)
3. Load index.php in your browser and if all goes well, you will see your Nagios installation come to life, and refresh every 20 seconds. 
4. Reward yourself with a refreshing beverage. 

## Advanced configuration

The configuration file is fairly simple at this point, but here's an explanation of all the parts.

* Nagios hosts array

![Nagios hosts array](https://github.com/lozzd/Nagdash/raw/master/images/screenshots/09_nagios-hosts-array.png)

    * Hostname: The hostname or IP address of a server running the nagios-api
    * Port: The port the nagios-api instance is running on
    * Protocol: http or https
    * Tag: The text that should be displayed against all hosts and services from this instance.
    * Taccolour: The background colour of the tag that is displayed next to all hosts and services from this instance. 
    * url: (optional) the url to this web interface of this instance.  will be used to link the tags

* Filter: This is a simple regex filter if you wish to only display certain hosts from all your instances. For example, if you were making a dedicated dashboard for a team that manages a certain set of servers.
* Duration: This sets the default Downtime duration when the "Downtime" button is clicked from the Nagdash interface. Out of the box, it's 60 minutes.
* User auth: Just implement a function called `nagdash_get_user()` which returns the username of the current user and it will be used for acknowledgements.

## On the fly configuration
You can access the settings dialog inside of Nagdash by pressing the 's' key. 

Currently the following things are configurable when using Nagdash:

* Enable/disable individual Nagios instances depending on your preference

This information is set using a cookie on your local machine, so you can customise Nagdash to your own needs without affecting others. 

## Known issues
* You will need a relatively up to date copy of the nagios-api, since some fields were not available in the older version. If you see "Reference time not set", you need to update your copy of the nagios-api. 
* The above also applies for acknowledging problems. If you receive an error that the command is not supported, please update your copy of nagios-api. 
* Your hosts MUST have unique names between instances. If there are non-unique names, the services from the FIRST Nagios instance will be used. Please let me know if this is a major issue for you, I can add an optional workaround. 
