<?php

/**
 * Notas (Observações) do contato
 */

namespace App\Models;

class Note {
    private $id;
    private $contactId;
    private $dateContact;
    private $obs;

    public function getId()
    {
        return $this->id;
    }

    public function getContactId()
    {
        return $this->contactId;
    }

    public function setContactId(string $contactId)
    {
        $this->contactId = $contactId;

        return $this;
    }

    public function getDateContact()
    {
        if (empty($this->dateContact)) {
            return null;
        }
        
        return new \DateTime($this->dateContact);
    }

    public function setDateContact(\DateTime $dateContact)
    {
        $this->dateContact = $dateContact->format('Y-m-d H:i:s');

        return $this;
    }

    public function getObs()
    {
        return $this->obs;
    }

    public function setObs(string $obs)
    {
        $this->obs = $obs;

        return $this;
    }
}