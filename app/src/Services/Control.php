<?php

/**
 * Controle dos serviços
 */

namespace App\Services;

use App\Auth\JWT;
use App\Config\Output;
use Sz\Config\Uri;

class Control
{
    // Serviços que não precisam de login
    private static $servicesFree = [
        'login'
    ];

    // Serviços que precisam de acessos administrativos
    private static $servicesAdmin = [
        'user'
    ];

    public static function run(Uri $uri)
    {
        self::cors();

        $baseService = '\App\Services\\' . ucwords($uri->getPage());
        if (!class_exists($baseService)) {
            die('Serviço inexistente');
        }

        /** @var iServices $service */
        $service = new $baseService;

        if (!$service instanceof iServices) {
            die('Serviço inexistente');
        }

        // Token válido ou página livre de análise de token?
        if (!in_array($uri->getPage(), self::$servicesFree)) {
            $auth = $uri->getHeader('Authorization');
            if (empty($auth) || !JWT::isActive($auth)) {
                Output::error('Acesso negado', $uri->getMethod(), [], true);
            }
            // É admin?
            if (in_array($uri->getPage(), self::$servicesAdmin)) {
                if (JWT::getObs($auth) != 'admin') {
                    Output::error('Seu usuário não está autorizado a acessar essa página. Contate o seu administrador.', $uri->getMethod());
                }
            }
        }

        $service->run($uri);
    }

    private static function cors()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }
    }
}
