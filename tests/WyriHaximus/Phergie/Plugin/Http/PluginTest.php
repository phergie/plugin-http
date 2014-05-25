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

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testEmptyMakeHttpRequest() {
        $plugin = new Plugin();
        $plugin->makeHttpRequest();
    }
}
