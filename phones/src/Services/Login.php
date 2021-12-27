<?php

/**
 * Serviços de login / JWT Token
 */

namespace App\Services;

use App\Auth\JWT;
use App\Auth\Login as AuthLogin;
use App\Config\Output;
use Sz\Config\Uri;

class Login implements iServices
{
    public function run(Uri $uri)
    {
        if ($uri->getMethod() != 'POST') {
            Output::error('Método inválido', $uri->getMethod());
        }

        $username = $uri->getParam('username');
        $password = $uri->getParam('password');

        if (empty($username) || empty($password)) {
            Output::error('Informe Email e Senha para acesso', $uri->getMethod());
        }

        $login = new AuthLogin();
        $token = $login->login($username, $password);

        if (!$token) {
            Output::error($login->getLastErrorMsg(), $uri->getMethod(), [], true);
        }

        Output::success([
            'user' => JWT::getUid($token),
            'expiresIn' =>  [
                'timestamp' => JWT::getExpires($token)->getTimestamp(),
                'dateTime' => JWT::getExpires($token)->format('Y-m-d H:i:s'),
            ]
        ], $uri->getMethod(), ['authorization' => $token], true);
    }
}
