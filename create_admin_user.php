<?php
/**
 * Cria um usuário administrativo.
 * ATENÇÃO! Apague esse arquivo do servidor após criar o primeiro usuário.
 */

use App\Auth\Login;
use Sz\Conn\Connection;
use Sz\Conn\Query;

require_once 'app/bootstrap.php';

Query::setConn(new Connection(
    DB_NAME,
    DB_HOST,
    DB_USER,
    DB_PASS
));

$login = new Login();
$login->createNewUser('Aministrador', 'email@admin.com', 'Senha!1234', true);