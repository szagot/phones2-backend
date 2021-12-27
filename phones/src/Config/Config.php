<?php

/**
 * Pega os dados de configuração
 */

namespace App\Config;

class Config
{
    private $config;

    public function __construct()
    {
        $this->config = include(BASEDIR . 'configure.php');

        // Pasta temporária existe? Senão, cria
        if (!file_exists(TMPDIR)) {
            mkdir(TMPDIR);
        }

        // Pasta de logs existe? Senão, cria
        if (!file_exists(LOGDIR)) {
            mkdir(LOGDIR);
        }
    }

    public function isDebug()
    {
        return $this->config['debug'] ?? true;
    }

    public function getTokenExpires()
    {
        // Se não foi declarado, ele coloca 12 horas para debug, ou 30 minutos se não tiver em debug.
        return $this->config['tokenExpires'] ?? ($this->isDebug() ? '+12 hours' : '+30 minutes');
    }

    public function getRoot()
    {
        return $this->config['root'] ?? '';
    }



    public function getDBName()
    {
        return $this->config['db_name'] ?? '';
    }

    public function getDBHost()
    {
        return $this->config['db_host'] ?? '';
    }

    public function getDBUser()
    {
        return $this->config['db_user'] ?? '';
    }

    public function getDBPass()
    {
        return $this->config['db_pass'] ?? '';
    }



    public function getCountryCode()
    {
        return $this->config['country_code'] ?? '+55';
    }

    public function getOldContact()
    {
        return $this->config['old_contact'] ?? '-3 months';
    }
}
