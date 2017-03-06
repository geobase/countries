<?php

namespace Smart\Geo\Generator\DataGenerator\Region;

use Smart\Geo\Generator\Container;

class RegionDataWriter
{
    const LIST_JSON_FILENAME = 'regions/regions.json';
    const ITEM_JSON_FILENAME = 'regions/regions/%s.json';
    const POLYGON_DATA_FILENAME = 'regions/regions/%s/polygon.json';

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function writeAllRegion($regions)
    {
        $output = [];
        foreach ($regions as $region) {
            $output[$region['code']] = $this->mapRegionToArray($region);
        }
        $this->doWrite(array_values($output));
    }

    /**
     * @param array $output
     */
    private function doWrite($output)
    {
        $listFile =
            $this->container->getConfig()->getStorage() . DIRECTORY_SEPARATOR . self::LIST_JSON_FILENAME;
        $this->mkdir($listFile);
        file_put_contents($listFile, json_encode($output));

        foreach ($output as $item) {
            $itemFile =
                $this->container->getConfig()->getStorage() . DIRECTORY_SEPARATOR .
                sprintf(self::ITEM_JSON_FILENAME, $item['code']);
            $this->mkdir($itemFile);
            file_put_contents($itemFile, json_encode($item));
        }
    }

    /**
     * @param string $filename
     */
    private function mkdir($filename)
    {
        $dir = dirname($filename);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    /**
     * @param array $region
     * @return array
     */
    private function mapRegionToArray($region)
    {
        return [
            'names' => $this->mapLanguagesToArray($region),
            'code' => $region['code'],
            'long_code' => $region['long_code'],
            'country' => $region['country'],
            'type' => $region['type'],
            'timezone' => $region['timezone'],
            'latitude' => $region['latitude'],
            'longitude' => $region['longitude'],
            'north' => isset($region['bounding_box']['north']) ? (string)$region['bounding_box']['north'] : null,
            'east' => isset($region['bounding_box']['east']) ? (string)$region['bounding_box']['east'] : null,
            'south' => isset($region['bounding_box']['south']) ? (string)$region['bounding_box']['south'] : null,
            'west' => isset($region['bounding_box']['west']) ? (string)$region['bounding_box']['west'] : null,
        ];
    }

    /**
     * @param array $region
     * @return array
     */
    private function mapLanguagesToArray($region)
    {
        $retval = [];
        foreach ($this->container->getLanguageCollection()->getLanguages() as $language) {
            if (isset($region['names'][$language])) {
                $retval[$language] = $region['names'][$language];
            } else {
                $retval[$language] = $region['name'];
            }
        }
        return $retval;
    }
}
