<?php

/**
 * Configurações iniciais
 */

use App\Config\Config;

require_once 'vendor/autoload.php';

// Diretórios de base
define('BASEDIR', __DIR__ . DIRECTORY_SEPARATOR);
// Para arquivos criados pelo Apache
define('TMPDIR', BASEDIR . 'tmp' . DIRECTORY_SEPARATOR);
// para arquivos criados pelo usuário do servidor
define('LOGDIR', BASEDIR . 'log' . DIRECTORY_SEPARATOR);

$config = new Config();

// Raiz do sistema
define('ROOT', $config->getRoot());

// Durabilidade do token
define('TOKEN_EXPIRES', $config->getTokenExpires());

// Dados do banco
define('DB_NAME', $config->getDBName());
define('DB_HOST', $config->getDBHost());
define('DB_USER', $config->getDBUser());
define('DB_PASS', $config->getDBPass());

// Padrões
define('REGEX_PASS', '/^[^\s\t]{6,}$/');
define('REGEX_MAIL', '/^[^@\s]+@[^@\s.]+\.[^@\s]+$/i');

date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', $config->isDebug() ? 'On' : 'Off');
error_reporting($config->isDebug() ? E_ALL : 0);
