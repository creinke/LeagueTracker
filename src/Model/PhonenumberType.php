<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class PhonenumberType {
    use EnumTrait;

    const string CELL = "CELL";
    const string WORK = "WORK";
    const string HOME = "HOME";
    const string OTHER = "OTHER";

    private static array $enum = array( 1 => 'CELL', 2 => 'WORK', 3 => 'HOME', 4 => 'OTHER');
}