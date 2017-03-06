<?php

namespace Smart\Geo\Generator\Tests;

use PHPUnit_Framework_TestCase;
use Smart\Geo\Generator\Provider\Cache;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @return \Smart\Geo\Generator\Container
     */
    public function getContainer()
    {
        global $container;
        return $container;
    }

    public function emptyCache()
    {
        (new Cache($this->getContainer()))->voidCache();
    }
}
