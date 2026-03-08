<?php
namespace App\Form;

use Doctrine\Common\Collections\ArrayCollection;

class SeasonFormBean {
    public function __construct() {
        $this->events = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    private $enddate;
    private $events;
    private $name;
    private $sessions; 
    private $startdate;

    public function getEnddate() {
        return $this->enddate;
    }

    public function getEvents() {
        return $this->events;
    }

    public function getName() {
        return $this->name;
    }

    public function getSessions() {
        return $this->sessions;
    }
    
    public function getStartdate() {
        return $this->startdate;
    }

    public function setEnddate($enddate) {
        $this->enddate = $enddate;
    }

    public function setEvents($events) {
        $this->events = $events;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function setSessions($sessions) {
        $this->sessions = $sessions;
    }
    
    public function setStartdate($startdate) {
        $this->startdate = $startdate;
    }
}