<?php

namespace Smart\Geo\Generator\DataGenerator\Country\GeoNamesCountry;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaParser;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaProvider;

class GeoNamesCountryParser
{
    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param object $country
     * @return array
     */
    public function parseCountry($country)
    {
        return [
            'names' => $this->fetchCountryNames($country->countryCode),
        ];
    }

    /**
     * @param string $countryCode
     * @return array
     */
    public function fetchCountryNames($countryCode)
    {
        $retval = [];
        $languages = $this->container->getLanguageCollection()->getLanguages();
        foreach ($languages as $language) {
            $info = $this->container->getProvider()->getGeoNamesProvider()->getCountryInfo($countryCode, $language);
            $info = current($info);
            $retval[$language] = $info->countryName;
        }
        return $retval;
    }
}
