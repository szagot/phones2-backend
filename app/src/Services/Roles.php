<?php

/**
 * Devolve as regras de acesso conforme o tipo de usuário
 */

namespace App\Services;

use App\Auth\JWT;
use App\Config\Output;
use App\Models\AdminUser;
use Sz\Config\Uri;
use Sz\Conn\Query;

class Roles implements iServices
{
    public function run(Uri $uri)
    {
        $auth = $uri->getHeader('Authorization');
        $email = JWT::getUid($auth);
        $roles = [
            [
                'role' => 'ROLE_USER',
                'privileges' => [
                    ['privilege' => 'CREATE'],
                    ['privilege' => 'UPDATE'],
                    ['privilege' => 'READ'],
                    ['privilege' => 'EXECUTE'],
                    ['privilege' => 'DELETE'],
                ]
            ]
        ];

        if (!empty($email)) {
            $user = Query::exec('SELECT * FROM users WHERE email = :email', [
                'email' => $email,
            ], AdminUser::class)[0] ?? null;

            if (!empty($user) && $user->isAdmin()) {
                $roles[] = [
                    'role' => 'ROLE_ADMIN',
                    'privileges' => [
                        ['privilege' => 'CREATE'],
                        ['privilege' => 'UPDATE'],
                        ['privilege' => 'READ'],
                        ['privilege' => 'EXECUTE'],
                        ['privilege' => 'DELETE'],
                    ]
                ];
            }
        }

        Output::success([
            'roles' => $roles
        ], $uri->getMethod());
    }
}
