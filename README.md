# Pi-hole Pauser

## Overview

The Pi-hole ad blocker is an effective tool for blocking ads network-wide, but it can sometimes interfere with online services like video streaming or opening some web pages. To address this issue, we introduce Pi-hole Pauser, a Laravel 11 application that allows you to temporarily pause Pi-hole's ad blocking from a single web page.

## Features

* Pause ad blocking for one or more Pi-holes simultaneously from a single web page
* Adjustable pause duration (default: 30 seconds)
* Supports multiple Pi-holes
* Configurable minimum and maximum pause durations via environment variables
* Pause duration can be adjusted via URL parameter.

## Installation

To install Pi-hole Pauser, follow these steps:


* Clone the repository to a directory of your choice.
* Set up a PHP 8.3+ environment with the required extensions and server configuration (see [Laravel's documentation](https://laravel.com/docs/11.x/deployment#server-requirements)).
* Install the PHP `sqlite3` extension.
* Run the following commands:
```shell
cd /path/to/piholepauser
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Configuration

To add Pi-holes to your network, run the included Pi-hole manager from the command line:

```shell
php artisan pihole:manager
```

This tool allows you to view and manage configured Pi-holes, as well as add, edit, or remove them.

## Usage

Once installed and configured, pause ad blocking by visiting your web server's URL in your browser. Pi-Hole Pauser will attempt to pause all configured Pi-holes simultaneously. From there, you will also:

* View the current status of pausing, including which Pi-holes were successfully paused
* See a timer indicating when ad blocking will resume


You can also adjust the pause duration by adding a parameter to the URL. For example, to pause for 60 seconds, you would add 60 to the URL: http://piholepauserurl/60. The default maximum pause duration is 5 minutes (300 seconds), but this can be adjusted via environment variables if needed.

To adjust the minimum and maximum pause durations, update the following values in your .env file:

* PIHOLE_MIN_TIMEOUT_SECONDS: sets the minimum pause duration in seconds (also serves as the default) and
* PIHOLE_MAX_TIMEOUT_SECONDS: sets the maximum pause duration in seconds

For example, to set a minimum pause duration of 15 seconds and a maximum duration of 10 minutes, you would update your .env file as follows:

```dotenv
# Pi-hole Pauser specific config
PIHOLE_MIN_TIMEOUT_SECONDS=15
PIHOLE_MAX_TIMEOUT_SECONDS=600
```
## Contributing

If you'd like to contribute to this project or suggest improvements, please feel free to submit your ideas. You can also open a pull request for new features or bug fixes.
