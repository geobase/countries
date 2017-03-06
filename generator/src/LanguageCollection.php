<?php

namespace Smart\Geo\Generator;

class LanguageCollection
{
    /**
     * @var array
     */
    private $languages = [
        'en',
        'fr',
        'de'
    ];

    /**
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param array $languages
     * @return $this
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
        return $this;
    }
}
