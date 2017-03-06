<?php

namespace Smart\Geo\Generator\Provider\Wikipedia;

/**
 * Jungle Wikipedia Syntax Parser
 *
 * @link https://github.com/donwilson/PHP-Wikipedia-Syntax-Parser
 *
 * @author Don Wilson <donwilson@gmail.com>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @package Jungle
 * @subpackage Wikipedia Syntax Parser
 *
 * @todo Add option for extracting only specific types of information - Jungle_WikiSyntax_Parser::set_extract
 * (array('pageAttributes', 'majorSections', 'externalLinks'))
 * @todo Toggle debug mode - Jungle_WikiSyntax_Parser::show_debug() and Jungle_WikiSyntax_Parser::hide_debug()
 */
class WikipediaParser
{
    private $text = "";
    private $title = "";
    private $cargo = array();

    /**
     * @param string $text Raw Wikipedia syntax (found via a Page's Edit textarea or from the Wikiepdia Data Dump
     * page->revision->text)
     * @param string $title
     */
    public function __construct($text, $title = "")
    {
        $this->text = $text;
        $this->title = $title;

        $this->cargo['pageAttributes'] = array();
    }

    /**
     * @return array Contents of $this->cargo
     */
    public function parse()
    {
        $this->initialClean();

        $this->pageAttributes();
        $this->majorSections();
        $this->personData();
        $this->externalLinks();
        $this->categories();
        $this->infoboxes();
        $this->foreignWikis();
        //$this->citations();

        $this->packCargo();

        return $this->cargo;
    }

    private function pageAttributes()
    {
        $pageAttributes = array(
            'type' => false,
            'child_of' => ""
        );

        if (
            $pageAttributes['type'] === false &&
            (preg_match(
                "#^(Template|Wikipedia|Portal|User|File|MediaWiki|Template|Category|Book|Help|Course|Institution)".
                "\\:#si",
                $this->title,
                $match
            ))
        ) {
            // special wikipedia pages
            $pageAttributes['type'] = strtolower($match['1']);
        }

        if (
            $pageAttributes['type'] === false &&
            (preg_match(
                "#\\#REDIRECT(?:\\s*?)\\[\\[([^\\]]+?)\\]\\]#si",
                $this->text,
                $match
            ))
        ) {
            // redirection
            $pageAttributes['type'] = "redirect";
            $pageAttributes['child_of'] = $match[1];
        }

        if (
            $pageAttributes['type'] === false &&
            (preg_match(
                "#\\{\\/{(disambig|hndis|disamb|hospitaldis|geodis|disambiguation|mountainindex|roadindex|school ".
                "disambiguation|hospital disambiguation|mathdab|math disambiguation)((\\|[^\\}]+?)?)\\}\\}#si",
                $this->text,
                $match
            ))
        ) {
            // disambiguation file
            $pageAttributes['type'] = "disambiguation";
            $pageAttributes['disambiguation_key'] = $match[1];

            if (!empty($match[2])) {
                $pageAttributes['disambiguation_value'] = $match[2];
            }

            return;
        }

        if ($pageAttributes['type'] === false) {
            // just a normal page
            $pageAttributes['type'] = "main";
        }

        $this->cargo['pageAttributes'] = $pageAttributes;
    }

    /**
     * @todo Some pages use {{MLB infobox ... }} instead of {{Infobox MLB ... }} [ex: http://en.wikipedia.org/wiki/
     * Texas_Rangers_(baseball)]. I think {{MLB ...}} is an actual Wikipedia template and not distinctly an Infobox
     * template
     */
    private function infoboxes()
    {
        $infobox = array();

        preg_match_all(
            "#\\{\\{(?:\\s*?)Infobox(?:\\s*?)(.+?)" . PHP_EOL . "(.+?)" . PHP_EOL . "\\}\\}" . PHP_EOL . "#si",
            $this->text,
            $matches
        );

        if (!empty($matches[0])) {
            foreach ($matches[0] as $key => $nil) {
                $infobox_values = array();
                $infobox_tmp = $matches[2][$key];

                $infobox_tmp = explode("\n", $infobox_tmp);
                $last_line_key = "";

                foreach ($infobox_tmp as $line) {
                    $line = trim($line);

                    if (preg_match("#^\\|#si", $line)) {
                        $line = preg_replace("#^\\|(\\s*?)#si", "", $line);
                        $bits = explode("=", $line, 2);

                        $lineKey = trim(preg_replace("#[^A-Za-z0-9]#si", "_", strtolower($bits[0])), "_");
                        $lineValue = isset($bits[1]) ? trim($bits[1]) : '';

                        $infobox_values[$lineKey] = array();
                    } else {
                        if (!isset($infobox_values[$last_line_key])) {
                            continue;   // this is likely an editor message of some sort
                        }

                        $lineKey = $last_line_key;
                        $lineValue = $line;
                    }

                    $lineValues = preg_split(
                        "#<(?:\\s*?)br(?:\\s*?)(/?)(?:\\s*?)>#si",
                        $lineValue,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );

                    $infobox_values[$lineKey] = array_merge($infobox_values[$lineKey], $lineValues);

                    $last_line_key = $lineKey;
                }

                $infobox[] = array(
                    'type' => $matches[1][$key],
                    'contents' => $infobox_values
                );
            }
        }

        $this->cargo['infoboxes'] = $infobox;
    }

