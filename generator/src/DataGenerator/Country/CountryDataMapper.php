<?php

namespace Smart\Geo\Generator\DataGenerator\Country;

use Smart\Geo\Generator\Container;

class CountryDataMapper
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
     * @param array $countries
     * @return array
     */
    public function mapGeneratedCountryToArray(array $countries)
    {
        $languages = $this->container->getLanguageCollection()->getLanguages();

        $retval = [];
        foreach ($countries as $country) {
            $names = [];
            foreach ($languages as $language) {
                if (isset($country['names'][$language])) {
                    $names[$language] = $country['names'][$language];
                }
            }

            $item = [
                'shortCode' => $country['countryCode'],
                'code' => $country['isoAlpha3'],
                'names' => $names,
                'currency' => isset($country['currencyCode']) ? $country['currencyCode'] : null,
                'continent' => isset($country['continent']) ? $country['continent'] : null,
                'population' => isset($country['population']) ? $country['population'] : null,
                'area' => isset($country['areaInSqKm']) ? $country['areaInSqKm'] : null,
                'capital' => isset($country['capital']) ? $country['capital'] : null,
                'latitude' => isset($country['latitude']) ? (string)$country['latitude'] : null,
                'longitude' => isset($country['longitude']) ? (string)$country['longitude'] : null,
                'north' => isset($country['north']) ? (string)$country['north'] : null,
                'east' => isset($country['east']) ? (string)$country['east'] : null,
                'south' => isset($country['south']) ? (string)$country['south'] : null,
                'west' => isset($country['west']) ? (string)$country['west'] : null,
                'timezone' => isset($country['timezone']) ? $country['timezone'] : null,
                'polygon' => isset($country['polygon']) ? $country['polygon'] : null,
            ];

            $retval[] = $item;
        }

        return $retval;
    }
}
