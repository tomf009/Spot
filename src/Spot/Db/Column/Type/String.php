<?php

namespace Spot\Type;

class String extends AbstractType implements TypeInterface
{
    /**
     * {@inherit}
     */
    public static function cast($value)
    {
        $value = trim($value);
        return ($value === '') ? null : $value;
    }
}
