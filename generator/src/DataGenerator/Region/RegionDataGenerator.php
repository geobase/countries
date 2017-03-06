<?php

namespace Smart\Geo\Generator\DataGenerator\Region;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\DataGenerator\Region\OpenStreetMapRegion\OpenStreetMapRegionParser;
use Smart\Geo\Generator\DataGenerator\Region\WikipediaRegion\WikipediaRegionParser;
use Smart\Geo\Generator\DataGenerator\Region\WikipediaRegionList\WikipediaRegionList;
use Exception;

class RegionDataGenerator
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
    public function genereteAllRegion()
    {
        $regions = [];
        $regionList = (new WikipediaRegionList($this->container))->createWikipediaRegionList();
        $openStreetMapRegionParser = new OpenStreetMapRegionParser($this->container);
        foreach ($regionList as $region) {
            try {
                $openStreetMapRegionData = $openStreetMapRegionParser->parseRegion($region);
                $regions[] = array_merge(
                    $region,
                    $openStreetMapRegionData
                );
            } catch (Exception $e) {
                echo "Region {$region['name']} was not found and has not been included" . PHP_EOL;
            }
        }
        return $regions;
    }
}
