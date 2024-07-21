# Pi-hole Pauser

## About
The Pi-hole ad blocker is great at doing what it's supposed to: block ads. However sometimes, depending on what you have
on your block list, it will also disrupt some online services, like video streaming ones. Of course you can login to
your Pi-hole dashboard and disable it from there. But it is a hassle, especially if you have more than one configured in
your network. It's also inconvenient for other users who may not have access to the Pi-hole(s) dashboard. Here comes
Pi-hole Pauser. It is a tool which allows you to temporarily pause Pi-holes ad blocking from a single web
page. It works with one ore more Pi-holes and will simultaneously pause all of them. By default, it will pause the
ad blockers for 30 seconds, allowing you to load that video stream or open that page that is being blocked.

## Installation

The Pi-hole Pauser is a Laravel 11 application and requires PHP >= 8.3. Also all PHP extensions as described on their
[documentation](https://laravel.com/docs/11.x/deployment#server-requirements).

Installation is as simple as cloning the repository where you want to host it, pointing your httpd document root to the 
`/public` folder, creating an .env file and generating the encryption key. You will also need to run the migration for
the required SQLite database tables to be created.

```shell
cd /path/to/piholepauser
cp .env.example .env
php artisan key:generate
php artisan migrate
```

## Adding Pi-holes

Once installed, you will need to add some information about your Pi-holes in order for the app to be able to communicate
with them. You will need the Pi-hole's IP address and its API Token. The API token can be obtained from the Pi-hole's
admin dashboard under Settings > API > Show API Token. To configure them, run the included Pi-hole manager from the
command line:
```shell
php artisan pihole:manager
```
From here you will be able to view configured Pi-holes, as well as add, edit or remove them. Note that if you ever
change the Pi-hole admin password, the API Token will change as well and you will need to re-run this manager to update
it.

## Pause time

By default, Pi-hole Pauser will pause ad blocking for 30 seconds. This can be changed by adding a parameter to the URL
of the site, indicating how much time the pause should last for. For example, if you'd like it to pause for 60 seconds,
you add `60` to the URL, as such:
```
http://pyholepauserurl/60
```
By default, you can pause to a maximum of 300 seconds (5 minutes). 

If you would like to change these defaults, you can do so by editing the `.env` file and changing the values of the
`PIHOLE_MIN_TIMEOUT_SECONDS` and `PIHOLE_MAX_TIMEOUT_SECONDS` config entries. The `PIHOLE_MIN_TIMEOUT_SECONDS` is not
only the minimum amount of time pausing will last but also the default.

## Enjoy ad blocker pausing

Once all is done, all you have to is point your browser to the server running the application. This will show you the
current status of pausing, which Pi-holes were successful in being paused and a timer indicating when ad blocking will
resume.

## Contributing
If you find this useful but think it can be improved, feel free to offer your suggestions. You may also issue a PR
directly for new features or bug fixes. 
