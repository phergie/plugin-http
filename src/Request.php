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
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        
        if (!isset($this->config['url']) {
            throw new \InvalidArgumentException('Missing url index');
        }
        
        $this->setDefaults();
    }

    public function getConfig() {
        return $this->config;
    }

    protected function setDefaults() {

    }
}
