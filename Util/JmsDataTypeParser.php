<?php

namespace AC\WebServicesBundle\Util;

/**
 * A utility class for parsing data type declarations as defined by the JMS Serializer.
 */
class JmsDataTypeParser
{
    /**
     * Return true if a given type is considered a "primitive", meaning it's not a
     * container of other types.
     *
     * @param  string  $type
     * @return boolean
     */
    public static function isPrimitive($type)
    {
        return in_array($type, array('boolean', 'integer', 'string', 'double', 'array', 'DateTime'));
    }

    /**
     * Parses data type declarations in the format of "array<V>" or "array<K, V>",
     * in order to return information about the data types included in an array.
     *
     * Data (if found) is returned in the following format:
     *
     *      array(
     *          'key' => null|string,
     *          'value' => string,
     *      );
     *
     * @param  string     $type
     * @return array|null
     */
    public static function getNestedTypeInArray($type)
    {
        //TODO: check for new datetime format
        
        //could be some type of array with <V>, or <K,V>
        $regEx = "/\<([A-Za-z0-9\\\]*)(\,?\s?(.*))?\>/";
        if (preg_match($regEx, $type, $matches)) {
            if (!empty($matches[3])) {
                return array(
                    'key' => $matches[1],
                    'value' => $matches[3]
                );
            }

            return array(
                'key' => null,
                'value' => $matches[1]
            );
        }

        return false;
    }

    public static function getNestedObjectInArray($type)
    {
        if ($nested = self::getNestedTypeInArray($type) && !self::isPrimitive($nested['value'])) {
            return $nested;
        }

        return false;
    }

}
