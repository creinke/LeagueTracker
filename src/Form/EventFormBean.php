<?php
namespace App\Form;

use App\Entity\EventDE;

class EventFormBean {
    private EventDE $event;

    public function getEvent(): EventDE {
        return $this->event;
    }

    public function setEvent(EventDE $event): void {
        $this->event = $event;
    }
}