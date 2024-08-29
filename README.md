<p align="center">
  <picture>
    <img src="https://raw.githubusercontent.com/tchubaba/pausepi/master/public/images/pausepi.png" width="220" height="236" alt="PausePi">
  </picture>
</p>

# PausePi

## Overview

The Pi-hole ad blocker is an effective tool for blocking ads network-wide, but it can sometimes interfere with online services like video streaming or opening some web pages. Of course you can pause ad blocking from the Pi-hole admin dashboard, but it is a hassle, especially if you have more than one configured in your network. It's also inconvenient for other users who may not have access to the Pi-hole's dashboard. 

To address these issues, we introduce PausePi, a tool that allows you to temporarily pause Pi-hole's ad blocking from a single web page.

## Features

* Pause ad blocking for one or multiple Pi-holes simultaneously
* Adjustable pause duration (default: 30 seconds) via URL parameter
* Includes an easy-to-use configuration tool for managing your Pi-holes' information
* Configurable minimum and maximum pause durations via environment variables
* Deployable as a Docker container

## Installation

PausePi is a Laravel 11 application. To deploy it, you can either use the provided docker 
container or install it natively.

### Docker Container
To deploy it as a Docker container (this assumes Docker is already installed):
* Clone this repository to a directory of your choice.
* CD into this directory and run the following commands:
```shell
docker compose up -d --build
docker exec -it pausepi-php cp /var/www/.env.example /var/www/.env
docker exec -it pausepi-php php /var/www/artisan key:generate
docker exec -it pausepi-php php /var/www/artisan migrate
```

### Native install

* Clone this repository to a directory of your choice.
* Set up a PHP 8.3+ environment with the required extensions and server configuration (see [Laravel's documentation](https://laravel.com/docs/11.x/deployment#server-requirements)).
* Install the PHP `sqlite3` extension.
* CD into the directory and run the following commands:
```shell
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Configuration

To enable communication between the application and your Pi-holes, you will need to provide the necessary configuration information. This includes the IP addresses of the Pi-holes and their corresponding API tokens. The API token can be retrieved from the Pi-hole's administrative dashboard by navigating to Settings > API > Show API Token. To configure them, run the included PausePi Manager from the command line:

If running the docker container
```shell
docker exec -it pausepi-php php /var/www/artisan pausepi:manager
```

If running natively
```shell
php artisan pausepi:manager
```

This tool allows you to view configured Pi-holes, as well as add, edit, or remove them. Note that if you ever change the Pi-hole admin password, the API Token will change as well and you will need to re-run this manager to update it.

## Usage

Once installed and configured, pause ad blocking by visiting your web server's URL in your browser. PausePi will attempt to pause all configured Pi-holes simultaneously. From there, you will also:

* View the current status of pausing, including which Pi-holes were successfully paused
* See a timer indicating when ad blocking will resume


You can also adjust the pause duration by adding a parameter to the URL. For example, to pause for 60 seconds, you would add 60 to the URL: http://pausepi/60. The default maximum pause duration is 5 minutes (300 seconds), but this can be adjusted via environment variables if needed.

To adjust the minimum and maximum pause durations, update the following values in your .env file:

* PAUSEPI_MIN_TIMEOUT_SECONDS: sets the minimum pause duration in seconds (also serves as the default) and
* PAUSEPI_MAX_TIMEOUT_SECONDS: sets the maximum pause duration in seconds

For example, to set a minimum pause duration of 15 seconds and a maximum duration of 10 minutes, you would update your .env file as follows:

```dotenv
# PausePi specific config
PAUSEPI_MIN_TIMEOUT_SECONDS=15
PAUSEPI_MAX_TIMEOUT_SECONDS=600
```
## Contributing

If you'd like to contribute to this project or suggest improvements, please feel free to submit your ideas. You can also open a pull request for new features or bug fixes.
