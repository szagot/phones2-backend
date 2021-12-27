<?php

/**
 * Efetua o login
 */

namespace App\Auth;

use App\Models\AdminUser;
use Sz\Conn\Query;

class Login
{
    private $errorMsg;

    /**
     * Tenta efetuar o login
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function login($username, $password)
    {
        /** @var User $user */
        $user = Query::exec('SELECT * FROM users WHERE email = :email', [
            'email' => $username,
        ], AdminUser::class)[0] ?? null;

        // Existe o usuario
        if (empty($user)) {
            $this->errorMsg = 'Usuário não cadastrado';
            return false;
        }

        // Verifica a senha
        if (!$user->verifyPass($password)) {
            $this->errorMsg = 'Senha inválida';
            return false;
        }

        return JWT::generateBearer($user->getEmail(), $user->isAdmin() ? 'admin' : 'normal');
    }

    public function createNewUser($name, $email, $pass, $isAdmin = false)
    {
        // verifica se o usuario ja existe
        $user = Query::exec('SELECT * FROM users WHERE email = :email', [
            'email' => $email,
        ])[0] ?? null;

        // Campos obrigatórios preenchidos?
        if (empty($name) || empty($email) || strlen($pass) < 6) {
            $this->errorMsg = 'Para novos usuários, preencha os campos Nome, Email e Senha. A senha deve ter no mínimo 6 caracteres';
            return false;
        }

        // Existe o usuario
        if (!empty($user)) {
            $this->errorMsg = 'Email já cadastrado';
            return false;
        }

        $user = new AdminUser();
        $user
            ->setName($name)
            ->setEmail($email)
            ->setPass($pass)
            ->setAdmin($isAdmin);

        $response = Query::exec(
            'INSERT INTO users (name, email, pass, admin) VALUES (:name, :email, :pass, :admin)',
            [
                'name'  => $user->getName(),
                'email' => $user->getEmail(),
                'pass'  => $user->getEncryptedPass(),
                'admin' => $user->isAdmin(),
            ]
        );

        if (!$response) {
            $this->errorMsg = Query::getLog(true)['errorMsg'];
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getLastErrorMsg()
    {
        return $this->errorMsg;
    }
}
