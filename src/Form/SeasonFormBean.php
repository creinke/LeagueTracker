<?php
namespace App\Form;

use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class SeasonFormBean {
    public function __construct() {
        $this->events = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    private DateTime $enddate;
    private ArrayCollection$events;
    private string $name;
    private ArrayCollection $sessions;
    private DateTime $startdate;

    public function getEnddate(): DateTime {
        return $this->enddate;
    }

    public function getEvents(): ArrayCollection {
        return $this->events;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSessions(): ArrayCollection {
        return $this->sessions;
    }
    
    public function getStartdate(): DateTime {
        return $this->startdate;
    }

    public function setEnddate(DateTime $enddate): void {
        $this->enddate = $enddate;
    }

    public function setEvents(ArrayCollection $events): void {
        $this->events = $events;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setSessions(ArrayCollection $sessions): void {
        $this->sessions = $sessions;
    }
    
    public function setStartdate(DateTime $startdate): void {
        $this->startdate = $startdate;
    }
}