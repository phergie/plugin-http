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
 * Plugin for Provide HTTP functionality to other plugins.
 *
 * @category Phergie
 * @package WyriHaximus\Phergie\Plugin\Http
 */
class Request
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     *
     *
     * @param array $config
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if (!isset($this->config['url'])) {
            throw new \InvalidArgumentException('Missing url');
        }

        if (!isset($this->config['resolveCallback'])) {
            throw new \InvalidArgumentException('Missing resolve callback');
        }

        $this->setDefaults();
    }

    public function getConfig()
    {
        return $this->config;
    }

    protected function setDefaults()
    {
        if (!isset($this->config['method'])) {
            $this->config['method'] = 'GET';
        }

        if (!isset($this->config['headers'])) {
            $this->config['headers'] = [];
        }

        if (!isset($this->config['body'])) {
            $this->config['body'] = '';
        }

        if (!isset($this->config['rejectCallback'])) {
            $this->config['rejectCallback'] = function () {
            };
        }
    }

    public function getMethod()
    {
        return $this->config['method'];
    }

    public function getUrl()
    {
        return $this->config['url'];
    }

    public function getHeaders()
    {
        return $this->config['headers'];
    }

    public function getBody()
    {
        return $this->config['body'];
    }

    public function callResolve($response)
    {
        return $this->config['resolveCallback']($response);
    }

    /**
     * @param \Exception $error
     */
    public function callReject($error)
    {
        return $this->config['rejectCallback']($error);
    }
}
