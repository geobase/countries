<?php

namespace Smart\Geo\Generator\Provider;

use Smart\Geo\Generator\Container;

class Cache
{
    const PERIOD = 3600;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return string
     */
    private function getCacheDir()
    {
        return realpath(__DIR__ . '/../../.cache');
    }

    /**
     * @param string $key
     * @return string
     */
    private function getCacheFile($key)
    {
        $key = hash('ripemd160', $key);
        return $this->getCacheDir() . DIRECTORY_SEPARATOR. $key;
    }

    public function voidCache()
    {
        foreach (scandir($this->getCacheDir()) as $item) {
            if ($item !== '.' && $item !== '..') {
                unlink($this->getCacheDir() . DIRECTORY_SEPARATOR . basename($item));
            }
        }
    }

    /**
     * @param string $key
     * @return string
     */
    public function get($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return null;
    }

    /**
     * @param string $key
     * @param string $reponse
     */
    public function set($key, $reponse)
    {
        file_put_contents($this->getCacheFile($key), $reponse);
    }
}
