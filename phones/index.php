<?php

/**
 * Back-end para serviços de disparo de email
 * 
 * @author Daniel Bispo <szagot@gmail.com>
 */

use App\Services\Control;
use Sz\Config\Uri;
use Sz\Conn\Connection;
use Sz\Conn\Query;

require_once 'bootstrap.php';

Query::setConn(new Connection(
    DB_NAME,
    DB_HOST,
    DB_USER,
    DB_PASS
));

Control::run(new Uri(ROOT));
