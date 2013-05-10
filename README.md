#Primal Routing

Created and Copyright 2013 by Jarvis Badgley, chiper at chipersoft dot com.

Primal Routing is a Clean URL routing class for PHP 5.3 and later, built on four principles:

1. Every route is a file
2. The forward-slash is a segment delimiter, not a hierarchy
3. The route that matches the most segments wins.
4. Any segment can have a paired value

##Route Matching

Lets take the following URL as an example:

    http://localhost/alpha/beta/charley/delta

The above request is parsed into a list of arguments.  Primal Routing then works backwards, scanning your routes folder for the first file that matches the segments list (delimiting the file names with periods).  In this case it will scan for the following files in order:

1. `alpha.beta.charley.delta.php`
2. `alpha.beta.charley.php`
3. `alpha.beta.php`
4. `alpha.php`
5. `_catchall.php`
6. `_notfound.php`

*Note that the last two routes can be changed using the `->setNotFound()` and `->setCatchAll()` functions.*

The first match that it finds will be executed. If no routes are found which match the request url then it will look for a catchall route before finally invoking a File Not Found route (if no _notfound handler exists, it will throw an exception).

##Route Arguments

All segments which are _not_ part of the route name are passed to the route when it is called, indexed by their order.  So if the above url matched to `alpha.beta.php`, the route would received the following arguments array:

    Array
    (
        [0] => charley
        [1] => delta
    )

Now lets look at principle #4, every segment can have a paired value.  Examine the following url:

    http://localhost/alpha=1/beta=foo/charley=0/delta=/echo

This url will still match to the same route, but the paired segments will be available as parameters:

    Array
    (
        [alpha] => 1
        [beta] => foo
        [charley] => 0
        [delta] =>
    )

Note, the `enableEmptyArgumentInclusion()` option will cause all segments to be included as parameters, with empty segments being null.


###Site Index

The url `/` or `http://my.domain.com/` is interpreted by Primal Routing as a call to the site index.  Primal Routing will attempt to route to "index" (call `setSiteIndex()` to change this value) before passing to the file not found route ("_notfound").  The site index cannot receive arguments unless the url begins with the site index name.  Example: 

    http://my.domain.com/index/foo=bar


##Demo

If you have Composer and PHP 5.4 installed you can see Primal Routing in action by running the following from inside the repo root:

    composer dump-autoload
    php -S localhost:8000 demo.php

This will create a temporary server on your computer.  Try the following urls:

- http://localhost:8000/
- http://localhost:8000/phpinfo
- http://localhost:8000/demo/dump/alpha/beta=12/charley=delta/
- http://localhost:8000/this/does/not/exist/

##Running Tests

You must have Composer and PHPUnit installed to run the unit tests.  Run the following from inside the repo root:

    composer dump-autoload
    phpunit


##Server Forwarding

In a standard configuration, Apache and Nginx will only call the file containing the Primal Routing code if you directly access it.  The following configurations expect that `index.php` contains the Primal Routing initialization code.

###Apache

Place the following into the virtualhost Directory definition, or into a `.htaccess` file at the root of your website.

    <IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /
    RewriteRule ^$ index.php	[L]

    # forward all requests to /
    RewriteRule ^$ index.php [L]

    # send all other requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-f [OR]
    RewriteRule ^/?.*$ index.php [L]
    </IfModule>

Note that Apache must be configured with mod_rewrite for this to work.

###Nginx

Place the following into the virtualhost `server` block.

    location = / {
        rewrite ^ /index.php last;
    }

    location / {
        if (!-e $request_filename) {
            rewrite ^ /index.php last;
        }
    }

Note that this configuration assumes that you have PHP configured through a FastCGI interface in the traditional method.  It may also be necessary to add the following to your PHP location directive:

    try_files $uri $uri/ $uri/index.php /index.php;


##License

Primal Routing is released under an MIT license.  No attribution is required.  For details see the enclosed LICENSE file.




