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
use React\EventLoop\LoopInterface;
use Phergie\Irc\Client\React\LoopAwareInterface;
use React\HttpClient\Client as HttpClient;
use React\HttpClient\Factory as HttpClientFactory;
use React\Promise\Deferred;
use React\Dns\Resolver\Factory as ResolverFactory;
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

    public function setLoop(LoopInterface $loop) {
        $this->loop = $loop;
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

    /**
     *
     *
     * @param Request $request
     */
    public function makeHttpRequest(Request $request)
    {
        $buffer = '';
        $httpRequest = $this->getClient()->request($request->getMethod(), $request->getUrl(), $request->getHeaders());
        $httpRequest->on('response', function ($response) use ($request, &$buffer) {
            $request->callResponse($response);
            $response->on('data', function ($data) use ($request, &$buffer) {
                $request->callData($response);
                
                if ($request->shouldBuffer()) {
                    $buffer .= $data;
                }
            });
        });
        $httpRequest->on('end', function () use ($request, &$buffer) {
            $request->callResolve($buffer);
        });
        $httpRequest->on('headers-written', function ($that) use ($request) {
            $that->write($request->getBody());
        });
        $httpRequest->end();
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

        $factory = new ResolverFactory();

        $this->resolver = $factory->createCached('8.8.8.8', $this->loop);

        return $this->resolver;
    }
}
