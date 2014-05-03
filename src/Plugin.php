<?php

/**
 * This file is part of PhergieHttp.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace WyriHaximus\Phergie\Plugin\Http;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Event\EventInterface;
use React\Promise\Deferred;
use React\HttpClient\Client as HttpClient;
use React\HttpClient\Factory as HttpClientFactory;

/**
 * Plugin for Provide HTTP functionality to other plugins.
 *
 * @category Phergie
 * @package WyriHaximus\Phergie\Plugin\Http
 */
class Plugin extends AbstractPlugin
{
    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = array())
    {

    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'services.http' => 'provideHttpService',
        );
    }

    /**
     *
     *
     * @param \Phergie\Irc\Event\EventInterface $event
     * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
     */
    public function provideHttpService(EventInterface $event, EventQueueInterface $queue)
    {
        $deferred = new Deferred();

        $request = $this->getClient()->request('GET', 'https://github.com/');
        $request->on('response', function ($response) use ($deferred) {
            $deferred->progress(array(
                'type' => 'response',
                'payload' => $response,
            ));
            $response->on('data', function ($data) use ($deferred) {
                $deferred->progress(array(
                    'type' => 'data',
                    'payload' => $data,
                ));
            });
        });
        $request->on('end', function () use ($deferred) {
            $deferred->resolve();
        });
        $request->end();
        return $deferred->promise();
    }

    public function getClient()
    {
        if ($this->client instanceof HttpClient) {
            return $this->client;
        }

        $factory = new HttpClientFactory();
        $this->client = $factory->create($this->loop, $this->getResolver());

        return $this->client;
    }

    public function getResolver()
    {
        if ($this->resolver instanceof Resolver) {
            return $this->resolver;
        }

        $factory = new Factory();

        $this->resolver = $factory->createCached('8.8.8.8', $this->loop);

        return $this->resolver;
    }
}
