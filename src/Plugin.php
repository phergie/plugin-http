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
use React\EventLoop\LoopInterface;
use Phergie\Irc\Client\React\LoopAwareInterface;
use React\HttpClient\Client as HttpClient;
use React\HttpClient\Factory as HttpClientFactory;
use React\HttpClient\Response;
use React\Dns\Resolver\Resolver;

/**
 * Plugin for Provide HTTP functionality to other plugins.
 *
 * @category Phergie
 * @package WyriHaximus\Phergie\Plugin\Http
 */
class Plugin extends AbstractPlugin implements LoopAwareInterface
{
    protected $resolver;
    protected $client;

    protected $dnsResolverEvent = 'dns.resolver';

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
        if (isset($config['dnsResolverEvent'])) {
            $this->dnsResolverEvent = $config['dnsResolverEvent'];
        }
    }

    public function setLoop(LoopInterface $loop) {
        $this->loop = $loop;
    }

    public function setResolver(Resolver $resolver) {
        $this->resolver = $resolver;
    }

    public function setClient(HttpClient $client) {
        $this->client = $client;
    }

    /**
     *
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'http.request' => 'makeHttpRequest',
        );
    }

    public function logDebug($message) {
        $this->logger->debug('[Http]' . $message);
    }

    /**
     *
     *
     * @param Request $request
     */
    public function makeHttpRequest(Request $request)
    {
        $that = $this;

        $this->getClient(function($client) use ($request, $that) {
            $requestId = uniqid();
            $buffer = '';
            $httpReponse = null;
            $httpRequest = $client->request($request->getMethod(), $request->getUrl(), $request->getHeaders());
            $httpRequest->on('response', function (Response $response) use ($request, &$buffer, &$httpReponse, $that, $requestId) {
                $that->logDebug('[' . $requestId . ']Response received');
                $request->callResponse($response->getHeaders(), $response->getCode());
                $httpReponse = $response;
                $response->on('data', function ($data) use ($request, &$buffer, $that, $requestId) {
                    $that->logDebug('[' . $requestId . ']Data received');
                    $request->callData($data);

                    if ($request->shouldBuffer()) {
                        $buffer .= $data;
                    }
                });
            });
            $httpRequest->on('end', function () use ($request, &$buffer, &$httpReponse, $that, $requestId) {
                if ($httpReponse instanceof Response) {
                    $that->logDebug('[' . $requestId . ']Request done');
                    $request->callResolve($buffer, $httpReponse->getHeaders(), $httpReponse->getCode());
                } else {
                    $that->logDebug('[' . $requestId . ']Request done but no response received');
                    $request->callReject(new \Exception('Never received response'));
                }
            });
            $httpRequest->on('headers-written', function ($connection) use ($request, $that, $requestId) {
                $that->logDebug('[' . $requestId . ']Writing body');
                $connection->write($request->getBody());
            });
            $httpRequest->on('error', function ($error) use ($request, $that, $requestId) {
                $that->logDebug('[' . $requestId . ']Error executing request: ' . (string)$error);
                $request->callReject($error);
            });
            $that->logDebug('[' . $requestId . ']Sending request');
            $httpRequest->end();
        });
    }

    public function getClient($callback)
    {
        if ($this->client instanceof HttpClient) {
            $this->logDebug('Existing HttpClient found using it');
            $callback($this->client);
            return;
        }

        $this->logDebug('Creating new HttpClient');

        $that = $this;
        $this->getResolver(function($resolver) use ($that, $callback, $that) {
            $that->logDebug('Requesting DNS Resolver');
            $factory = new HttpClientFactory();
            $client = $factory->create($that->loop, $resolver);
            $that->setClient($client);
            $callback($client);
        });
    }

    /**
     * @param Factory $factory
     *
     * @return Resolver
     */
    public function getResolver($callback)
    {
        if ($this->resolver instanceof Resolver) {
            $callback($this->resolver);
            return;
        }

        $this->logDebug('Requesting DNS Resolver');

        $that = $this;
        $this->emitter->emit($this->dnsResolverEvent, array(function($resolver) use ($that, $callback, $that) {
            $that->logDebug('DNS Resolver received');
            $that->setResolver($resolver);
            $callback($resolver);
        }));
    }
}
