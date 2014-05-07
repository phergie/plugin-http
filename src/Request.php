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

    public function getConfig() {
        return $this->config;
    }

    protected function setDefaults() {
        if (!isset($this->config['method'])) {
            $this->config['method'] = 'GET';
        }

        if (!isset($this->config['headers'])) {
            $this->config['headers'] = array();
        }

        if (!isset($this->config['body'])) {
            $this->config['body'] = '';
        }

        if (!isset($this->config['responseCallback'])) {
            $this->config['responseCallback'] = function() {};
        }

        if (!isset($this->config['dataCallback'])) {
            $this->config['dataCallback'] = function() {};
        }

        if (!isset($this->config['rejectCallback'])) {
            $this->config['rejectCallback'] = function() {};
        }
        
        if (!isset($this->config['buffer'])) {
            $this->config['buffer'] = true;
        }
    }
    
    public function getMethod() {
        return $this->config['method'];
    }
    
    public function getUrl() {
        return $this->config['url'];
    }
    
    public function getHeaders() {
        return $this->config['headers'];
    }
    
    public function getBody() {
        return $this->config['body'];
    }
    
    public function callResolve($payload = null) {
        return $this->config['resolveCallback']($payload);
    }
    
    public function callResponse($payload = null) {
        return $this->config['responseCallback']($payload);
    }
    
    public function callData($payload = null) {
        return $this->config['dataCallback']($payload);
    }
    
    public function callReject($payload = null) {
        return $this->config['rejectCallback']($payload);
    }
    
    public function shouldBuffer() {
        return (bool)$this->config['buffer'];
    }
}
