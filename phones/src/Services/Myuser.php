<?php

/**
 * Serviços para o usuário logado
 */

namespace App\Services;

use App\Auth\JWT;
use App\Config\Output;
use Sz\Config\Uri;

class Myuser implements iServices
{
    public function run(Uri $uri)
    {
        $userService = new User();
        $auth = $uri->getHeader('Authorization');
        $loggedMail = JWT::getUid($auth);

        switch ($uri->getMethod()) {
            
            case Output::METHOD_PATCH:

                $email = $loggedMail;
                $name = $uri->getParam('name');
                $password = $uri->getParam('password');
                $confirmPassword = $uri->getParam('confirmPassword');

                $user = $userService->update($loggedMail, null, $email, $name, $password, $confirmPassword, null);
                if (!$user) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            case Output::METHOD_GET:

                Output::success($userService->getByMail($loggedMail), $uri->getMethod());
                break;

            default:

                Output::error('Requisição inválida', $uri->getMethod());
        }
    }
}
