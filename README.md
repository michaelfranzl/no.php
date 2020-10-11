# no.php

Transparent reverse proxy written in PHP that allows you to not have to write PHP any more.

This short, single-file, 130-line PHP script is a simple and fully transparent HTTP(S) reverse proxy written in PHP that allows you to never have to use PHP again for a new project, if you feel so inclined, for example if you are forced to host on a fully 3rd-party-managed server where you can't do more than run PHP and upload files via FTP. The PHP script simply reads all requests from a browser pointed to it, forwards them (via PHP's curl library) to a web application listening at another URL (e.g. on a more powerful, more secure, more private, or more capable server in a different data center), and returns the responses transparently and unmodified.

Supports:

* Regular and XMLHttpRequests (AJAX)
* All HTTP headers without discrimination
* GET and POST verbs
* Content types (HTTP payload) without discrimination
* Redirects (internal redirects are rewritten to relative URIs)
* Multipart content type
* Cookies (with conversion of the backend domain to the no.php host)

Does not support (or not tested):

* HTTP verbs other than GET and POST (but these are usually emulated anyway)
* HTTP greater than version 1.1 (e.g. reusable connections)
* Upgrade to websocket (persistent connections)


## Usage illustrated by the standard example

You have a non-PHP web application (called the "backend") listening on `https://myapp.backend.com:3000` but due to constraints you must make it available on a shared hosting server called `https://example.com/subdir` which only supports PHP and can't be configured at all. On latter server, Apache (or Nginx, doesn't matter) will usually do the following:

1. If a URI points to a .php file, this file will be interpreted
2. If a URI points to a file that is not existing, a 404 status will be returned.

Using no.php, to accomodate the second case, all URIs of the proxied web app (including static files) must be appended to the URI `https://example.com/subdir/no.php`. For example:

    https://example.com/subdir/no.php/images/image.png
    https://example.com/subdir/no.php/people/15/edit
    
If your backend app supports that extra `/subdir/no.php` prefix to all paths, you are all set and ready to use no.php. Then:

1. Simply copy `no.php` into the `subdir` directory of example.com
2. Change `$backend_url` in `no.php` to `"https://myapp.backend.com:3000"`
3. Point a browser to `https://example.com/subdir/no.php`


In Ruby on Rails for example you must do a minimal adaptation to facilitate the mentioned URL prefix -- please consult the Ruby on Rails documentation for full details, but here is a hint:

    ENV['RAILS_RELATIVE_URL_ROOT'] = "/subdir/no.php"

    Rails.application.configure do
      config.relative_url_root = ENV['RAILS_RELATIVE_URL_ROOT']
    end

    Rails.application.routes.draw do
      scope path: ENV['RAILS_RELATIVE_URL_ROOT'] do
        # routes here
      end
    end
