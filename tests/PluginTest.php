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
            //'http.streamingRequest' => 'makeStreamingHttpRequest',
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
}
