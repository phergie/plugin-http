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
        $this->assertSame(array(
            'http.request' => 'makeHttpRequest',
            'http.streamingRequest' => 'makeStreamingHttpRequest',
        ), $subscribedEvents);
    }

    public function testLogDebug() {
        $logger = $this->getMock('Monolog\Logger', array(
            'debug',
        ), array(
            'test',
        ));
        $logger->expects($this->once())
            ->method('debug')
            ->with('[Http]foo:bar');

        $plugin = new Plugin();
        $plugin->setLogger($logger);
        $plugin->logDebug('foo:bar');
    }

    public function testGetClient() {
        $httpClient = $this->getMock('React\HttpClient\Client', array(), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
        ));

        $callbackFired = false;
        $that = $this;
        $callback = function($client) use (&$callbackFired, $that, $httpClient) {
            $that->assertSame($httpClient, $client);
            $callbackFired = true;
        };

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setClient($httpClient);
        $plugin->getClient($callback);

        $this->assertTrue($callbackFired);
    }

    public function testGetFreshClient() {
        $that = $this;

        $emitter = $this->getMock('Evenement\EventEmitterInterface', array(
            'on',
            'once',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'emit',
        ));
        $emitter->expects($this->once())
            ->method('emit')
            ->with('dns.resolver')
            ->will($this->returnCallback(
                function ($eventName, $callback) use ($that) {
                    $callback[0]($that->getMock('React\Dns\Resolver\Resolver', array(), array(
                        $that->getMock('React\Dns\Query\ExecutorInterface'),
                        $that->getMock('React\Dns\Query\ExecutorInterface'),
                    )));
                }
            ));

        $callbackFired = false;
        $callback = function($client) use (&$callbackFired, $that) {
            $that->assertInstanceOf('React\HttpClient\Client', $client);
            $callbackFired = true;
        };

        $plugin = new Plugin();
        $plugin->setLoop($this->getMock('React\EventLoop\LoopInterface'));
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->setEventEmitter($emitter);
        $plugin->getClient($callback);

        $this->assertTrue($callbackFired);
    }

    public function testGetResolver() {
        $that = $this;

        $emitter = $this->getMock('Evenement\EventEmitterInterface', array(
            'on',
            'once',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'emit',
        ));
        $emitter->expects($this->once())
            ->method('emit')
            ->with('dns.resolver')
            ->will($this->returnCallback(
                function ($eventName, $callback) use ($that) {
                    $callback[0]($that->getMock('React\Dns\Resolver\Resolver', array(), array(
                        $that->getMock('React\Dns\Query\ExecutorInterface'),
                        $that->getMock('React\Dns\Query\ExecutorInterface'),
                    )));
                }
            ));

        $callbackFiredA = false;
        $callbackA = function($resolver) use (&$callbackFiredA, $that) {
            $that->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredA = true;
        };
        $callbackFiredB = false;
        $callbackB = function($resolver) use (&$callbackFiredB, $that) {
            $that->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
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


    public function testNonDefaultDnsResolverEvent() {
        $that = $this;

        $emitter = $this->getMock('Evenement\EventEmitterInterface', array(
            'on',
            'once',
            'removeListener',
            'removeAllListeners',
            'listeners',
            'emit',
        ));
        $emitter->expects($this->once())
            ->method('emit')
            ->with('foo.bar')
            ->will($this->returnCallback(
                function ($eventName, $callback) use ($that) {
                    $callback[0]($that->getMock('React\Dns\Resolver\Resolver', array(), array(
                    $that->getMock('React\Dns\Query\ExecutorInterface'),
                    $that->getMock('React\Dns\Query\ExecutorInterface'),
                    )));
                }
            ));

        $callbackFiredA = false;
        $callbackA = function($resolver) use (&$callbackFiredA, $that) {
            $that->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredA = true;
        };
        $callbackFiredB = false;
        $callbackB = function($resolver) use (&$callbackFiredB, $that) {
            $that->assertInstanceOf('React\Dns\Resolver\Resolver', $resolver);
            $callbackFiredB = true;
        };

        $plugin = new Plugin(array(
            'dnsResolverEvent' => 'foo.bar',
        ));
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
    public function testEmptyMakeHttpRequest() {
        $plugin = new Plugin();
        $plugin->makeHttpRequest();
    }

    public function testMakeHttpRequest() {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));

        $httpRequest = $this->getMock('React\HttpClient\Request', array(
            'on',
            'end',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
            $this->getMock('React\HttpClient\RequestData', array(), array(
                '',
                '',
            )),
        ));
        $httpClient = $this->getMock('React\HttpClient\Client', array(
            'request',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
        ));

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com/')
            ->willReturn($httpRequest);

        $plugin = new Plugin();
        $plugin->setClient($httpClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeHttpRequest($request);
    }

    public function testMakeStreamingHttpRequest() {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));

        $httpRequest = $this->getMock('React\HttpClient\Request', array(
            'on',
            'end',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
            $this->getMock('React\HttpClient\RequestData', array(), array(
                '',
                '',
            )),
        ));
        $httpClient = $this->getMock('React\HttpClient\Client', array(
            'request',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
            $this->getMock('React\SocketClient\ConnectorInterface'),
        ));

        $httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'http://example.com/')
            ->willReturn($httpRequest);

        $plugin = new Plugin();
        $plugin->setClient($httpClient);
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->makeStreamingHttpRequest($request);
    }

    public function testOnResponse() {
        $response = $this->getMock('React\HttpClient\Response', array(
            'on',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\Stream\Stream', array(), array(
                $this->getMock('React\EventLoop\LoopInterface'),
                $this->getMock('React\EventLoop\LoopInterface'),
            )),
            '',
            '',
            200,
            '',
            array(
                'foo' => 'bar',
            ),
        ));

        $response->expects($this->once())
            ->method('on')
            ->with('data', $this->isType('callable'))
            ->willReturnCallback(function($void, $callback) {
                $callback('abc');
                return $callback;
            });

        $that = $this;
        $buffer = 'foo';
        $httpReponse = 'bar';
        $callbackFired = false;

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'responseCallback' => function($headers, $code) use (&$callbackFired, $that) {
                $that->assertSame(array(
                    'foo' => 'bar',
                ), $headers);
                $that->assertSame(200, $code);
                $callbackFired = true;
            },
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onResponse($response, $request, $buffer, $httpReponse, 123);

        $this->assertTrue($callbackFired);
    }

    public function testOnResponseStream() {
        $response = $this->getMock('React\HttpClient\Response', array(
            'on',
        ), array(
            $this->getMock('React\EventLoop\LoopInterface'),
            $this->getMock('React\Stream\Stream', array(), array(
                $this->getMock('React\EventLoop\LoopInterface'),
                $this->getMock('React\EventLoop\LoopInterface'),
            )),
            '',
            '',
            200,
            '',
            array(
                'foo' => 'bar',
            ),
        ));

        $response->expects($this->once())
            ->method('on')
            ->with('data', $this->isType('callable'))
            ->willReturnCallback(function($void, $callback) {
                $callback('abc');
                return $callback;
            });

        $that = $this;
        $buffer = 'foo';
        $httpReponse = 'bar';
        $callbackFiredA = false;
        $callbackFiredB = false;

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'responseCallback' => function($headers, $code) use (&$callbackFiredA, $that) {
                $that->assertSame(array(
                    'foo' => 'bar',
                ), $headers);
                $that->assertSame(200, $code);
                $callbackFiredA = true;
            },
            'dataCallback' => function($data) use (&$callbackFiredB, $that) {
                $that->assertSame('abc', $data);
                $callbackFiredB = true;
            },
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onResponseStream($response, $request, $buffer, $httpReponse, 123);

        $this->assertTrue($callbackFiredA);
        $this->assertTrue($callbackFiredB);
    }

    public function testOnEndResolve() {
        $that = $this;
        $buffer = 'foo';
        $callbackFired = false;

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function($buffer, $headers, $code) use (&$callbackFired, $that) {
                $that->assertSame('foo', $buffer);
                $that->assertSame(array(
                    'foo' => 'bar',
                ), $headers);
                $that->assertSame(200, $code);
                $callbackFired = true;
            },
        ));

        $response = $this->getMock('React\HttpClient\Response', array(
            'on',
        ), array(
                $this->getMock('React\EventLoop\LoopInterface'),
                $this->getMock('React\Stream\Stream', array(), array(
                $this->getMock('React\EventLoop\LoopInterface'),
                $this->getMock('React\EventLoop\LoopInterface'),
            )),
            '',
            '',
            200,
            '',
            array(
                'foo' => 'bar',
            ),
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onEnd($request, $buffer, $response, 123);

        $this->assertTrue($callbackFired);
    }

    public function testOnEndReject() {
        $that = $this;
        $buffer = 'foo';
        $response = 'error';
        $callbackFired = false;

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'rejectCallback' => function($error) use (&$callbackFired, $that) {
                $that->assertInstanceOf('\Exception', $error);
                $that->assertSame('Never received response', $error->getMessage());
                $callbackFired = true;
            },
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onEnd($request, $buffer, $response, 123);

        $this->assertTrue($callbackFired);
    }

    public function testOnHeadersWritten() {
        $connection = $this->getMock('React\SocketClient\ConnectorInterface', array(
            'create',
            'write',
        ));
        $connection->expects($this->once())
            ->method('write')
            ->with('foo:bar');

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'body' => 'foo:bar',
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onHeadersWritten($connection, $request, 123);
    }

    public function testOnError() {
        $that = $this;
        $callbackFired = false;

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'rejectCallback' => function($error) use (&$callbackFired, $that) {
                $that->assertInstanceOf('\Exception', $error);
                $that->assertSame('abc', $error->getMessage());
                $callbackFired = true;
            },
        ));

        $plugin = new Plugin();
        $plugin->setLogger($this->getMock('Psr\Log\LoggerInterface'));
        $plugin->onError(new \Exception('abc'), $request, 123);

        $this->assertTrue($callbackFired);
    }

    public function testMakeHttpRequestCallbacks() {
        $httpRequest = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));
        $request = Phake::mock('React\HttpClient\Request');
        $response = Phake::mock('React\HttpClient\Response');
        $connection = Phake::mock('React\Stream\Stream');
        $error = new \Exception();

        $client = Phake::mock('React\HttpClient\Client');
        Phake::when($client)->request('GET', 'http://example.com/', array())->thenReturn($request);

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

    public function testStreamingMakeHttpRequestCallbacks() {
        $httpRequest = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));
        $request = Phake::mock('React\HttpClient\Request');
        $response = Phake::mock('React\HttpClient\Response');
        $connection = Phake::mock('React\Stream\Stream');
        $error = new \Exception();

        $client = Phake::mock('React\HttpClient\Client');
        Phake::when($client)->request('GET', 'http://example.com/', array())->thenReturn($request);

        $plugin = Phake::partialMock('\WyriHaximus\Phergie\Plugin\Http\Plugin');
        Phake::when($plugin)->getClient($this->isType('callable'))->thenReturn($client);
        Phake::when($plugin)->onResponseStream($response, $httpRequest, '', null, $this->isType('string'))->thenReturn(true);
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
