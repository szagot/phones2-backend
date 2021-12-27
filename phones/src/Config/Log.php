<?php

/**
 * Grava log
 */

namespace App\Config;

class Log
{
    const LOG_ERROR = 'ERROR';
    const LOG_WARNING = 'WARNING';
    const LOG_INFO = 'INFO';
    const LOG_SUCCESS = 'SUCCESS';

    private $name;

    /**
     * @param string $type Tipo do log. Não deve contra espaços nem caracteres especiais.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setLog(string $log, string $type = LOG_INFO)
    {
        $strType = str_pad($type, 7, ' ');
        $strDate = date('Y-m-d H:i:s');
        
        file_put_contents($this->getLogFileName(), "{$strType} | {$strDate} | {$log}" . PHP_EOL, FILE_APPEND);
    }

    private function getLogFileName()
    {
        return LOGDIR . $this->name . '-' . date('Ymd') . '.log';
    }
}
