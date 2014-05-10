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
        $httpReponse = null;
        $httpRequest = $this->getClient()->request($request->getMethod(), $request->getUrl(), $request->getHeaders());
        $httpRequest->on('response', function (Response $response) use ($request, &$buffer, &$httpReponse) {
            $request->callResponse($response->getHeaders(), $response->getCode());
            $httpReponse = $response;

            $response->on('data', function ($data) use ($request, &$buffer) {
                $request->callData($data);
                
                if ($request->shouldBuffer()) {
                    $buffer .= $data;
                }
            });
        });
        $httpRequest->on('end', function () use ($request, &$buffer, &$httpReponse) {
            $request->callResolve($buffer, $httpReponse->getHeaders(), $httpReponse->getCode());
        });
        $httpRequest->on('headers-written', function ($that) use ($request) {
            $that->write($request->getBody());
        });
        $httpRequest->on('error', function ($error) use ($request) {
            $request->callReject($error);
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

    /**
     * @param Factory $factory
     *
     * @return Resolver
     */
    public function getResolver()
    {
        if ($this->resolver instanceof Resolver) {
            return $this->resolver;
        }

        $this->resolver = $this->emitter->emit($this->dnsResolverEvent);

        return $this->resolver;
    }
}
