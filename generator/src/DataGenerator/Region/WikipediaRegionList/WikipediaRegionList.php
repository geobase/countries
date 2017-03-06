<?php

namespace Smart\Geo\Generator\DataGenerator\Region\WikipediaRegionList;

use Exception;
use SmartData\SmartData\Region\Type\FederalDistrict;
use SmartData\SmartData\Region\Type\Province;
use SmartData\SmartData\Region\Type\State;
use SmartData\SmartData\Region\Type\Territory;
use Smart\Geo\Generator\Container;

class WikipediaRegionList
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
    public function createWikipediaRegionList()
    {
        $listParser = new WikipediaRegionListParser($this->container);
        $regionList = $listParser->parseRegionList();

        $regions = [];
        $regionParser = new WikipediaRegionListItemParser($this->container);
        foreach ($regionList as $country => $regionTypes) {
            foreach ($regionTypes as $regionType => $regionItems) {
                foreach ($regionItems as $region) {
                    try {
                        $region = $regionParser->parseRegion($region, $regionType, $country);
                    } catch (Exception $e) {
                        echo $e->getMessage() . PHP_EOL;
                        continue;
                    }
                    $region['country'] = strtoupper($country);
                    switch ($regionType) {
                        case 'states':
                            $region['type'] = 'State';
                            break;
                        case 'federal_districts':
                            $region['type'] = 'Federal District';
                            break;
                        case 'territories':
                            $region['type'] = 'Territory';
                            break;
                        case 'provinces':
                            $region['type'] = 'Province';
                            break;
                    }
                    $regions[] = $region;
                }
            }
        }

        return $regions;
    }
}
