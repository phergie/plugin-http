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

use WyriHaximus\Phergie\Plugin\Http\Request;

/**
 * Tests for the Request class.
 *
 * @category Phergie
 * @package WyriHaximus\Phergie\Plugin\Http
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{

    public function testDefaultConfig()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));
        $config = $request->getConfig();
        $this->assertSame(8, count($config));
        $this->assertTrue(isset($config['url']));
        $this->assertSame('http://example.com/', $config['url']);
        $this->assertTrue(isset($config['resolveCallback']));
        $this->assertInternalType('callable', $config['resolveCallback']);
        $this->assertTrue(isset($config['method']));
        $this->assertSame('GET', $config['method']);
        $this->assertTrue(isset($config['headers']));
        $this->assertSame(array(), $config['headers']);
        $this->assertTrue(isset($config['body']));
        $this->assertSame('', $config['body']);
        $this->assertTrue(isset($config['responseCallback']));
        $this->assertInternalType('callable', $config['responseCallback']);
        $this->assertTrue(isset($config['dataCallback']));
        $this->assertInternalType('callable', $config['dataCallback']);
        $this->assertTrue(isset($config['rejectCallback']));
        $this->assertInternalType('callable', $config['rejectCallback']);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://example.com/', $request->getUrl());
        $this->assertSame(array(), $request->getHeaders());
        $this->assertSame('', $request->getBody());
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testEmptyConfig()
    {
        $request = new Request();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNoUrlConfig()
    {
        $request = new Request(array());
    }
    /**
     * @expectedException InvalidArgumentException
     */
    public function testNoResolveCallbackConfig()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
        ));
    }

    public function testGetUrl()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
        ));
        $this->assertSame('http://example.com/', $request->getUrl());
    }

    public function testGetMethod()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'method' => 'POST',
        ));
        $this->assertSame('POST', $request->getMethod());
    }

    public function testGetHeaders()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'headers' => array(
                'foo' => 'bar',
            ),
        ));
        $this->assertSame(array(
            'foo' => 'bar',
        ), $request->getHeaders());
    }

    public function testGetBody()
    {
        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'body' => 'foo:bar',
        ));
        $this->assertSame('foo:bar', $request->getBody());
    }

    public function testCallResolve()
    {
        $callbackFired = false;
        $that = $this;
        $callback = function($buffer, $headers, $code) use (&$callbackFired, $that) {
            $that->assertSame('bar:foo', $buffer);
            $that->assertSame(array('bar' => 'foo'), $headers);
            $that->assertSame(123, $code);
            $callbackFired = true;
        };

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => $callback,
        ));

        $request->callResolve('bar:foo', array('bar' => 'foo'), 123);
        $this->assertTrue($callbackFired);
    }

    public function testCallReject()
    {
        $callbackFired = false;
        $that = $this;
        $callback = function($error) use (&$callbackFired, $that) {
            $that->assertSame('bar:foo', $error);
            $callbackFired = true;
        };

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'rejectCallback' => $callback,
        ));

        $request->callReject('bar:foo');
        $this->assertTrue($callbackFired);
    }

    public function testCallResponse()
    {
        $callbackFired = false;
        $that = $this;
        $callback = function($headers, $code) use (&$callbackFired, $that) {
            $that->assertSame(array('bar' => 'foo'), $headers);
            $that->assertSame(123, $code);
            $callbackFired = true;
        };

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'responseCallback' => $callback,
        ));

        $request->callResponse(array('bar' => 'foo'), 123);
        $this->assertTrue($callbackFired);
    }

    public function testCallData()
    {
        $callbackFired = false;
        $that = $this;
        $callback = function($data) use (&$callbackFired, $that) {
            $that->assertSame('bar:foo', $data);
            $callbackFired = true;
        };

        $request = new Request(array(
            'url' => 'http://example.com/',
            'resolveCallback' => function() {},
            'dataCallback' => $callback,
        ));

        $request->callData('bar:foo');
        $this->assertTrue($callbackFired);
    }
}
