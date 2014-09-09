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
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var null|Resolver
     */
    protected $resolver;

    /**
     * @var null|HttpClient
     */
    protected $client;

    /**
     * @var string
     */
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
     * @param Resolver $resolver
     */
    public function setResolver(Resolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param HttpClient $client
     */
    public function setClient(HttpClient $client)
    {
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
            'http.streamingRequest' => 'makeStreamingHttpRequest',
        );
    }

    /**
     * @param $message
     */
    public function logDebug($message)
    {
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

        $this->getClient(function (HttpClient $client) use ($request, $that) {
            $requestId = uniqid();
            $buffer = '';
            $httpResponse = null;
            $httpRequest = $client->request($request->getMethod(), $request->getUrl(), $request->getHeaders());
            $httpRequest->on('response', function (Response $response) use ($request, &$buffer, &$httpResponse, $that, $requestId) {
                $that->onResponse($response, $request, $buffer, $httpResponse, $requestId);
            });
            $httpRequest->on('end', function () use ($request, &$buffer, &$httpResponse, $that, $requestId) {
                $that->onEnd($request, $buffer, $httpResponse, $requestId);
            });
            $httpRequest->on('headers-written', function ($connection) use ($request, $that, $requestId) {
                $that->onHeadersWritten($connection, $request, $requestId);
            });
            $httpRequest->on('error', function ($error) use ($request, $that, $requestId) {
                $that->onError($error, $request, $requestId);
            });
            $that->logDebug('[' . $requestId . ']Sending request');
            $httpRequest->end();
        });
    }

    /**
     *
     *
     * @param Request $request
     */
    public function makeStreamingHttpRequest(Request $request)
    {
        $that = $this;

        $this->getClient(function (HttpClient $client) use ($request, $that) {
            $requestId = uniqid();
            $buffer = '';
            $httpResponse = null;
            $httpRequest = $client->request($request->getMethod(), $request->getUrl(), $request->getHeaders());
            $httpRequest->on('response', function (Response $response) use ($request, &$buffer, &$httpResponse, $that, $requestId) {
                $that->onResponseStream($response, $request, $buffer, $httpResponse, $requestId);
            });
            $httpRequest->on('end', function () use ($request, &$buffer, &$httpResponse, $that, $requestId) {
                $that->onEnd($request, $buffer, $httpResponse, $requestId);
            });
            $httpRequest->on('headers-written', function ($connection) use ($request, $that, $requestId) {
                $that->onHeadersWritten($connection, $request, $requestId);
            });
            $httpRequest->on('error', function ($error) use ($request, $that, $requestId) {
                $that->onError($error, $request, $requestId);
            });
            $that->logDebug('[' . $requestId . ']Sending request');
            $httpRequest->end();
        });
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param string $buffer
     * @param int $httpReponse
     * @param string $requestId
     */
    public function onResponse(Response $response, Request $request, &$buffer, &$httpReponse, $requestId)
    {
        $that = $this;
        $httpReponse = $response;

        $this->logDebug('[' . $requestId . ']Response received');
        $request->callResponse($response->getHeaders(), $response->getCode());
        $response->on('data', function ($data) use (&$buffer, $that, $requestId) {
            $that->logDebug('[' . $requestId . ']Data received');
            $buffer .= $data;
        });
    }

    /**
     * @param Response $response
     * @param Request $request
     * @param string $buffer
     * @param int $httpReponse
     * @param string $requestId
     */
    public function onResponseStream(Response $response, Request $request, &$buffer, &$httpReponse, $requestId)
    {
        $that = $this;
        $httpReponse = $response;

        $this->logDebug('[' . $requestId . ']Response received');
        $request->callResponse($response->getHeaders(), $response->getCode());

        $response->on('data', function ($data) use ($request, &$buffer, $that, $requestId) {
            $that->logDebug('[' . $requestId . ']Data received');
            $request->callData($data);
        });
    }

    /**
     * @param Request $request
     * @param string $buffer
     * @param int $httpReponse
     * @param string $requestId
     */
    public function onEnd(Request $request, &$buffer, &$httpReponse, $requestId)
    {
        if ($httpReponse instanceof Response) {
            $this->logDebug('[' . $requestId . ']Request done');
            $request->callResolve($buffer, $httpReponse->getHeaders(), $httpReponse->getCode());
        } else {
            $this->logDebug('[' . $requestId . ']Request done but no response received');
            $request->callReject(new \Exception('Never received response'));
        }
    }

    /**
     * @param $connection
     * @param Request $request
     * @param string $requestId
     */
    public function onHeadersWritten($connection, Request $request, $requestId)
    {
        $this->logDebug('[' . $requestId . ']Writing body');
        $connection->write($request->getBody());
    }

    /**
     * @param \Exception $error
     * @param Request $request
     * @param int $requestId
     */
    public function onError(\Exception $error, Request $request, $requestId)
    {
        $this->logDebug('[' . $requestId . ']Error executing request: ' . (string)$error);
        $request->callReject($error);
    }

    /**
     * @param callable $callback
     */
    public function getClient($callback)
    {
        if ($this->client instanceof HttpClient) {
            $this->logDebug('Existing HttpClient found, using it');
            $callback($this->client);
            return;
        }

        $this->logDebug('Creating new HttpClient');

        $that = $this;
        $this->getResolver(function ($resolver) use ($that, $callback, $that) {
            $that->logDebug('Requesting DNS Resolver');
            $factory = new HttpClientFactory();
            $client = $factory->create($that->getLoop(), $resolver);
            $that->setClient($client);
            $callback($client);
        });
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

        $that = $this;
        $this->emitter->emit($this->dnsResolverEvent, array(function ($resolver) use ($that, $callback, $that) {
            $that->logDebug('DNS Resolver received');
            $that->setResolver($resolver);
            $callback($resolver);
        }));
    }
}
