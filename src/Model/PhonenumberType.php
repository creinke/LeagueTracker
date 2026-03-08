<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class PhonenumberType {
    use EnumTrait;

    const CELL = "CELL";
    const WORK = "WORK";
    const HOME = "HOME";
    const OTHER = "OTHER";

    private static array $enum = array( 1 => 'CELL', 2 => 'WORK', 3 => 'HOME', 4 => 'OTHER');
}