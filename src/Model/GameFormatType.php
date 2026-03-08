<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class GameFormatType {
    use EnumTrait;

    const SINGLES_MATCH_PLAY = "SINGLES MATCH PLAY";
    const SINGLES_STROKE_PLAY = "SINGLES STROKE PLAY";

    private static $enum = array(1 => "SINGLES MATCH PLAY", 2 => "SINGLES STROKE PLAY");

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isMatchPlay(int $value): bool {
    	return $value == 1;
    }

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isStrokePlay(int $value): bool {
    	return $value == 2;
    }
}