<?php

namespace JsonAst\Helper;

class NumberHelper
{
    public static function isDigit1to9($char)
    {
        return ($char >= '1' && $char <= '9');
    }

    public static function isExp($char)
    {
        return ($char === 'e' || $char === 'E');
    }

    public static function isDigit($char)
    {
        return ($char >= '0' && $char <= '9');
    }

    public static function isHex($char)
    {
        return (
            self::isDigit($char)
            || ($char >= 'a' && $char <= 'f')
            || ($char >= 'A' && $char <= 'F')
        );
    }
}
