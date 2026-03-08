<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class EventFormatType {
    use EnumTrait;

    const MATCH_PLAY = "MATCH PLAY";
    const BETTER_BALL = "BETTER BALL";
    const SHAMBLE = "SHAMBLE";
    const SCRAMBLE = "SCRAMBLE";
    const LOW_TEAM_NET = "LOW TEAM NET";
    const STROKE_PLAY = "STROKE PLAY";
    
    private static array $enum = array( 1 => "MATCH PLAY", 2 => "BETTER BALL", 3 => "SHAMBLE", 4 => "SCRAMBLE", 5 => "LOW TEAM NET", 6 => "STROKE PLAY");

    public static function isBetterBall(int $value): bool {
        return $value == 2;
    }
    
    public static function isLowTeamNet(int $value): bool {
        return $value == 5;
    }
    
    public static function isMatchPlay(int $value): bool {
        return $value == 1;
    }
    
    public static function isScramble(int $value): bool {
        return $value == 4;
    }
    
    public static function isShamble(int $value): bool {
        return $value == 3;
    }
    
    public static function isStrokePlay(int $value): bool {
        return $value == 6;
    }

	public static function singlesEventFormats() : array {
		return [1 => "MATCH PLAY", 6 => "STROKE PLAY"];
	}

	public static function teamEventFormats() : array {
		return [2 => "BETTER BALL", 3 => "SHAMBLE", 4 => "SCRAMBLE", 5 => "LOW TEAM NET"];
	}
}