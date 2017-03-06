<?php

namespace Smart\Geo\Generator\DataGenerator\Country;

use Smart\Geo\Generator\Container;

class CountryDataWriter
{
    const LIST_JSON_FILENAME = 'countries/countries.json';
    const ITEM_JSON_FILENAME = 'countries/countries/%s.json';
    const ITEM_POLYGON_JSON_FILENAME = 'countries/countries/%s/polygon.json';

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
     */
    public function writeCountryData(array $countries)
    {
        $listFile = $this->container->getConfig()->getStorage() . '/' . self::LIST_JSON_FILENAME;

        $dir = dirname($listFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $itemJsonFile = $this->container->getConfig()->getStorage() . '/' . self::ITEM_JSON_FILENAME;
        $dir = dirname($itemJsonFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if ($files = scandir($dir)) {
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    if (is_file($dir . '/' . $file)) {
                        unlink($dir . '/' . $file);
                    } else {
                        foreach (scandir($dir . '/' . $file) as $childFile) {
                            if ($childFile !== '.' && $childFile !== '..') {
                                unlink($dir . '/' . $file . '/' . $childFile);
                            }
                        }
                        rmdir($dir . '/' . $file);
                    }
                }
            }
        }

        foreach ($countries as $country) {
            $this->writeItemFile($country);
        }

        $this->writeListFile($countries);
    }

    /**
     * @param array $countries
     */
    private function writeListFile(array $countries)
    {
        $listFile = $this->container->getConfig()->getStorage() . '/' . self::LIST_JSON_FILENAME;

        $data = [];
        foreach ($countries as $country) {
            unset($country['polygon']);
            $data[$country['shortCode']] = $country;
        }
        ksort($data);
        $data = array_values($data);
        file_put_contents($listFile, json_encode($data));
    }

    /**
     * @param array $country
     */
    private function writeItemFile(array $country)
    {
        $itemJsonFile = $this->container->getConfig()->getStorage() . '/' . self::ITEM_JSON_FILENAME;
        $itemJsonFile = sprintf($itemJsonFile, $country['shortCode']);

        $itemPoygonJsonFile = $this->container->getConfig()->getStorage() . '/' .
            self::ITEM_POLYGON_JSON_FILENAME;
        $itemPoygonJsonFile = sprintf($itemPoygonJsonFile, $country['shortCode']);
        $dir = dirname($itemPoygonJsonFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $itemCountry = $country;
        unset($itemCountry['polygon']);

        file_put_contents($itemJsonFile, json_encode($itemCountry));

        $polygon = $country['polygon'];

        file_put_contents($itemPoygonJsonFile, json_encode($polygon));
    }
}
