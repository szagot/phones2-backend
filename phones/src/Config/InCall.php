<?php

/**
 * Controle de ligação, para impedir o acesso a um número que já está sendo usado
 */

namespace App\Config;

use App\Models\BlockedBy;

class InCall
{
    const PATH_CONTROL = TMPDIR . 'incall.json';

    private $inCall;

    public function __construct()
    {
        $this->inCall = file_exists(self::PATH_CONTROL) ? json_decode(file_get_contents(self::PATH_CONTROL)) : [];
    }

    /**
     * Verifica se um número está em ligação
     *
     * @param   string      $userMail
     * @param   integer     $contactId
     * @return  string|bool Retorna o email caso o número já esteja em ligação por outro usuário ou FALSE se contrário
     */
    public function inCall(string $userMail, int $contactId)
    {
        if (empty($this->inCall)) {
            return false;
        }

        /** @var BlockedBy $blockedBy */
        foreach ($this->inCall as $blockedBy) {
            if ($blockedBy->contactId == $contactId) {
                // Faz mais de 1 hora que o número está preso? Libera o numero
                if ($blockedBy->dateTime <= date('Y-m-d H:i:s', strtotime('-1 hour'))) {
                    $this->freeNumber($userMail, $contactId);
                    return false;
                }
                return ($blockedBy->userMail != $userMail) ? $blockedBy->userMail : false;
            }
        }

        return false;
    }

    /**
     * Adiciona um número como estando em ligação
     *
     * @param   string  $userMail
     * @param   integer $contactId
     * @return  bool    Retorna TRUE caso o número foi marcado como em ligação
     */
    public function addCall(string $userMail, int $contactId)
    {
        // Verifica se o número já foi adicionado
        if (!empty($this->inCall)) {
            /** @var BlockedBy $blockedBy */
            foreach ($this->inCall as $blockedBy) {
                if ($blockedBy->contactId == $contactId) {
                    $blockedBy->dateTime = date('Y-m-d H:i:s');
                    return file_put_contents(self::PATH_CONTROL, json_encode($this->inCall));
                }
            }
        }

        $this->inCall[] = new BlockedBy($userMail, $contactId, date('Y-m-d H:i:s'));
        file_put_contents(self::PATH_CONTROL, json_encode($this->inCall));
        return true;
    }

    /**
     * Libera um número
     *
     * @param   string  $userMail
     * @param   integer $contactId
     * @return  bool
     */
    public function freeNumber(string $userMail, int $contactId)
    {
        if (!empty($this->inCall)) {
            /** @var BlockedBy $blockedBy */
            foreach ($this->inCall as $index => $blockedBy) {
                if ($blockedBy->contactId == $contactId) {
                    if ($userMail == $blockedBy->userMail) {
                        unset($this->inCall[$index]);
                        sort($this->inCall);
                        file_put_contents(self::PATH_CONTROL, json_encode($this->inCall));
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
