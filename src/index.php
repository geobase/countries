<?php

namespace GeoBase\CountriesData;

class CountriesData {
    public static function getPath() {
        return realpath(__DIR__ . '/..');
    }
    public static function getCountriesPath() {
        return realpath(__DIR__ . '/../countries');
    }
    public static function getRegionsPath() {
        return realpath(__DIR__ . '/../regions');
    }
}
