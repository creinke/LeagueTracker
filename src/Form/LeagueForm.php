<?php

namespace App\Form;

use App\Entity\LeagueDE;

class LeagueForm {
	private LeagueDE $league;

	public function getEvent(): LeagueDE {
		return $this->league;
	}

	public function setEvent(LeagueDE $event): void {
		$this->league = $event;
	}
}