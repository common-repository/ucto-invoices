<?php

/**
 * Class UctoplusHelpers
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
class UctoplusHelpers
{
    public static $countryCodes = [
        'SK' => 'SVK',
        'CZ' => 'CZE',
        'AT' => 'AUT',
    ];

    public static function getUserCountry($country)
    {
        if (self::$countryCodes[ $country ]) {
            return self::$countryCodes[ $country ];
        } else {
            return $country;
        }
    }
}