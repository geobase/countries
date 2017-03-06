<?php

namespace Smart\Geo\Generator\DataGenerator\Country\WikipediaCountry;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaParser;
use Smart\Geo\Generator\Provider\Wikipedia\WikipediaProvider;

class WikipediaCountryParser
{
    const NAME_URL =
        'http://en.wikipedia.org/w/api.php?action=query&titles=%s&prop=langlinks&lllimit=500&format=xml';

    const INFOBOXES_URL =
        'http://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&format=xml&titles=%s&rvsection=0';

    const ISO_CODES_URL =
        'http://en.wikipedia.org/w/api.php?action=query&prop=revisions&rvprop=content&rvsection=4&format=xml&titles=ISO_3166-1';

    const GEOLOCATION_URL =
        'http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false';

    const WIKIPEDIA_PAGE_URL =
        'http://en.wikipedia.org/wiki/%s';

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
     * @param array $country
     * @return array
     */
    public function parseCountry(array $country)
    {
        $info = $this->parseCountryInfoboxes($country['link']);
        $names = $this->getCountryNames($country['link']);
        $codes = $this->getCountryCodes($country['name'], $names, $country['link'], $info);

        if (empty($names)) {
            trigger_error('Could not find country names for ' . $country['name'], E_USER_ERROR);
        }

        if (!empty($codes)) {
            return [
                'names' => $names,
                'info' => $info,
                'codes' => $codes,
            ];
        }

        return null;
    }

    /**
     * @param string $countryName
     * @return array
     */
    public function parseCountryInfoboxes($countryName)
    {
        $url = sprintf(self::INFOBOXES_URL, $countryName);
        $content = $this->wikipediaProvider->getRawContent($url);

        $info = [];
        // Redirects
        if (preg_match_all(
            "/\\{\\{redirect2?\\|([^\\}]*)\\}\\}/",
            $content->api[0]->query[0]->pages[0]->page[0]->revisions[0]->rev[0]->value,
            $matches
        )) {
            if (isset($matches[1][0])) {
                $info['other_names'] = explode('|', $matches[1][0]);
            }
        }

        $content = (string)$content->api[0]->query[0]->pages[0]->page[0]->revisions[0]->rev[0]->value;
        $wiki = new WikipediaParser($content);
        $content = $wiki->parse();
        if (empty($content['infoboxes']) && !empty($content['intro_section'])) {
            $info = $this->parseRawCountryInfoboxes($content['intro_section']);
        } else {
            $content = current($content['infoboxes'])['contents'];

            if (isset($content['conventional_long_name'])) {
                $info['conventional_long_name'] = current($content['conventional_long_name']);
            }

            if (isset($content['common_name'])) {
                $info['common_name'] = current($content['common_name']);
            }

            if (isset($content['country_code'])) {
                $info['country_code'] = current($content['country_code']);
            }

            if (isset($content['iso3166code'])) {
                $info['iso3166code'] = current($content['iso3166code']);
            }
        }

        if (empty($info)) {
            //trigger_error('Could not find country information for ' . $countryName, E_USER_ERROR);
        }

        return $info;
    }

    /**
     * @param $content
     * @return array
     */
    public function parseRawCountryInfoboxes($content)
    {
        return [];
    }

    /**
     * @param string $countryName
     * @return array
     */
    public function getCountryNames($countryName)
    {
        $url = sprintf(self::NAME_URL, trim($countryName));
        $content = $this->wikipediaProvider->getRawContent($url);

        $names = [];
        $names['en'] = trim((string)$content->api[0]->query[0]->pages[0]->page[0]->attributes->title);

        foreach ($content->api[0]->query[0]->pages[0]->page[0]->langlinks[0]->ll as $name) {
            $language = trim((string)$name->attributes->lang);
            $names[$language] = trim((string)$name->value);
        }
        return $names;
    }

    /**
     * @param string $name
     * @param array $names
     * @param string $link
     * @param array $info
     * @param bool $searchDeeper
     * @return string
     */
    public function getCountryCodes($name, array $names, $link, array $info, $searchDeeper = true)
    {
        $original = [$name, $names, $link, $info];

        $content = $this->wikipediaProvider->getRevision(self::ISO_CODES_URL);

        $wiki = new WikipediaParser(trim($content['majorSections'][0]['text']));
        $content = $wiki->parse()['intro_section'];

        preg_match_all("/\\|-\n\\| .*\n\\| .*\n/", $content, $matches);

        $englishName = $names['en'];
        $names = array_merge($names, [
            $name,
            $link,
            isset($info['conventional_long_name']) ? $info['conventional_long_name'] : null,
            isset($info['common_name']) ? $info['common_name'] : null,
        ]);
        if (isset($info['other_names'])) {
            $names = array_merge($names, $info['other_names']);
        }
        if (stripos($englishName, 'the ') !== false) {
            $names[] = trim(str_ireplace('the ', '', $englishName));
            $parts = preg_split('/the /i', $englishName, 2);
            $names[] = $parts[1] . ', the ' . $parts[0];
            $names[] = $parts[1] . ', ' . $parts[0];
        }

        $isoCode = isset($info['iso3166code']) ? $info['iso3166code'] : null;

        $matches = current($matches);
        $codes = [];
        foreach ($matches as $match) {
            if (null !== $isoCode && stripos($match, "ISO 3166-2:{$isoCode}") !== false) {
                preg_match("/\\[ISO 3166-2:([\\w]{2,2})\\]/", $match, $isoCode);
                if (isset($isoCode[1])) {
                    $codes['iso'] = $isoCode[1];
                }
                preg_match("/<tt>([\\w]{3,3})<\\/tt>/", $match, $countryCode);
                if (isset($countryCode[1])) {
                    $codes['code'] = $countryCode[1];
                }
                return $codes;
            }

            foreach ($names as $name) {
                if (!empty($name) && strpos($match, trim($name)) !== false) {
                    preg_match("/\\[ISO 3166-2:([\\w]{2,2})\\]/", $match, $isoCode);
                    if (isset($isoCode[1])) {
                        $codes['iso'] = $isoCode[1];
                    }
                    preg_match("/<tt>([\\w]{3,3})<\\/tt>/", $match, $countryCode);
                    if (isset($countryCode[1])) {
                        $codes['code'] = $countryCode[1];
                    }
                    return $codes;
                }
            }
        }

        if ($searchDeeper) {
            $isoCode = $this->getIsoCountryCodesFromWikiPage($link);
            if ($isoCode) {
                $original[3]['iso3166code'] = $isoCode;
                return $this->getCountryCodes($original[0], $original[1], $original[2], $original[3], false);
            }
        }

        return null;
    }

    /**
     * @param string $link
     * @return mixed|null
     */
    public function getIsoCountryCodesFromWikiPage($link)
    {
        $url = sprintf(self::WIKIPEDIA_PAGE_URL, urlencode($link));
        $content = $this->wikipediaProvider->getRawResponse($url);

        if (preg_match('/ISO_3166\\-2\\:(\\w){2,2}/', $content, $matches)) {
            return str_replace('ISO_3166-2:', '', $matches[0]);
        }

        return null;
    }
}
