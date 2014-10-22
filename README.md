# WyriHaximus/PhergieHttp

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for Provide HTTP functionality to other plugins.

[![Build Status](https://secure.travis-ci.org/WyriHaximus/PhergieHttp.png?branch=master)](http://travis-ci.org/WyriHaximus/PhergieHttp)

## Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require wyrihaximus/phergie-http 
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Requirements

The HTTP plugin requires the [DNS plugin](https://github.com/WyriHaximus/PhergieDns) to be setup for DNS resolving.

## Configuration

```php
return array(

    'plugins' => array(

        // dependency
        new \WyriHaximus\Phergie\Plugin\Dns\Plugin, // Needed to do DNS lookups

        new \WyriHaximus\Phergie\Plugin\Http\Plugin(array(

            // All configuration is optional

            'dnsResolverEvent' => 'dns.resolver', // Event for retrieving the DNS resolver, defaults to 'dns.resolver'

        )),

    )
);
```

## Usage

```php
$this->emitter->emit('http.request', array(new \WyriHaximus\Phergie\Plugin\Http\Request(array(
    'url' => 'https://github.com/',                     // Required
    'resolveCallback' => function($buffer, $headers, $code) { // Required
        // Data received do something with it
    },
    'method' => 'GET',                                  // Optional, request method
    'headers' => array(),                               // Optional, headers for the request
    'body' => '',                                       // Optional, request body to write after the headers
    'responseCallback' => function($headers, $code) {}, // Optional, callback that triggers with the response headers
    'dataCallback' => function($data) {},               // Optional, callback that triggers for each chunk of incoming data
    'rejectCallback' => function($error) {},            // Optional, callback that gets triggered on connection errors
    'buffer' => true,                                   // Optional, buffer the incoming requested file data and when completed pass it to resolveCallback, set to false to disable that
))));
```

A note about `resolveCallback` and `rejectCallback`. `rejectCallback` will only fire on a socket error. So `resolveCallback` will be called no matter what [`HTTP status code`](http://en.wikipedia.org/wiki/List_of_HTTP_status_codes) as the request has been successful on a connection level. Choosing the appropriate response to a status code is up to the event callee.

## Tests

To run the unit test suite:

```
curl -s https://getcomposer.org/installer | php
php composer.phar install
cd tests
../vendor/bin/phpunit
```

## License

Released under the MIT License. See `LICENSE`.
