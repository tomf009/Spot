<?php

namespace Spot\Type;

class Datetime extends AbstractType implements TypeInterface
{
    /**
     * @var string
     */
    protected static $format = 'Y-m-d h:i:s';

    /**
     * @{inherit}
     */
    public static function cast($value)
    {
        if (is_string($value) || is_numeric($value)) {
            // Create new \DateTime instance from string value
            if (is_numeric($value)) {
                $value = new \DateTime('@' . $value);
            } else if ($value) {
                $value = new \DateTime($value);
            } else {
                $value = null;
            }
        }
        return $value instanceof \DateTime ? $value->format(static::$format) : $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function dump($value)
    {
        $value = static::cast($value);
        return ($value) ? $value->format(static::$format) : $value;
    }
}
