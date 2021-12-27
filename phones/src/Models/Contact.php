<?php

namespace App\Models;

class Contact
{
    /** O ID é o numero do telefone completo - apenas números */
    private $id;
    private $ddd;
    private $prefix;
    private $sufix;
    private $resident;
    private $publisher;
    /** Ligar em: 1 para domingo, 7 para sábado */
    private $dayOfWeek;
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function generateId()
    {
        $this->id = $this->ddd . $this->prefix . $this->sufix;

        return $this;
    }

    public function getDDD()
    {
        return $this->ddd;
    }

    public function setDDD(int $ddd)
    {
        $this->ddd = $ddd;

        return $this;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function setPrefix(int $prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getSufix()
    {
        return $this->sufix;
    }

    public function setSufix(int $sufix)
    {
        $this->sufix = $sufix;

        return $this;
    }

    public function getResident()
    {
        return $this->resident;
    }

    public function setResident(int $resident)
    {
        $this->resident = $resident;

        return $this;
    }

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function setPublisher(int $publisher)
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getDayOfWeek($isFormated = false)
    {
        if (empty($this->dayOfWeek)) {
            return null;
        }
        $dayText = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
        return $isFormated ? $dayText[$this->dayOfWeek - 1] : $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek)
    {
        if ($dayOfWeek < 1) {
            $dayOfWeek = 1;
        }

        if ($dayOfWeek > 7) {
            $dayOfWeek = 7;
        }

        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function getUpdatedAt()
    {
        if (empty($this->updatedAt)) {
            return null;
        }
        
        return new \DateTime($this->updatedAt);
    }
}
