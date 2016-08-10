<?php

namespace Cascade;

class Util
{

    /**
     * Convert to lowerCamelCase
     *
     * @param string $propertyName
     * @return string $camelCasedName
     *
     * Borrowed from the `denormalize` method of Symfony's CamelCaseToSnakeCaseNameConverter
     */
    public static function camelize($propertyName)
    {
        $camelCasedName = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
        }, $propertyName);
        return lcfirst($camelCasedName);
    }
}
