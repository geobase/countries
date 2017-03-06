<?php

namespace Smart\Geo\Generator\DataGenerator\Country\GeoNamesCountryList;

use Smart\Geo\Generator\Container;

class GeoNamesCountryList
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function fetchGeoNamesCountryList()
    {
        return $this->container->getProvider()->getGeoNamesProvider()->getCountryInfo();
    }
}
