<?php

namespace App\Utility;

trait EnumTrait {
    public static function values(): array {
        return self::$enum;
    }

    public static function toOrdinal($name) : int {
        return array_search($name, self::$enum);
    }

    public static function toString($ordinal) : string {
        return self::$enum[$ordinal];
    }
}