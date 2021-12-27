<?php

namespace App\Models;


class AdminUser
{
    private $id;
    private $name;
    private $email;
    private $pass;
    private $admin;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AdminUser
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return AdminUser
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return AdminUser
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEncryptedPass()
    {
        return $this->pass;
    }

    /**
     * @param string $pass
     *
     * @return bool
     */
    public function verifyPass($pass)
    {
        return password_verify($pass, $this->pass);
    }

    /**
     * @param string $pass
     *
     * @return AdminUser
     */
    public function setPass($pass)
    {
        $this->pass = password_hash($pass, PASSWORD_BCRYPT);
        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->admin == 1;
    }

    /**
     * @param bool $admin
     *
     * @return AdminUser
     */
    public function setAdmin(bool $admin)
    {
        $this->admin = $admin ? 1 : 0;
        return $this;
    }
}
