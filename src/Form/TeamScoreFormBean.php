<?php

namespace App\Form;

use App\Entity\ScoreDE;

/**
 * TeamScoreFormBean
 */
class TeamScoreFormBean {
    /**
     * public contructor
     */
    public function __construct(string $teamname, string $teamscore) {
        $this->teamname = $teamname;
        $this->teamscore = $teamscore;
    }

    private $teamname;
    private $teamscore;
    
    /**
     * @return NULL[]|number[]
     */
    public function getFirstnine() {
        $nine = [];
        $strokes = ScoreDE::unpack($this->teamscore);
        
        for ($i = 0; $i < 9; $i++) {
            if ($strokes[$i] == 15) {
                $nine[] = null;
            } else {
                $nine[] = $strokes[$i];
            }
        }
        return $nine;
    }
    
    /**
     * @return NULL[]|number[]
     */
    public function getSecondnine() {
        $nine = [];
        $strokes = ScoreDE::unpack($this->teamscore);
        
        for ($i = 9; $i < 18; $i++) {
            if ($strokes[$i] == 15) {
                $nine[] = null;
            } else {
                $nine[] = $strokes[$i];
            }
        }
        return $nine;
    }
    
    /**
     * @return string
     */
    public function getTeamname() {
        return $this->teamname;
    }
    
    /**
     * @return int[]
     */
    public function getTeamscore() {
        return $this->teamscore;
    }
    
    /**
     * @param int[] $strokes
     */
    public function setFirstnine($strokes) {
        $a = ScoreDE::unpack($this->teamscore);
        
        for ($i = 0; $i < 9; $i++) {
            $a[$i] = $strokes[$i];
        }
        $this->teamscore = ScoreDE::packIntArray($a);
    }
    
    /**
     * @param int[] $strokes
     */
    public function setSecondnine($strokes) {
        $a = ScoreDE::unpack($this->teamscore);
        
        for ($i = 0; $i < 9; $i++) {
            $a[9 + $i] = $strokes[$i];
        }
        $this->teamscore = ScoreDE::packIntArray($a);
    }
}