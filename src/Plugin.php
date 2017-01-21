<?php

/**
 * This file is part of PhergieHttp.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Phergie\Plugin\Http;

use GuzzleHttp\Client;
use Phergie\Irc\Bot\React\AbstractPlugin;
use React\EventLoop\LoopInterface;
use Phergie\Irc\Client\React\LoopAwareInterface;
use React\Dns\Resolver\Resolver;
use WyriHaximus\React\RingPHP\HttpClientAdapter;

/**
 * Plugin for Provide HTTP functionality to other plugins.
 *
 * @category Phergie
 * @package Phergie\Plugin\Http
 */
class Plugin extends AbstractPlugin implements LoopAwareInterface
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Resolver
     */
    protected $resolver;

    /**
     * @var string
     */
    protected $dnsResolverEvent = 'dns.resolver';

    /**
     * @var array
     */
    protected $guzzleClientOptions = [];

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (isset($config['dnsResolverEvent'])) {
            $this->dnsResolverEvent = $config['dnsResolverEvent'];
        }

        if (isset($config['guzzleClientOptions']) && is_array($config['guzzleClientOptions'])) {
            $this->guzzleClientOptions = $config['guzzleClientOptions'];
        }
    }

    /**
     * @param LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return null|LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'http.client' => 'getGuzzleClient',
            'http.request' => 'makeHttpRequest',
            'http.streamingRequest' => 'makeStreamingHttpRequest',
        ];
    }

    /**
     * @param $message
     */
    public function logDebug($message)
    {
        $this->logger->debug('[Http]' . $message);
    }

    /**
     * @param Request $request
     */
    public function makeHttpRequest(Request $request)
    {
        $this->getGuzzleClient(
            function (Client $client) use ($request) {
                $requestId = uniqid();
                $httpResponse = null;
                $requestObject = $client->createRequest($request->getMethod(), $request->getUrl(), [
                    'future' => true,
                    'stream' => false,
                    'headers' => $request->getHeaders(),
                ]);
                $this->logDebug('[' . $requestId . ']Sending request');
                $client->send($requestObject)->then(function ($response) use ($requestId, $request) {
                    $this->logDebug('[' . $requestId . ']Remote responded');
                    $request->callResolve($response);
                }, function ($error) use ($requestId, $request) {
                    $this->logDebug('[' . $requestId . ']Error during request');
                    $request->callReject($error);
                });

            }
        );
    }

    /**
     * @param Request $request
     */
    public function makeStreamingHttpRequest(Request $request)
    {
        $this->getGuzzleClient(
            function (Client $client) use ($request) {
                $requestId = uniqid();
                $httpResponse = null;
                $requestObject = $client->createRequest($request->getMethod(), $request->getUrl(), [
                    'future' => true,
                    'stream' => true,
                    'headers' => $request->getHeaders(),
                ]);
                $this->logDebug('[' . $requestId . ']Sending request');
                $client->send($requestObject)->then(function ($response) use ($requestId, $request) {
                    $this->logDebug('[' . $requestId . ']Remote responded');
                    $request->callResolve($response);
                }, function ($error) use ($requestId, $request) {
                    $this->logDebug('[' . $requestId . ']Error during request');
                    $request->callReject($error);
                });

            }
        );
    }

    /**
     * @param callable $callback
     */
    public function getGuzzleClient($callback)
    {
        if ($this->client instanceof Client) {
            $this->logDebug('Existing Guzzle client, using it');
            $callback($this->client);
            return;
        }

        $this->logDebug('Creating new Guzzle client');

        $this->getResolver(
            function ($resolver) use ($callback) {
                $this->logDebug('Requesting DNS Resolver');
                $options = $this->guzzleClientOptions;
                $options['handler'] = new HttpClientAdapter($this->loop, null, $resolver);
                $this->client = new Client($options);
                $callback($this->client);
            }
        );
    }

    /**
     * @param Client $client
     */
    public function setGuzzleClient(Client $client)
    {
        $this->client = $client;
    }


    /**
     * @param callable $callback
     */
    public function getResolver($callback)
    {
        if ($this->resolver instanceof Resolver) {
            $callback($this->resolver);
            return;
        }
        $this->logDebug('Requesting DNS Resolver');
        $this->emitter->emit(
            $this->dnsResolverEvent,
            [
                function ($resolver) use ($callback) {
                    $this->logDebug('DNS Resolver received');
                    $this->setResolver($resolver);
                    $callback($resolver);
                }
            ]
        );
    }

    /**
     * @param Resolver $resolver
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }
}
