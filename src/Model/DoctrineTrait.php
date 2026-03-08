<?php

namespace App\Model;

use Doctrine\ORM\EntityManager;

trait DoctrineTrait {
    public function getEntityManager() : EntityManager {
        if (empty($this->em)) {
            $init = new Init();
            $base = new Base($init->getParams());
            $this->em = $base->getEm();
        }
        return $this->em;
    }
}