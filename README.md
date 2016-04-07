# The Idea

SimpleFrame is a very slim framework intended to allow developers to write/use
any code they want without limiting their options in any way, except the bare
minimum required to make the framework itself work.

There is only ONE required file in the `/app` directory to make the site work,
and that is `routes.php`. Technically it will work even without that, but it will
just show a `404 page not found`.

## Testing

You can use the built-in PHP server to run the code. Just execute `php -S localhost:8000`
in the `/public` directory and open `http://localhost:8000` in your browser.
It works with pretty URLs, no need to prefix `/index.php` to your routes.

You can test almost any page (the exception being file uploads) in the terminal
by going to the `/public` directory and executing
`php index.php METHOD ROUTE[ DATA]`, e.g. `php index.php get /`
or `php index.php post /abc foo=bar&bar[0]=baz`.
Redirects obviously won't work, and absolute URLs generated via the framework's
router will be invalid, but aside from that, everything else will work the same.

At the time of writing this, the framework only relies on three $_SERVER variables,
namely `REQUEST_METHOD`, `REQUEST_URI` and `HTTP_REFERER`.
Absolute route generation will also use `HTTPS` and `HTTP_HOST` variables.

Alternatively, you can run custom console commands via `php index.php command[ arguments]`.

## Installation

This project is available via composer:

`composer create-project jurchiks/simpleframe your-project-name`
