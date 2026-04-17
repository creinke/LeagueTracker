<?php
namespace App\Form;

use App\Entity\SeasonDE;

class SeasonForm {
    private SeasonDE $season;

    public function getSeason(): SeasonDE {
        return $this->season;
    }

    public function setSeason(SeasonDE $season): void {
        $this->season = $season;
    }
}