    private function majorSections()
    {
        $majorSections = array();

        $majorSections_splits = preg_split(
            "#(?:\\s{1,})==(?:\\s*?)([^=]+?)(?:\\s*?)==#si",
            $this->text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $this->cargo['intro_section'] = array_shift($majorSections_splits);

        if (!empty($majorSections_splits)) {
            foreach ($majorSections_splits as $key => $text) {
                if (($key % 2) == 1) {
                    $majorSections[] = array(
                        'title' => $majorSections_splits[($key - 1)]
                    , 'text' => $majorSections_splits[$key]
                    );
                }
            }
        }

        $this->cargo['majorSections'] = $majorSections;
    }

    private function personData()
    {
        $personData = array();

        preg_match(
            "#\\{\\{(?:\\s*?)Persondata" . PHP_EOL . "(.+?)" . PHP_EOL . "\\}\\}" . PHP_EOL . "#si",
            $this->text,
            $match
        );

        if (!empty($match[0])) {
            $personDataTmp = $match[1];

            $personDataTmp = explode(PHP_EOL, $personDataTmp);

            foreach ($personDataTmp as $line) {
                $line = preg_replace("#^\|(\s*?)#si", "", $line);
                $bits = explode("=", $line, 2);
                $lineKey = trim(preg_replace("#[^A-Za-z0-9]#si", "_", strtolower($bits[0])), "_");
                $lineValue = trim($bits[1]);

                $personData[$lineKey] = $lineValue;
            }
        }

        $this->cargo['personData'] = $personData;
    }

    private function externalLinks()
    {
        $externalLinks = array();

        preg_match(
            "#" . PHP_EOL . "==(?:\\s*?)External(?:\\s{1,}?)links(?:\\s*?)==(.+?)" . PHP_EOL . PHP_EOL . "#si",
            $this->text,
            $matches
        );

        if (!empty($matches[1])) {
            // \n is better than PHP_EOL as the line separators in the wiki syntax might not be the same as the
            // OS-specific PHP_EOL [maybe there's a better way of doing this?]
            $lines = explode("\n", $matches[1]);

            $this->cargo['debug_el'] = $lines;   // temporary

            if (!empty($lines)) {
                foreach ($lines as $line) {
                    if (preg_match("#^\*(.+?)$#si", trim($line), $match)) {
                        $lineValue = trim($match[1]);

                        switch (true) {
                            case preg_match(
                                "#\\[(?:\\s*?)http(s?)\\:\\/\\/([^\\s]+?)(?:\\s+?)([^\\]]+?)(?:\\s*?)]#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => 'url',
                                    'attributes' => array(
                                        'url' => "http" . $lmatch[1] . "://" . $lmatch[2],
                                        'text' => trim($lmatch[3])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\[(?:\\s*?)([^\\s]+?)(\\s+?)http(s?)\\:\\/\\/([^\\s]+?)(?:\\s*?)]#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => 'url',
                                    'attributes' => array(
                                        'url' => "http" . $lmatch[2] . "://" . $lmatch[3],
                                        'text' => trim($lmatch[1])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)official(?:\\s+?)([^\\|]+?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "official",
                                    'attributes' => array(
                                        'type' => trim($lmatch[1]),
                                        'value' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)(myspace|facebook|twitter)(?:\\s*?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => strtolower(trim($lmatch[1])),
                                    'attributes' => array(
                                        'username' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)reverbnation(?:\\s*?)\\|([^\\|]+?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "reverbnation",
                                    'attributes' => array(
                                        'username' => $lmatch[1],
                                        'name' => $lmatch[2]
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)spotify(?:\\s*?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $bits = explode("|", $lmatch[1]);

                                $spotify_attrs = array(
                                    'code' => $bits[0]
                                );

                                if (count($bits) > 1) {
                                    $spotify_attrs['name'] = $bits[1];
                                }

                                if (count($bits) > 2) {
                                    $spotify_attrs['type'] = $bits[2];
                                }

                                $externalLinks[] = array(
                                    'type' => "spotify"
                                , 'attributes' => $spotify_attrs
                                );

                                unset($bits, $spotify_attrs);
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)dmoz(?:\\s*?)\\|([^\\|]+?)\\|([^\\|]+?)\\|(?:\\s*?)user".
                                "(?:\\s*?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "dmoz",
                                    'attributes' => array(
                                        'username' => trim($lmatch[1]),
                                        'name' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)dmoz(?:\\s*?)\\|([^\\|]+?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "dmoz",
                                    'attributes' => array(
                                        'category' => trim($lmatch[1]),
                                        'title' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)imdb(?:\\s*?)([^\\|]+?)\\|(?:\\s*?)([0-9]+?)(?:\\s*?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "imdb",
                                    'attributes' => array(
                                        'type' => trim($lmatch[1]),
                                        'id' => ltrim(trim($lmatch[2]), "0")
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)mtv(?:\\s*?)([^\\|]+?)\\|(?:\\s*?)([A-Za-z0-9\\-\\_]+?)".
                                "(?:\\s*?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => "mtv",
                                    'attributes' => array(
                                        'type' => trim($lmatch[1]),
                                        'id' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)amg(?:\\s*?)([^\\|]+?)\\|(?:\\s*?)([0-9]+?)(?:\\s*?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $externalLinks[] = array(
                                    'type' => 'amg',
                                    'attributes' => array(
                                        'type' => trim($lmatch[1]),
                                        'id' => trim($lmatch[2])
                                    )
                                );
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)allmusic(?:\\s*?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $tmp = array(
                                    'type' => 'allmusic',
                                    'attributes' => array()
                                );

                                $tmpAttrs = explode("|", $lmatch[1]);

                                if (!empty($tmpAttrs)) {
                                    $tmp['attributes'] = array();

                                    foreach ($tmpAttrs as $tmpValueRaw) {
                                        $tmpValue = explode("=", $tmpValueRaw, 2);

                                        $tmp['attributes'][trim($tmpValue[0])] = trim($tmpValue[1]);
                                    }

                                    unset($tmpValueRaw, $tmpValue);
                                }

                                $externalLinks[] = $tmp;

                                unset($tmp, $tmpAttrs);
                                break;
                            case preg_match(
                                "#\\{\\{(?:\\s*?)(imdb|discogs|musicbrainz)(?:\\s*?)".
                                "([^\\|]+?)\\|([^\\}\\}]+?)\\}\\}#si",
                                $lineValue,
                                $lmatch
                            ):
                                $tmp = array(
                                    'type' => strtolower(trim($lmatch[1])),
                                    'attributes' => array(
                                        'type' => strtolower(trim($lmatch[2]))
                                    )
                                );

                                $tmpAttrs = explode("|", $lmatch[3]);

                                if (!empty($tmpAttrs)) {
                                    foreach ($tmpAttrs as $tmpValueRaw) {
                                        $tmpValue = explode("=", $tmpValueRaw, 2);

                                        $tmp['attributes'][trim($tmpValue[0])] = trim($tmpValue[1]);
                                    }

                                    unset($tmpValueRaw, $tmpValue);
                                }

                                $externalLinks[] = $tmp;

                                unset($tmp, $tmpAttrs);
                                break;
                            default:
                                $externalLinks[] = array(
                                    'type' => 'raw'
                                , 'text' => $lineValue
                                );
                            // ............... eventually ignore any malformed text ............... //
                        }
                    }
                }
            }
        }

        $this->cargo['externalLinks'] = $externalLinks;
    }

    private function categories()
    {
        $categories = array();

        preg_match_all("#\\[\\[(?:\\s*?)Category\\:([^\\]\\]]+?)\\]\\]#si", $this->text, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[1] as $nil => $mvalue) {
                $categories[] = trim($mvalue);
            }
        }

        $this->cargo['categories'] = $categories;
    }

    private function foreignWikis()
    {
        $foreignWikis = array();

        preg_match_all("#\\[\\[([a-z]{2}|simple):([^\\]]+?)\\]\\]#si", $this->text, $matches);

        if (!empty($matches[0])) {
            foreach ($matches[0] as $mkey => $nil) {
                $foreignWikis[$matches[1][$mkey]] = trim($matches[2][$mkey]);
            }
        }

        $this->cargo['foreign_wiki'] = $foreignWikis;
    }

    /**
     * @todo http://en.wikipedia.org/wiki/Wikipedia:Citation_templates
     */
    private function citations()
    {
        $citations = array();

        // ...

        $this->cargo['citations'] = $citations;
    }

    private function initialClean()
    {
        // strip out the crap we don't need
        $this->cargo['title'] = trim($this->title);

        // get rid of unneeded Editor comments
        $this->text = preg_replace("#<!\\-\\-(.+?)\\-\\->#si", "", $this->text);

        // the wrapped PHP_EOL adds for easier regex matches
        $this->text = PHP_EOL . PHP_EOL . $this->text . PHP_EOL . PHP_EOL;
    }

    private function packCargo()
    {
        //$this->cargo['raw_text'] = $this->text;
    }
}
