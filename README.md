# WyriHaximus/PhergieHttp

[Phergie](http://github.com/phergie/phergie-irc-bot-react/) plugin for Provide HTTP functionality to other plugins.

[![Build Status](https://secure.travis-ci.org/WyriHaximus/PhergieHttp.png?branch=master)](http://travis-ci.org/WyriHaximus/PhergieHttp)

## Install

The recommended method of installation is [through composer](http://getcomposer.org).

```JSON
{
    "require": {
        "wyrihaximus/phergie-http": "dev-master"
    }
}
```

See Phergie documentation for more information on
[installing and enabling plugins](https://github.com/phergie/phergie-irc-bot-react/wiki/Usage#plugins).

## Configuration

```php
new \WyriHaximus\Phergie\Plugin\Http\Plugin(array(



))
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
