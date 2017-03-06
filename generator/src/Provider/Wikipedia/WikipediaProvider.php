<?php

namespace Smart\Geo\Generator\Provider\Wikipedia;

use Smart\Geo\Generator\Container;
use Smart\Geo\Generator\Provider\Http;
use Smart\Geo\Generator\Provider\XmlParser;
use Smart\Geo\Generator\Provider\HttpInterface;

class WikipediaProvider
{
    const SEARCH_URL = 'http://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=%s&srprop=&format=xml&continue';

    /**
     * @var HttpInterface
     */
    private $http;

    /**
     * @var XmlParser
     */
    private $xmlParser;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->http = new Http($container);
        $this->xmlParser = new XmlParser();
    }

    /**
     * @param string $url
     * @return string
     */
    public function getRawResponse($url)
    {
        return $this->http->get($url);
    }

    /**
     * @param string $url
     * @return object
     */
    public function getRawContent($url)
    {
        return $this->xmlParser->parseXml($this->http->get($url));
    }

    /**
     * @param string $url
     * @return string
     */
    public function getRawRevision($url)
    {
        $content = $this->xmlParser->parseXml($this->http->get($url));
        return $content->api[0]->query[0]->pages[0]->page[0]->revisions[0]->rev[0]->value;
    }

    /**
     * @param string $url
     * @param string $path
     * @return string
     */
    public function getRevision($url, $path = null)
    {
        $content = $this->xmlParser->parseXml($this->http->get($url));
        $content = $content->api[0]->query[0]->pages[0]->page[0]->revisions[0]->rev[0]->value;
        $wiki = new WikipediaParser($content);
        $content = $wiki->parse();
        if ($path) {
            return $this->getContentPathRecursive($content, explode('.', $path));
        } else {
            return $content;
        }
    }

    /**
     * @param array $content
     * @param array $paths
     * @return string
     */
    private function getContentPathRecursive(array $content, array $paths)
    {
        $currentPath = current($paths);
        $remainingPaths = array_slice($paths, 1);
        if (isset($content[$currentPath])) {
            if (count($remainingPaths)) {
                return $this->getContentPathRecursive($content[$currentPath], $remainingPaths);
            } else {
                return $content[$currentPath];
            }
        }
        return null;
    }

    /**
     * @param string $query
     * @return array|null
     */
    public function getSearchResult($query)
    {
        $url = sprintf(self::SEARCH_URL, urlencode($query));
        $content = $this->xmlParser->parseXml($this->http->get($url));
        $results = $content->api[0]->query[0]->search[0]->p;

        $retval = [];
        foreach ($results as $result) {
            $retval[] = isset($result->attributes->title) ? $result->attributes->title : null;
        }
        return $retval;
    }
}
