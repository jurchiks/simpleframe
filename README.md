# The Idea

SimpleFrame is a very slim framework intended to allow developers to write/use
any code they want without limiting their options in any way, except the bare
minimum required to make the framework itself work.

There is only ONE required file in the `/app` directory to make the site work,
and that is `routes.php`. Technically it will work even without that, but it will
just show a `404 page not found`.

## Testing

You can test almost any page (the exception being file uploads) by opening
a terminal, going to the `/public` directory, and executing
`php index.php METHOD ROUTE[ DATA]`, e.g. `php index.php get /`
or `php index.php post /abc foo=bar&bar[0]=baz`.

At the time of writing this, the framework only relies on two $_SERVER variables,
namely `REQUEST_METHOD` and `REQUEST_URI`, both of which are easy to simulate
in a terminal.

## Installation

This project is available via composer:

`composer create-project jurchiks/simpleframe your-project-name`
