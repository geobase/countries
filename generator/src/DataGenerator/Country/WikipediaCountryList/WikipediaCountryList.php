<?php

namespace Smart\Geo\Generator\DataGenerator\Country\WikipediaCountryList;

use GuzzleHttp\Client;
use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\HtmlParser;
use DOMElement;
use Symfony\Component\Console\Output\OutputInterface;

class WikipediaCountryList
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
    public function createWikipediaCountryList()
    {
        $listParser = new WikipediaCountryListParser($this->container);
        $countryList = $listParser->parseCountryList();
        return $countryList;
    }
}
