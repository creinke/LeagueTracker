<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class EmailType {
    use EnumTrait;

    const string WORK = "WORK";
    const string PERSONAL = "PERSONAL";
    const string OTHER = "OTHER";

    private static array $enum = array( 1 => "WORK", 2 => "PERSONAL", 3 => "OTHER");
}