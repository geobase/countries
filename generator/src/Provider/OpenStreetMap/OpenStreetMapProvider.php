<?php

namespace Smart\Geo\Generator\Provider\OpenStreetMap;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Http;
use Smart\Geo\Generator\Provider\XmlParser;

class OpenStreetMapProvider
{
    const SEARCH_ADDRESS_URL = 'http://nominatim.openstreetmap.org/search?q=%s&format=json&polygon=1&addressdetails=1&limit=10&accept-language=%s';
    const RELATION_URL = 'http://www.openstreetmap.org/api/0.6/relation/%s';

    /**
     * @var array
     */
    private $overrideSearch = [
        'Washington, D.C.' => 'District of Columbia'
    ];

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->http = new Http($container);
    }

    /**
     * @param string $address
     * @param string $language
     * @return array
     */
    public function searchAddress($address, $language = 'en')
    {
        foreach ($this->overrideSearch as $match => $value) {
            $address = str_replace($match, $value, $address);
        }
        $url = sprintf(self::SEARCH_ADDRESS_URL, urlencode($address), $language);
        return json_decode($this->http->get($url), true);
    }

    /**
     * @param string $relationId
     * @param string $language
     * @return object
     */
    public function fetchRelation($relationId, $language = 'en')
    {
        $url = sprintf(self::RELATION_URL, urlencode($relationId), $language);
        return (new XmlParser)->parseXml($this->http->get($url));
    }
}
