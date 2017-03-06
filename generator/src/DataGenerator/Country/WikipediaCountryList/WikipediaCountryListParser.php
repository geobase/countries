<?php

namespace Smart\Geo\Generator\DataGenerator\Country\WikipediaCountryList;

use DOMElement;
use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaProvider;
use Smart\Geo\Generator\Provider\Html\HtmlParser;

class WikipediaCountryListParser
{
    const SOURCE_URL = 'http://en.wikipedia.org/w/api.php?action=parse&page=List_of_sovereign_states&format=xml&continue';

    /**
     * @var array
     */
    private $exceptions = ['Other_states'];

    /**
     * @var WikipediaProvider
     */
    private $wikipediaProvider;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->wikipediaProvider = new WikipediaProvider($container);
    }

    /**
     * @return array
     */
    public function parseCountryList()
    {
        $content = $this->wikipediaProvider->getRawContent(self::SOURCE_URL);

        $htmlParser = new HtmlParser($content->api[0]->parse[0]->text[0]->value);
        $list = $htmlParser->find('*/table[1]/tr');

        $countries = [];
        foreach ($list as $row) {
            $countryId = $htmlParser->find($row, '*/span[@id]');
            if ($countryId->length) {
                $countryId = $countryId->item(0);
                /** @var DomElement $countryId */
                if (!in_array($countryId->getAttribute('id'), $this->exceptions)) {
                    $countries[] = $this->parseCountryRow($row);
                }
            }
        }

        return $countries;
    }

    /**
     * @param DOMElement $row
     * @return array
     */
    private function parseCountryRow(DOMElement $row)
    {
        $htmlParser = new HtmlParser($row);
        /** @var DomElement $country */
        $country = $htmlParser->find('td[1]/*/a[1]')->item(0);

        $link = preg_replace("/^\\/wiki\\//", "", trim($country->getAttribute('href')));
        $name = trim($country->nodeValue);

        return [
            'link' => $link,
            'name' => $name,
        ];
    }
}
