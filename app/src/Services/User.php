<?php

/**
 * Serviços de usuário do sistema
 */

namespace App\Services;

use App\Auth\JWT;
use App\Config\Output;
use App\Models\AdminUser;
use Sz\Config\Uri;
use Sz\Conn\Query;

class User implements iServices
{
    private $error = '';

    public function run(Uri $uri)
    {
        switch ($uri->getMethod()) {

            case Output::METHOD_GET:
                
                if (!empty($uri->getFirstUrlParam())) {
                    if (is_numeric($uri->getFirstUrlParam())) {
                        // GET User
                        Output::success($this->get($uri->getFirstUrlParam()), $uri->getMethod());
                    }

                    Output::error('Requisição inválida', $uri->getMethod());
                }

                // GET All Users
                Output::success($this->getAll(), $uri->getMethod());
                break;

            case Output::METHOD_DELETE:

                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $auth = $uri->getHeader('Authorization');
                if (!$this->delete($uri->getFirstUrlParam(), JWT::getUid($auth))) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            case Output::METHOD_POST:

                $email = trim($uri->getParam('email'));
                $name = trim($uri->getParam('name'));
                $password = trim($uri->getParam('password'));
                $confirmPassword = trim($uri->getParam('confirmPassword'));
                $isAdmin = $uri->getParam('isAdmin', FILTER_VALIDATE_BOOLEAN);

                $user = $this->create($name, $email, $password, $confirmPassword, $isAdmin);
                if (!$user) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success($this->amendUser($user), $uri->getMethod());
                break;

            case Output::METHOD_PATCH:

                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $name = trim($uri->getParam('name'));
                $password = trim($uri->getParam('password'));
                $confirmPassword = trim($uri->getParam('confirmPassword'));

                $auth = $uri->getHeader('Authorization');
                $loggedMail = JWT::getUid($auth);
                $isAdmin = $uri->getParam('isAdmin', FILTER_VALIDATE_BOOLEAN);

                $user = $this->update($loggedMail, $uri->getFirstUrlParam(), null, $name, $password, $confirmPassword, $isAdmin);
                if (!$user) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            default:

                Output::error('Requisição inválida', $uri->getMethod());
        }
    }

    private function getAll()
    {
        $users = Query::exec('SELECT * FROM users', [], AdminUser::class);
        return empty($users) ? [] : array_map(array($this, 'amendUser'), $users);
    }

    private function get($id)
    {
        $user = Query::exec('SELECT * FROM users WHERE id = :id', [
            'id' => $id,
        ], AdminUser::class)[0] ?? null;
        return empty($user) ? [] : $this->amendUser($user);
    }

    /**
     * Utilizada no serviço /myuser
     */
    public function getByMail($email)
    {
        $user = Query::exec('SELECT * FROM users WHERE email = :email', [
            'email' => $email,
        ], AdminUser::class)[0] ?? null;
        return empty($user) ? [] : $this->amendUser($user);
    }

    private function delete($id, $userLogged)
    {
        $user = Query::exec('SELECT * FROM users WHERE id = :id', [
            'id' => $id,
        ], AdminUser::class)[0] ?? null;

        if (!$user) {
            $this->error = 'Não existe usuário com o ID ' . $id;
            return false;
        }

        if ($userLogged == $user->getEmail()) {
            $this->error = 'Você não pode deletar a si mesmo';
            return false;
        }

        $return = Query::exec('DELETE FROM users WHERE id = :id', [
            'id' => $id,
        ]);

        if (!$return) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return true;
    }

    private function create($name, $email, $password, $confirmPassword, $isAdmin)
    {
        if (!preg_match(REGEX_MAIL, $email)) {
            $this->error = 'Informe um email válido';
            return false;
        }

        if (strlen($name) < 3 || empty($password)) {
            $this->error = 'Todos os campos são obrigatórios, sendo que nome deve possuir pelo menos 4 caracteres';
            return false;
        }

        if ($password != $confirmPassword) {
            $this->error = 'A confirmação de senha é diferente da senha informada';
            return false;
        }

        if (!preg_match(REGEX_PASS, $password)) {
            $this->error = 'A senha precisa ter pelo menos 6 caracteres, e não pode conter espaços.';
            return false;
        }

        // Verifica se o usuario ja existe
        $user = Query::exec('SELECT * FROM users WHERE email = :email', [
            'email' => $email,
        ], AdminUser::class)[0] ?? null;

        if ($user) {
            $this->error = 'Já existe um usuário com o email ' . $email;
            return false;
        }

        $user = new AdminUser();
        $user
            ->setName($name)
            ->setEmail($email)
            ->setPass($password)
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
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        $user->setId(Query::getLog(true)['lastId']);

        return $user;
    }

    /**
     * Utilizada também no serviço /myuser
     */
    public function update($loggedMail, $id = null, $email = null, $name = null, $password = null, $confirmPassword = null, $isAdmin = null)
    {
        if (empty($id) && empty($email)) {
            $this->error = 'Nenhum identificador informado';
            return false;
        }

        $seeker = empty($email) ? 'id' : 'email';

        if (!empty($password)) {
            if ($password != $confirmPassword) {
                $this->error = 'A confirmação de senha é diferente da senha informada';
                return false;
            }

            if (!preg_match(REGEX_PASS, $password)) {
                $this->error = 'A senha precisa ter pelo menos 6 caracteres, e não pode conter espaços.';
                return false;
            }
        }

        if (!empty($name) && strlen($name) < 3) {
            $this->error = 'O campo nome deve possuir pelo menos 4 caracteres';
            return false;
        }

        /** @var AdminUser $user Verifica se o usuario existe */
        $user = Query::exec("SELECT * FROM users WHERE {$seeker} = :seeker", [
            'seeker' => $$seeker,
        ], AdminUser::class)[0] ?? null;

        if (!$user) {
            $this->error = "Não existe um usuário com o {$seeker} {$$seeker}";
            return false;
        }

        $fields = [
            'seeker' => $$seeker,
        ];
        $textFields = '';
        if (!empty($name) && $name != $user->getName()) {
            $user->setName($name);
            $fields['name'] = $user->getName();
            $textFields .= (empty($textFields) ? '' : ', ') . 'name = :name';
        }
        if (!empty($password)) {
            $user->setPass($password);
            $fields['pass'] = $user->getEncryptedPass();
            $textFields .= (empty($textFields) ? '' : ', ') . 'pass = :pass';
        }
        if (!is_null($isAdmin) && $isAdmin != $user->isAdmin() && $loggedMail != $user->getEmail()) {
            $user->setAdmin($isAdmin);
            $fields['admin'] = $user->isAdmin();
            $textFields .= (empty($textFields) ? '' : ', ') . 'admin = :admin';
        }

        // Se não foram necessária alterações
        if (empty($textFields)) {
            return true;
        }

        $response = Query::exec(
            "UPDATE users SET {$textFields} WHERE {$seeker} = :seeker",
            $fields
        );

        if (!$response) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return $user;
    }

    /**
     * Faz a conversão de usuário pro formato de devolução
     *
     * @param AdminUser $user
     * @return array
     */
    private function amendUser(AdminUser $user)
    {
        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'isAdmin' => $user->isAdmin()
        ];
    }
}
