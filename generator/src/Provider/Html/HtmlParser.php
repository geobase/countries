<?php

namespace Smart\Geo\Generator\Provider\Html;

use DOMDocument;
use DOMXpath;
use DOMElement;
use DOMNodeList;

class HtmlParser
{
    /**
     * @var string
     */
    private $html;

    /**
     * @var DOMDocument
     */
    private $doc;

    /**
     * @param mixed $html
     */
    public function __construct($html)
    {
        if ($html instanceof DOMDocument || $html instanceof DOMElement) {
            $this->doc = $html;
        } else {
            $this->html = $html;
        }
    }

    /**
     * @param mixed $doc
     * @param string $query
     * @return DOMNodeList|null
     */
    public function find($doc, $query = null)
    {
        if (null === $query) {
            $query = $doc;
            $doc = $this->getDoc();
        }

        if ($doc instanceof DOMElement) {
            $new = new DomDocument;
            $new->appendChild($new->importNode($doc, true));
            $doc = $new;
        }

        $xpath = new DOMXpath($doc);
        $elements = $xpath->query($query);
        return $elements;
    }

    /**
     * @return DOMDocument
     */
    public function getDoc()
    {
        if (null === $this->doc) {
            $this->doc = new DOMDocument();
            $this->doc->loadHTML($this->html);
        }
        return $this->doc;
    }
}
