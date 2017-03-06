<?php

namespace Smart\Geo\Generator\DataGenerator\Region\WikipediaRegionList;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaProvider;

class WikipediaRegionListParser
{
    /**
     * @var array
     */
    private $countries = [
        'us' => [
            'states' =>
                'http://en.wikipedia.org/w/api.php?action=query&titles=List_of_states_and_territories_of_the_United_States&prop=revisions&rvprop=content&rvsection=1&format=xml&continue',
            'federal_districts' =>
                'http://en.wikipedia.org/w/api.php?action=query&titles=List_of_states_and_territories_of_the_United_States&prop=revisions&rvprop=content&rvsection=2&format=xml&continue',
            'territories' =>
                'http://en.wikipedia.org/w/api.php?action=query&titles=List_of_states_and_territories_of_the_United_States&prop=revisions&rvprop=content&rvsection=3&format=xml&continue',
        ],
        'ca' => [
            'provinces' =>
                'http://en.wikipedia.org/w/api.php?action=query&titles=Provinces_and_territories_of_Canada&prop=revisions&rvprop=content&rvsection=2&format=xml&continue',
            'territories' =>
                'http://en.wikipedia.org/w/api.php?action=query&titles=Provinces_and_territories_of_Canada&prop=revisions&rvprop=content&rvsection=4&format=xml&continue'
        ]
    ];

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
    public function parseRegionList()
    {
        $list = [];
        foreach ($this->countries as $country => $urls) {
            switch ($country) {
                case 'us':
                    $list['us']['states'] = $this->parseUsStates($urls['states']);
                    $list['us']['federal_districts'] = $this->parseUsStates($urls['federal_districts']);
                    $list['us']['territories'] = $this->parseUsStates($urls['territories']);
                    break;
                case 'ca':
                    $list['ca']['provinces'] = $this->parseCaRegions($urls['provinces']);
                    $list['ca']['territories'] = $this->parseCaRegions($urls['territories']);
                    break;
            }
        }
        return $list;
    }

    /**
     * @param string $url
     * @return array
     */
    private function parseUsStates($url)
    {
        /*
         * Example of state:
         *
         * |-
         * !scope="row"|{{flag|Washington}}
         * |WA
         * |[[Olympia, Washington|Olympia]]
         * |[[Seattle]]
         * |November 11, 1889
         * |6,971,406
         * |{{Convert|71298|mi2|km2|sigfig=6|abbr=values|sortable=on}}
         * |{{Convert|66456|mi2|km2|sigfig=5|abbr=values|sortable=on}}
         * |{{Convert|4842|mi2|km2|sigfig=5|abbr=values|sortable=on}}
         * |10
         * |-
         */
        $content = $this->wikipediaProvider->getRevision($url, 'majorSections.0.text');
        $regex = "/!scope=\"row\"\\|[^\\}]*\\{\\{flag\\|([^}]*)\\}\\}/im";
        preg_match_all($regex, $content, $matches);
        if (isset($matches[1])) {
            $matches = $matches[1];
            foreach ($matches as $key => $match) {
                if (stripos($match, '|')) {
                    $parts = explode('|', $match);
                    $matches[$key] = trim(current($parts));
                } else {
                    $matches[$key] = trim($match);
                }
            }
            return $matches;
        }
        trigger_error('Could not find US regions', E_USER_ERROR);
        return null;
    }

    /**
     * @param string $url
     * @return array
     */
    private function parseCaRegions($url)
    {
        /**
         * Example of province
         *
         * |-
         * ! style="text-align: center;" | [[File:Flag of British Columbia.svg|border|30px]]
         * ! style="text-align: center;" | [[File:Arms of British Columbia.svg|30px]]
         * ! style="text-align: left;" | [[British Columbia]]
         * | style="text-align: center;" | BC
         * | [[Victoria, British Columbia|Victoria]]
         * | [[Vancouver]]
         * | {{dts|July 20, 1871}}
         * | style="text-align: right;" | 4,400,057
         * | style="text-align: right;" | 925,186
         * | style="text-align: right;" | 19,549
         * | style="text-align: right;" | 944,735
         * | colpos = "6" rowpos = "4" style="text-align: center;" | English{{ref|a|A}}
         * | colpos = "7" rowpos = "4" style="text-align: center;" | 36
         * | colpos = "7" rowpos = "4" style="text-align: center;" | 6
         * |-
         */

        $content = $this->wikipediaProvider->getRevision($url, 'majorSections.0.text');
        $regex = "/style=\"text-align: left;\" \\| \\[\\[([^\\]]*)\\]\\]/im";
        preg_match_all($regex, $content, $matches);
        if (isset($matches[1])) {
            $matches = $matches[1];
            foreach ($matches as $key => $match) {
                $matches[$key] = trim($match);
            }
            return $matches;
        }
        trigger_error('Could not find CA regions', E_USER_ERROR);
        return null;
    }
}
