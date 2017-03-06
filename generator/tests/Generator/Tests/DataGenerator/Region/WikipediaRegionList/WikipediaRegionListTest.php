<?php

namespace Smart\Geo\Generator\Tests\DataGenerator\Region\WikipediaRegionList;

use Smart\Geo\Generator\Tests\TestCase;
use Smart\Geo\Generator\DataGenerator\Region\WikipediaRegionList\WikipediaRegionList;

class WikipediaRegionListTest extends TestCase
{
    public function setUp()
    {
        $this->emptyCache();
    }

    public function testGetRegionList()
    {
        (new WikipediaRegionList($this->getContainer()))->createWikipediaRegionList();
    }
}
