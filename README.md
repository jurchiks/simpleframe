# The Idea

SimpleFrame is a very slim framework intended to allow developers to write/use
any code they want without limiting their options in any way, except the bare
minimum required to make the framework itself work.

There is only ONE required file in the `/app` directory to make the site work,
and that is `routes.php`. Technically it will work even without that, but it will
just show a `404 page not found`.

## Testing

You can test any page that responds to GET requests by opening a terminal,
going to the `/public` directory, and executing the following command:
`php index.php route /my/route`.

For POST requests, you can add parameters to the command:
`php index.php route /my/route post name=foo&bar[0]=baz`

## Installation

This project is available via composer:

`composer create-project jurchiks/simpleframe your-project-name`
