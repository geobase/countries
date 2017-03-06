<?php

namespace Smart\Geo\Generator\Provider;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\GeoNames\GeoNamesProvider;

class ProviderContainer
{
    /**
     * @var array
     */
    private $container = [];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->parent = $container;
    }

    /**
     * @return HttpInterface
     */
    public function getHttp()
    {
        if (!isset($this->container['http'])) {
            $this->container['http'] = new Http($this->parent);
        }
        return $this->container['http'];
    }

    /**
     * @param HttpInterface $http
     * @return $this
     */
    public function setHttp(HttpInterface $http)
    {
        $this->container['http'] = $http;
        return $this;
    }

    /**
     * @return GeoNamesProvider
     */
    public function getGeoNamesProvider()
    {
        if (!isset($this->container['geoNamesProvider'])) {
            $this->container['geoNamesProvider'] = new GeoNamesProvider($this->parent);
        }
        return $this->container['geoNamesProvider'];
    }

    /**
     * @param GeoNamesProvider $geoNamesProvider
     * @return $this
     */
    public function setGeoNamesProvider(GeoNamesProvider $geoNamesProvider)
    {
        $this->container['geoNamesProvider'] = $geoNamesProvider;
        return $this;
    }
}
