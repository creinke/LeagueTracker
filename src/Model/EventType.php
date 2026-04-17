<?php
namespace App\Model;

use App\Utility\EnumTrait;

abstract class EventType {
    use EnumTrait;

    const string LEAGUE_MATCH = "LEAGUE MATCH";
    const string POSITION_MATCH = "POSITION MATCH";
    const string PLAYOFF_MATCH = "PLAYOFF MATCH";
    const string TEAM_EVENT = "TEAM EVENT";
    const string SINGLES_MATCH = "SINGLES MATCH";
    
    private static array $enum = array( 1 => "LEAGUE MATCH", 2 => "POSITION MATCH", 3 => "PLAYOFF MATCH", 4 => "TEAM EVENT", 5 => "SINGLES MATCH");

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isPlayoffMatch(int $value): bool {
        return $value == 3;
    }

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isTeamMatch(int $value): bool {
        return $value == 1 || $value == 2 || $value == 3;
    }

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isSinglesMatch(int $value): bool {
        return $value == 5;
    }

	/**
	 * @param int $value
	 *
	 * @return bool
	 */
	public static function isTeamEvent(int $value): bool {
        return $value == 4;
    }
}