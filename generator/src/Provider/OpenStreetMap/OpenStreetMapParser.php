<?php

namespace Smart\Geo\Generator\Provider\OpenStreetMap;

class OpenStreetMapParser
{
    /**
     * @param object $content
     * @return array
     */
    public function parseNames($content)
    {
        $names = [];
        foreach ($content->osm[0]->relation[0]->tag as $tag) {
            if (stripos($tag->attributes->k, 'name:') === 0) {
                $names[substr($tag->attributes->k, strlen('name:'))] = $tag->attributes->v;
            }
        }
        return $names;
    }

    /**
     * @param object $content
     * @return string
     */
    public function parseTimeZone($content)
    {
        foreach ($content->osm[0]->relation[0]->tag as $tag) {
            if ($tag->attributes->k === 'timezone') {
                return $tag->attributes->v;
            }
        }
        return null;
    }

    /**
     * @param array $content
     * @return array
     */
    public function parsePolygon($content)
    {
        $coordinates = [];
        if (isset($content['polygonpoints'])) {
            foreach ($content['polygonpoints'] as $coordinate) {
                $coordinates[] = [$coordinate[0], $coordinate[1]];
            }
        } else {
            return null;
        }
        return $coordinates;
    }

    /**
     * @param array $content
     * @return array
     */
    public function parseBoundingBox($content)
    {
        $boundingBox = [
            'south' => $content['boundingbox'][0],
            'north' => $content['boundingbox'][1],
            'west' => $content['boundingbox'][2],
            'east' => $content['boundingbox'][3],
        ];
        return $boundingBox;
    }

    /**
     * @param array $content
     * @return string
     */
    public function parseLatitude($content)
    {
        return $content['lat'];
    }

    /**
     * @param array $content
     * @return string
     */
    public function parseLongitude($content)
    {
        return $content['lon'];
    }

    /**
     * @param object $content
     * @return string
     */
    public function parseRegionLongCode($content)
    {
        foreach ($content->osm[0]->relation[0]->tag as $tag) {
            if ($tag->attributes->k === 'ISO3166-2') {
                return $tag->attributes->v;
            }
        }
        return null;
    }

    /**
     * @param object $content
     * @return string
     */
    public function parseContinent($content)
    {
        foreach ($content->osm[0]->relation[0]->tag as $tag) {
            if ($tag->attributes->k === 'is_in:continent') {
                return $tag->attributes->v;
            }
        }
        return null;
    }
}
