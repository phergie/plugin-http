<?php
/**
 * This file is part of PhergieHttp.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phergie\Tests\Plugin\Http;

use React\Promise\FulfilledPromise;
use Phergie\Plugin\Http\Plugin;
use Phergie\Plugin\Http\Request;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Phergie\Plugin\Http
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSubscribedEvents()
    {
        $plugin = new Plugin();
        $subscribedEvents = $plugin->getSubscribedEvents();
        $this->assertInternalType('array', $subscribedEvents);
        $this->assertSame(
            [
                'http.client' => 'getGuzzleClient',
                'http.request' => 'makeHttpRequest',
                'http.streamingRequest' => 'makeStreamingHttpRequest',
            ],
            $subscribedEvents
        );
    }

    public function testLogDebug()
    {
        $logger = $this->getMock(
            'Monolog\Logger',
            [
                'debug',
            ],
            [
                'test',
            ]
        );
        $logger->expects($this->once())
            ->method('debug')
            ->with('[Http]foo:bar');

        $plugin = new Plugin();
        $plugin->setLogger($logger);
        $plugin->logDebug('foo:bar');
    }

    public function testGetLoop()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $plugin = new Plugin();
        $plugin->setLoop($loop);
        $this->assertEquals($loop, $plugin->getLoop($loop));
    }

    public function testGetResolver()
    {
        $emitter = $this->getMock(
            'Evenement\EventEmitterInterface',
            [
                'on',
                'once',
                'removeListener',
                'removeAllListeners',
                'listeners',
                'emit',
            ]
        );
        $emitter->expects($this->once())
            ->method('emit')
            ->with('dns.resolver')
            ->will(
                $this->returnCallback(
                    function ($eventName, $callback) {
                        $callback[0](
                            $this->getMock(
                                'React\Dns\Resolver\Resolver',
                                [],
                                [
                                    $this->getMock('React\Dns\Query\ExecutorInterface'),
                                    $this->getMock('React\Dns\Query\ExecutorInterface'),
                                ]
                            )
                        );
                    }
                )
            );

        $callbackFiredA = false;
        $callbackA = function ($resolver) use (&$callbackFiredA) {
            $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredA = true;
        };
        $callbackFiredB = false;
        $callbackB = function ($resolver) use (&$callbackFiredB) {
            $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredB = true;
        };

        $plugin = new Plugin();
        $plugin->setLoop($this->getMock('React\EventLoop\LoopInterface'));
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setEventEmitter($emitter);
        $plugin->getResolver($callbackA);
        $plugin->getResolver($callbackB);

        $this->assertTrue($callbackFiredA);
        $this->assertTrue($callbackFiredB);
    }


    public function testNonDefaultDnsResolverEvent()
    {
        $emitter = $this->getMock(
            'Evenement\EventEmitterInterface',
            [
                'on',
                'once',
                'removeListener',
                'removeAllListeners',
                'listeners',
                'emit',
            ]
        );
        $emitter->expects($this->once())
            ->method('emit')
            ->with('foo.bar')
            ->will(
                $this->returnCallback(
                    function ($eventName, $callback) {
                        $callback[0](
                            $this->getMock(
                                'React\Dns\Resolver\Resolver',
                                [],
                                [
                                    $this->getMock('React\Dns\Query\ExecutorInterface'),
                                    $this->getMock('React\Dns\Query\ExecutorInterface'),
                                ]
                            )
                        );
                    }
                )
            );

        $callbackFiredA = false;
        $callbackA = function ($resolver) use (&$callbackFiredA) {
            $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredA = true;
        };
        $callbackFiredB = false;
        $callbackB = function ($resolver) use (&$callbackFiredB) {
            $this->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredB = true;
        };

        $plugin = new Plugin(
            [
                'dnsResolverEvent' => 'foo.bar',
            ]
        );
        $plugin->setLoop($this->getMock('React\EventLoop\LoopInterface'));
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setEventEmitter($emitter);
        $plugin->getResolver($callbackA);
        $plugin->getResolver($callbackB);

        $this->assertTrue($callbackFiredA);
        $this->assertTrue($callbackFiredB);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testEmptyMakeHttpRequest()
    {
        if (PHP_MAJOR_VERSION === 5)
        {
            $plugin = new Plugin();
            $plugin->makeHttpRequest();
        }
        else
        {
            trigger_error('PHPUnit_Framework_Error');
        }
    }

    /**
     * @requires PHP 7
     */
    public function testEmptyMakeHttpRequest70()
    {
        try {
            $plugin = new Plugin();
            $plugin->makeHttpRequest();
        } catch (\TypeError $e) {
            $this->assertInstanceOf('TypeError', $e);
        }
    }

    public function testMakeHttpRequest()
    {
        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
            ]
        );

        $httpClientAdapter = $this->getMock(
            'WyriHaximus\React\RingPHP\HttpClientAdapter',
            [
                '__invoke',
            ],
            [
                $this->getMock('React\EventLoop\LoopInterface'),
            ]
        );

        $guzzleClient = $this->getMock(
            'GuzzleHttp\Client',
            [
                'send',
            ],
            [
                [
                    'handler' => $httpClientAdapter,
                ],
            ]
        );

        $guzzleClient->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('GuzzleHttp\Message\RequestInterface'))
            ->willReturn(new FulfilledPromise());

        $plugin = new Plugin();
        $plugin->setGuzzleClient($guzzleClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeHttpRequest($request);
    }
    public function testMakeStreamingHttpRequest()
    {
        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
            ]
        );

        $httpClientAdapter = $this->getMock(
            'WyriHaximus\React\RingPHP\HttpClientAdapter',
            [
                '__invoke',
            ],
            [
                $this->getMock('React\EventLoop\LoopInterface'),
            ]
        );

        $guzzleClient = $this->getMock(
            'GuzzleHttp\Client',
            [
                'send',
            ],
            [
                [
                    'handler' => $httpClientAdapter,
                ],
            ]
        );

        $guzzleClient->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('GuzzleHttp\Message\RequestInterface'))
            ->willReturn(new FulfilledPromise());

        $plugin = new Plugin();
        $plugin->setGuzzleClient($guzzleClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeStreamingHttpRequest($request);
    }
    public function testMakeHttpRequestWithHeaders()
    {
        $request = $this->getMock(
            '\Phergie\Plugin\Http\Request',
            [
                'getHeaders'
            ],
            [
                [
                    'url' => 'http://example.com/',
                    'headers' => [
                        'Accept' => 'text/html'
                    ],
                    'resolveCallback' => function () {
                    }
                ]
            ]
        );

        $httpClientAdapter = $this->getMock(
            'WyriHaximus\React\RingPHP\HttpClientAdapter',
            [
                '__invoke',
            ],
            [
                $this->getMock('React\EventLoop\LoopInterface'),
            ]
        );

        $guzzleClient = $this->getMock(
            'GuzzleHttp\Client',
            [
                'send',
            ],
            [
                [
                    'handler' => $httpClientAdapter,
                ],
            ]
        );

        $request->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Accept' => 'text/html']);

        $guzzleClient->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('GuzzleHttp\Message\RequestInterface'))
            ->willReturn(new FulfilledPromise());

        $plugin = new Plugin();
        $plugin->setGuzzleClient($guzzleClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeHttpRequest($request);
    }
    public function testMakeStreamingHttpRequestWithHeaders()
    {
        $request = $this->getMock(
            '\Phergie\Plugin\Http\Request',
            [
                'getHeaders'
            ],
            [
                [
                    'url' => 'http://example.com/',
                    'headers' => [
                        'Accept' => 'text/html'
                    ],
                    'resolveCallback' => function () {
                    }
                ]
            ]
        );

        $httpClientAdapter = $this->getMock(
            'WyriHaximus\React\RingPHP\HttpClientAdapter',
            [
                '__invoke',
            ],
            [
                $this->getMock('React\EventLoop\LoopInterface'),
            ]
        );

        $guzzleClient = $this->getMock(
            'GuzzleHttp\Client',
            [
                'send',
            ],
            [
                [
                    'handler' => $httpClientAdapter,
                ],
            ]
        );

        $request->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Accept' => 'text/html']);

        $guzzleClient->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('GuzzleHttp\Message\RequestInterface'))
            ->willReturn(new FulfilledPromise());

        $plugin = new Plugin();
        $plugin->setGuzzleClient($guzzleClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeStreamingHttpRequest($request);
    }
}
