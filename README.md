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
