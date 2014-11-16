<?php
/**
 * This file is part of PhergieHttp.
 *
 ** (c) 2014 Cees-Jan Kiewiet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WyriHaximus\Phergie\Tests\Plugin\Http;

use WyriHaximus\Phergie\Plugin\Http\Plugin;
use WyriHaximus\Phergie\Plugin\Http\Request;

use Phake;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package WyriHaximus\Phergie\Plugin\Http
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

    public function testGetClient()
    {
        $httpClient = $this->getMock(
            'React\HttpClient\Client',
            [],
            [
                $this->getMock('React\SocketClient\ConnectorInterface'),
                $this->getMock('React\SocketClient\ConnectorInterface'),
            ]
        );

        $callbackFired = false;
        $callback = function ($client) use (&$callbackFired, $httpClient) {
            $this->assertSame($httpClient, $client);
            $callbackFired = true;
        };

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setClient($httpClient);
        $plugin->getClient($callback);

        $this->assertTrue($callbackFired);
    }

    public function testGetFreshClient()
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

        $callbackFired = false;
        $callback = function ($client) use (&$callbackFired) {
            $this->assertInstanceOf('React\HttpClient\Client', $client);
            $callbackFired = true;
        };

        $plugin = new Plugin();
        $plugin->setLoop($this->getMock('React\EventLoop\LoopInterface'));
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setEventEmitter($emitter);
        $plugin->getClient($callback);

        $this->assertTrue($callbackFired);
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
        $plugin = new Plugin();
        $plugin->makeHttpRequest();
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

        $httpRequest = $this->getMock(
            'React\HttpClient\Request',
            [
                'on',
                'end',
            ],
            [
                $this->getMock('React\SocketClient\ConnectorInterface'),
                $this->getMock(
                    'React\HttpClient\RequestData',
                    [],
                    [
                        '',
                        '',
                    ]
                ),
            ]
        );
        $httpClient = $this->getMock(
            'React\HttpClient\Client',
            [
                'request',
            ],
            [
                $this->getMock('React\SocketClient\ConnectorInterface'),
                $this->getMock('React\SocketClient\ConnectorInterface'),
            ]
        );

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com/')
            ->willReturn($httpRequest);

        $plugin = new Plugin();
        $plugin->setClient($httpClient);
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

        $httpRequest = $this->getMock(
            'React\HttpClient\Request',
            [
                'on',
                'end',
            ],
            [
                $this->getMock('React\SocketClient\ConnectorInterface'),
                $this->getMock(
                    'React\HttpClient\RequestData',
                    [],
                    [
                        '',
                        '',
                    ]
                ),
            ]
        );
        $httpClient = $this->getMock(
            'React\HttpClient\Client',
            [
                'request',
            ],
            [
                $this->getMock('React\SocketClient\ConnectorInterface'),
                $this->getMock('React\SocketClient\ConnectorInterface'),
            ]
        );

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com/')
            ->willReturn($httpRequest);

        $plugin = new Plugin();
        $plugin->setClient($httpClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeStreamingHttpRequest($request);
    }

    public function testOnResponse()
    {
        $stream = fopen('php://temp', 'r+');
        $response = $this->getMock(
            'React\HttpClient\Response',
            [
                'on',
            ],
            [
                $this->getMock(
                    'React\Stream\Stream',
                    [],
                    [
                        $stream,
                        $this->getMock('React\EventLoop\LoopInterface'),
                    ]
                ),
                '',
                '',
                200,
                '',
                [
                    'foo' => 'bar',
                ],
            ]
        );

        $response->expects($this->once())
            ->method('on')
            ->with('data', $this->isType('callable'))
            ->willReturnCallback(
                function ($void, $callback) {
                    $callback('abc');
                    return $callback;
                }
            );

        $buffer = 'foo';
        $httpReponse = 'bar';
        $callbackFired = false;

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
                'responseCallback' => function ($headers, $code) use (&$callbackFired) {
                    $this->assertSame(
                        [
                            'foo' => 'bar',
                        ],
                        $headers
                    );
                    $this->assertSame(200, $code);
                    $callbackFired = true;
                },
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onResponse($response, $request, $buffer, $httpReponse, 123);

        $this->assertTrue($callbackFired);

        fclose($stream);
    }

    public function testOnResponseStream()
    {
        $stream = fopen('php://temp', 'r+');
        $response = $this->getMock(
            'React\HttpClient\Response',
            [
                'on',
            ],
            [
                $this->getMock(
                    'React\Stream\Stream',
                    [],
                    [
                        $stream,
                        $this->getMock('React\EventLoop\LoopInterface'),
                    ]
                ),
                '',
                '',
                200,
                '',
                [
                    'foo' => 'bar',
                ],
            ]
        );

        $response->expects($this->once())
            ->method('on')
            ->with('data', $this->isType('callable'))
            ->willReturnCallback(
                function ($void, $callback) {
                    $callback('abc');
                    return $callback;
                }
            );

        $buffer = 'foo';
        $httpReponse = 'bar';
        $callbackFiredA = false;
        $callbackFiredB = false;

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
                'responseCallback' => function ($headers, $code) use (&$callbackFiredA) {
                    $this->assertSame(
                        [
                            'foo' => 'bar',
                        ],
                        $headers
                    );
                    $this->assertSame(200, $code);
                    $callbackFiredA = true;
                },
                'dataCallback' => function ($data) use (&$callbackFiredB) {
                    $this->assertSame('abc', $data);
                    $callbackFiredB = true;
                },
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onResponseStream($response, $request, $buffer, $httpReponse, 123);

        $this->assertTrue($callbackFiredA);
        $this->assertTrue($callbackFiredB);

        fclose($stream);
    }

    public function testOnEndResolve()
    {
        $stream = fopen('php://temp', 'r+');
        $buffer = 'foo';
        $callbackFired = false;

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function ($buffer, $headers, $code) use (&$callbackFired) {
                    $this->assertSame('foo', $buffer);
                    $this->assertSame(
                        [
                            'foo' => 'bar',
                        ],
                        $headers
                    );
                    $this->assertSame(200, $code);
                    $callbackFired = true;
                },
            ]
        );

        $response = $this->getMock(
            'React\HttpClient\Response',
            [
                'on',
            ],
            [
                $this->getMock(
                    'React\Stream\Stream',
                    [],
                    [
                        $stream,
                        $this->getMock('React\EventLoop\LoopInterface'),
                    ]
                ),
                '',
                '',
                200,
                '',
                [
                    'foo' => 'bar',
                ],
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onEnd($request, $buffer, $response, 123);

        $this->assertTrue($callbackFired);

        fclose($stream);
    }

    public function testOnEndReject()
    {
        $buffer = 'foo';
        $response = 'error';
        $callbackFired = false;

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
                'rejectCallback' => function ($error) use (&$callbackFired) {
                    $this->assertInstanceOf('\Exception', $error);
                    $this->assertSame('Never received response', $error->getMessage());
                    $callbackFired = true;
                },
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onEnd($request, $buffer, $response, 123);

        $this->assertTrue($callbackFired);
    }

    public function testOnHeadersWritten()
    {
        $connection = $this->getMock(
            'React\SocketClient\ConnectorInterface',
            [
                'create',
                'write',
            ]
        );
        $connection->expects($this->once())
            ->method('write')
            ->with('foo:bar');

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
                'body' => 'foo:bar',
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onHeadersWritten($connection, $request, 123);
    }

    public function testOnError()
    {
        $callbackFired = false;

        $request = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
                'rejectCallback' => function ($error) use (&$callbackFired) {
                    $this->assertInstanceOf('\Exception', $error);
                    $this->assertSame('abc', $error->getMessage());
                    $callbackFired = true;
                },
            ]
        );

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onError(new \Exception('abc'), $request, 123);

        $this->assertTrue($callbackFired);
    }

    public function testMakeHttpRequestCallbacks()
    {
        $httpRequest = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
            ]
        );
        $request = Phake::mock('React\HttpClient\Request');
        $response = Phake::mock('React\HttpClient\Response');
        $connection = Phake::mock('React\Stream\Stream');
        $error = new \Exception();

        $client = Phake::mock('React\HttpClient\Client');
        Phake::when($client)->request('GET', 'http://example.com/', [])->thenReturn($request);

        $plugin = Phake::partialMock('\WyriHaximus\Phergie\Plugin\Http\Plugin');
        Phake::when($plugin)->getClient($this->isType('callable'))->thenReturn($client);
        Phake::when($plugin)->onResponse($response, $httpRequest, '', null, $this->isType('string'))->thenReturn(true);
        Phake::when($plugin)->onEnd($httpRequest, '', null, $this->isType('string'))->thenReturn(true);
        Phake::when($plugin)->onHeadersWritten($connection, $httpRequest, $this->isType('string'))->thenReturn(true);
        Phake::when($plugin)->onError($error, $httpRequest, $this->isType('string'))->thenReturn(true);

        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeHttpRequest($httpRequest);

        Phake::verify($plugin)->getClient(
            Phake::capture($callbackClient)->when($this->isType('callable'))
        );
        $callbackClient($client);

        Phake::verify($request)->on(
            'response',
            Phake::capture($callbackOnRequest)->when($this->isType('callable'))
        );
        $callbackOnRequest($response);
        Phake::verify($plugin)->onResponse(
            $response,
            $httpRequest,
            '',
            null,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'end',
            Phake::capture($callbackOnEnd)->when($this->isType('callable'))
        );
        $callbackOnEnd();
        Phake::verify($plugin)->onEnd(
            $httpRequest,
            '',
            null,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'headers-written',
            Phake::capture($callbackOnHeadersWritten)->when($this->isType('callable'))
        );
        $callbackOnHeadersWritten($connection);
        Phake::verify($plugin)->onHeadersWritten(
            $connection,
            $httpRequest,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'error',
            Phake::capture($callbackOnError)->when($this->isType('callable'))
        );
        $callbackOnError($error);
        Phake::verify($plugin)->onError(
            $error,
            $httpRequest,
            $this->isType('string')
        );
    }

    public function testStreamingMakeHttpRequestCallbacks()
    {
        $httpRequest = new Request(
            [
                'url' => 'http://example.com/',
                'resolveCallback' => function () {
                },
            ]
        );
        $request = Phake::mock('React\HttpClient\Request');
        $response = Phake::mock('React\HttpClient\Response');
        $connection = Phake::mock('React\Stream\Stream');
        $error = new \Exception();

        $client = Phake::mock('React\HttpClient\Client');
        Phake::when($client)->request('GET', 'http://example.com/', [])->thenReturn($request);

        $plugin = Phake::partialMock('\WyriHaximus\Phergie\Plugin\Http\Plugin');
        Phake::when($plugin)->getClient($this->isType('callable'))->thenReturn($client);
        Phake::when($plugin)->onResponseStream(
            $response,
            $httpRequest,
            '',
            null,
            $this->isType('string')
        )->thenReturn(true);
        Phake::when($plugin)->onEnd($httpRequest, '', null, $this->isType('string'))->thenReturn(true);
        Phake::when($plugin)->onHeadersWritten($connection, $httpRequest, $this->isType('string'))->thenReturn(true);
        Phake::when($plugin)->onError($error, $httpRequest, $this->isType('string'))->thenReturn(true);

        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeStreamingHttpRequest($httpRequest);

        Phake::verify($plugin)->getClient(
            Phake::capture($callbackClient)->when($this->isType('callable'))
        );
        $callbackClient($client);

        Phake::verify($request)->on(
            'response',
            Phake::capture($callbackOnRequest)->when($this->isType('callable'))
        );
        $callbackOnRequest($response);
        Phake::verify($plugin)->onResponseStream(
            $response,
            $httpRequest,
            '',
            null,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'end',
            Phake::capture($callbackOnEnd)->when($this->isType('callable'))
        );
        $callbackOnEnd();
        Phake::verify($plugin)->onEnd(
            $httpRequest,
            '',
            null,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'headers-written',
            Phake::capture($callbackOnHeadersWritten)->when($this->isType('callable'))
        );
        $callbackOnHeadersWritten($connection);
        Phake::verify($plugin)->onHeadersWritten(
            $connection,
            $httpRequest,
            $this->isType('string')
        );

        Phake::verify($request)->on(
            'error',
            Phake::capture($callbackOnError)->when($this->isType('callable'))
        );
        $callbackOnError($error);
        Phake::verify($plugin)->onError(
            $error,
            $httpRequest,
            $this->isType('string')
        );
    }
}
