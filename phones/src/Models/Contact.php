<?php

namespace App\Models;

class Contact
{
    const PERIOD_MORNING = 1;
    const PERIOD_AFTERNOON = 2;
    const PERIOD_NIGHT = 3;

    /** O ID é o numero do telefone completo - apenas números */
    private $id;
    private $ddd;
    private $prefix;
    private $sufix;
    private $resident;
    private $publisher;
    /** Ligar em: 1 para domingo, 7 para sábado */
    private $dayOfWeek;
    /** Ligar em: 1 para manhã, 2 para tarde e 3 para noite */
    private $period;
    private $updatedAt;

    public function getId()
    {
        return $this->id;
    }

    public function generateId()
    {
        $this->id = $this->ddd . $this->prefix . str_pad($this->sufix, 4, '0', STR_PAD_LEFT);
        $this->updatedAt = date('Y-m-d H:i:s');

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

    public function getSufix($isFormated = true)
    {
        return $isFormated ? str_pad($this->sufix, 4, '0', STR_PAD_LEFT) : $this->sufix;
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

    public function setResident(string $resident)
    {
        $this->resident = $resident;

        return $this;
    }

    public function getPublisher()
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher)
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
    
    public function getPeriod($isFormated = false)
    {
        if (empty($this->period)) {
            return null;
        }
        $periodText = ['Manhã', 'Tarde', 'Noite'];
        return $isFormated ? $periodText[$this->period - 1] : $this->period;
    }

    public function setPeriod(int $period = self::PERIOD_MORNING)
    {
        if ($period < 1) {
            $period = 1;
        }

        if ($period > 3) {
            $period = 3;
        }

        $this->period = $period;

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
