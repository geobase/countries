<?php

namespace Smart\Geo\Generator\Provider\GeoNames;

use Smart\Geo\Generator\Container;

class GeoNamesProvider
{
    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $country
     * @param string $language
     * @return array|object
     */
    public function getCountryInfo($country = null, $language = 'en')
    {
        if (null === $country) {
            $url = "http://api.geonames.org/countryInfo?lang={$language}&username=smartdata&type=JSON";
        } else {
            $url = "http://api.geonames.org/countryInfo?lang={$language}&country={$country}&username=smartdata&type=JSON";
        }
        $data = $this->container->getProvider()->getHttp()->get($url);
        return json_decode($data)->geonames;
    }
}
