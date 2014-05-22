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
            'url' => 'http://wyrihaximus.net/',
            'resolveCallback' => function() {},
        ));
        $this->assertEquals(array(
            'url' => 'http://wyrihaximus.net/',
            'resolveCallback' => function() {},
            'method' => 'GET',
            'headers' => array(),
            'body' => '',
            'responseCallback' => function() {},
            'dataCallback' => function() {},
            'rejectCallback' => function() {},
            'buffer' => true,
        ), $request->getConfig());
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
            'url' => 'http://wyrihaximus.net/',
        ));
    }
}
