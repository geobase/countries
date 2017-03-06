<?php

namespace Smart\Geo\Generator\DataGenerator\Country;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\DataGenerator\Country\GeoNamesCountry\GeoNamesCountryParser;
use Smart\Geo\Generator\DataGenerator\Country\GeoNamesCountryList\GeoNamesCountryList;
use Smart\Geo\Generator\DataGenerator\Country\OpenStreetMapCountry\OpenStreetMapCountryParser;

class CountryDataGenerator
{
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
    public function genereteAllCountries()
    {
        $countryList = (new GeoNamesCountryList($this->container))->fetchGeoNamesCountryList();

        $geoNamesCountryParser = new GeoNamesCountryParser($this->container);
        foreach ($countryList as $key => $item) {
            if ($country = $geoNamesCountryParser->parseCountry($item)) {
                $countryList[$key] = array_merge((array)$item, $country);
            }
        }

        $openStreetMapCountryParser = new OpenStreetMapCountryParser($this->container);
        foreach ($countryList as $key => $item) {
            if ($country = $openStreetMapCountryParser->parseCountry($item)) {
                $countryList[$key] = array_merge((array)$item, $country);
            }
        }

        return $countryList;
    }
}
