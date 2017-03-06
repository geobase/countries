<?php

namespace Smart\Geo\Generator\Provider;

use GuzzleHttp\Client;
use Smart\Geo\Generator\Container;

class Http implements HttpInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->client = new Client();
        $this->cache = new Cache($container);
    }

    /**
     * @param string $file
     * @return string
     */
    public function get($file)
    {
        if ($this->cache->get($file)) {
            $response = $this->cache->get($file);
        } else {
            $response = $this->client->get($file)->getBody()->getContents();
            $this->cache->set($file, $response);
        }
        return $response;
    }
}
