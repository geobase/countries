<?php

namespace Smart\Geo\Generator\DataGenerator\Region\WikipediaRegionList;

use Exception;
use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaProvider;

class WikipediaRegionListItemParser
{
    const INFOBOXES_URL =
        'http://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=xml&titles=%s&rvsection=0&continue';
    const FULLCONTENT_URL =
        'http://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=xml&titles=%s&continue';
    const US_STATES_CODES =
        'http://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=xml&titles=List_of_U.S._state_abbreviations&continue';

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
     * @param string $region
     * @param string $type
     * @param string $country
     * @return array
     */
    public function parseRegion($region, $type, $country)
    {
        return $this->parseRegionPage($region, $type, $country);
    }

    /**
     * @param string $region
     * @param string $type
     * @param string $country
     * @return string
     */
    private function getSearchResult($region, $type, $country)
    {
        switch ($country) {
            case 'us':
                $country = 'United States';
                break;
            case 'ca':
                $country = 'Canada';
                break;
        }

        switch ($type) {
            case 'states':
                $type = 'State';
                break;
            case 'federal_districts':
                $type = 'Federal District';
                break;
            case 'provinces':
                $type = 'Province';
                break;
            case 'territories':
                $type = 'Territory';
                break;
        }

        return current($this->wikipediaProvider->getSearchResult("{$region} {$type}, {$country}"));
    }

    /**
     * @param string $region
     * @return string
     */
    private function getUsRegionNameFromCodePage($region)
    {
        $line = $this->getUsRegionLine($region);
        if (preg_match("/^\\|\\[\\[([^\\]]*)\\]\\]/", $line, $matches)) {
            $matches = next($matches);
            $matches = explode('|', $matches);
            foreach ($matches as $match) {
                if (strtolower(trim($match)) !== strtolower(trim($region))) {
                    return trim($match);
                }
            }
        }
        return null;
    }

    /**
     * @param string $region
     * @param string $type
     * @param string $country
     * @param bool $recursive
     * @return array
     */
    public function parseRegionPage($region, $type, $country, $recursive = true)
    {
        $url = sprintf(self::INFOBOXES_URL, $region);
        $content = $this->wikipediaProvider->getRevision($url, 'infoboxes.0.contents');

        if (null === $content) {
            $content = $this->wikipediaProvider->getRevision($url, 'intro_section');
            if (null !== $content) {
                $info = $this->parseIntroSection($content);
                if (!isset($info['name']) && !isset($info['code'])) {
                    unset($info);
                } else {
                    $info['link'] = $region;
                }
            }
        }

        if (!isset($info)) {
            $info = [];
            if (isset($content['name'])) {
                $info['name'] = current($content['name']);
            } elseif (isset($content['common_name'])) {
                $info['name'] = current($content['common_name']);
            } elseif (isset($content['conventional_long_name'])) {
                $info['name'] = current($content['conventional_long_name']);
            }

            if (isset($info['name'])) {
                if (isset($content['postalabbreviation'])) {
                    $info['code'] = $this->parseRegionCode(current($content['postalabbreviation']));
                } elseif (isset($content['country_code'])) {
                    $info['code'] = $this->parseRegionCode(current($content['country_code']));
                }
                $info['link'] = $region;
            } else {
                unset($info);
            }
        }

        if ($recursive && !isset($info)) {
            $search = $this->getSearchResult($region, $type, $country);
            $info = $this->parseRegionPage($search, $type, $country, false);
        }

        if (!isset($info)) {
            if ($country === 'us' && $search = $this->getUsRegionNameFromCodePage($region)) {
                $info = $this->parseRegionPage($search, $type, $country, false);
            } else {
                trigger_error('Not able to get info on ' . $region);
                return null;
            }
        }

        if (!isset($info['code']) && !$info['code'] = $this->getRegionCode($info['name'], $region)) {
            unset($info['code']);
        }

        if (!isset($info['code']) && $country === 'us' && isset($content['isocode'])) {
            $isocode = current($content['isocode']);
            if (strlen($isocode) === 2) {
                $info['code'] = $isocode;
            } else if (strlen($isocode) === 5) {
                $info['code'] = preg_replace("/US\\-([a-zA-Z]*)/i", "$1", $isocode);
            }
        }

        if (!isset($info['code']) && $country === 'us') {
            if (!$info['code'] = $this->parseRegionCode($this->getUsRegionCode($region))) {
                throw new Exception('Not able to get info on ' . $region);
            }
        } elseif (!isset($info['code']) && $country === 'ca') {
            throw new Exception('Not able to get info on ' . $region);
        } elseif (!isset($info['code'])) {
            throw new Exception('Not able to get info on ' . $region);
        }

        return $info;
    }

    /**
     * @param string $code
     * @return string
     */
    private function parseRegionCode($code)
    {
        if (strlen($code) === 2 && ctype_alpha($code)) {
            return $code;
        }
        return null;
    }

    /**
     * @param string $content
     * @return array
     */
    private function parseIntroSection($content)
    {
        $patterns = [
            'name' => "/Name *= *(.*)/i",
            'code' => "/PostalAbbreviation *= *(.*)/i",
        ];
        return $this->getPatternMatches($patterns, $content);
    }

    /**
     * @param string $region
     * @param string $link
     * @return string
     */
    private function getRegionCode($region, $link)
    {
        $patterns = [
            'code' => "/PostalAbbreviation *= *(.*)/i",
        ];

        $url = sprintf(self::FULLCONTENT_URL, $link);
        $retval = $this->getPatternMatches($patterns, $this->wikipediaProvider->getRawRevision($url));

        if (isset($retval['code'])) {
            if (strpos($retval['code'], '<')) {
                $retval['code'] = current(explode('<', $retval['code'], 2));
            } elseif (strpos($retval['code'], ' ')) {
                $retval['code'] = current(explode(' ', $retval['code'], 2));
            }
            return $this->parseRegionCode($retval['code']);
        }
        return null;
    }

    /**
     * @param string $region
     * @return mixed|null
     */
    private function getUsRegionCode($region)
    {
        $line = $this->getUsRegionLine($region);
        if (preg_match("/\\{\\{mono\\|([a-zA-Z]{2,2})\\}\\}/", $line, $matches)) {
            return next($matches);
        }
        return null;
    }

    /**
     * @param string $region
     * @return string
     */
    private function getUsRegionLine($region)
    {
        $content = $this->wikipediaProvider->getRevision(self::US_STATES_CODES, 'majorSections.0.text');
        $lines = preg_split("/\\n *\\|\\-(style=\"[^\"]*\")? *\\n/", $content);
        foreach ($lines as $line) {
            if (
                preg_match("/^\\|\\[\\[$region\\]\\]\\|\\|/", $line) ||
                preg_match("/^\\|\\[\\[$region\\|.*\\]\\]\\|\\|/", $line) ||
                preg_match("/^\\|\\[\\[.*\\|$region\\]\\]\\|\\|/", $line)
            ) {
                return $line;
            }
        }
        return null;
    }

    /**
     * @param array $patterns
     * @param string $content
     * @return array
     */
    private function getPatternMatches(array $patterns, $content)
    {
        $lines = explode(PHP_EOL, $content);
        $retval = [];
        foreach ($lines as $line) {
            foreach ($patterns as $name => $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    if (!isset($retval[$name])) {
                        $retval[$name] = next($matches);
                    }
                }
            }
        }
        return $retval;
    }
}
