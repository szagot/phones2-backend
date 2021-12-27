<?php

use App\Config\Log;
use Sz\Conn\Connection;
use Sz\Conn\Query;

require_once 'phones/bootstrap.php';

Query::setConn(new Connection(
    DB_NAME,
    DB_HOST,
    DB_USER,
    DB_PASS
));

if (!file_exists(TMPDIR . CSV_PREACHING) || !file_exists(TMPDIR . CSV_REVISITS)) {
    die('Nenhum arquivo encontrado para transferencia');
}

$log = new Log('transfer-to-csv');

$preachings = preg_split('/[\n\r]+/', file_get_contents(TMPDIR . CSV_PREACHING));
$revisits = preg_split('/[\n\r]+/', file_get_contents(TMPDIR . CSV_REVISITS));

// Criando listas
$qtInsert = 0;
foreach ($preachings as $preaching) {
    if (empty(trim($preaching))) {
        continue;
    }
    @list($ddd, $prefix, $sufix, $dateTime) = explode(';', $preaching);
    $id = $ddd . $prefix . $sufix;

    // Verificando existencia de registro
    $reg = Query::exec('SELECT * FROM contacts WHERE id = :id', ['id' => $id])[0] ?? null;
    if ($reg) {
        continue;
    }

    // Inserindo registro
    $response = Query::exec(
        'INSERT INTO contacts (id, ddd, prefix, sufix, updatedAt) VALUES (:id, :ddd, :prefix, :sufix, :updatedAt)',
        [
            'id'        => $id,
            'ddd'       => $ddd,
            'prefix'    => $prefix,
            'sufix'     => $sufix,
            'updatedAt' => $dateTime,
        ]
    );

    if (!$response) {
        $msg = "Erro ao inserir registro {$id}. " . Query::getLog(true)['errorMsg'];
        $log->setLog($msg, Log::LOG_ERROR);
        die($msg);
    }

    $qtInsert++;
}

echo "$qtInsert resgistros de campo novos" . PHP_EOL;

// Criando revisitas
$qtInsert = 0;
foreach ($revisits as $revisit) {
    if (empty(trim($revisit))) {
        continue;
    }
    @list($id, $resident, $publisher, $dateTime, $dayOfWeek, $text) = explode(';', $revisit);

    // Se não tem nem data pula
    if(empty($dateTime)){
        continue;
    }

    // Verificando existencia de registro
    $reg = Query::exec('SELECT * FROM contacts WHERE id = :id', ['id' => $id])[0] ?? null;
    if (!$reg) {
        $msg = "Registro de revisita {$id} não encontrado em contatos. " . Query::getLog(true)['errorMsg'];
        $log->setLog($msg, Log::LOG_ERROR);
        die($msg);
    }

    // Verifica se já possui essas entradas
    $reg = Query::exec(
        'SELECT * FROM notes WHERE (dateContact = :dateContact)  AND contactId = :contactId',
        [
            'contactId' => $id,
            'dateContact' => $dateTime,
        ]
    )[0] ?? null;
    if ($reg) {
        continue;
    }

    // Atualiza o registro principal
    $response = Query::exec(
        'UPDATE contacts SET resident = :resident, publisher = :publisher, dayOfWeek = :dayOfWeek, updatedAt = :updatedAt WHERE id = :id',
        [
            'id'        => $id,
            'resident'  => $resident,
            'publisher' => $publisher,
            'dayOfWeek' => $dayOfWeek,
            'updatedAt' => $dateTime,
        ]
    );

    if (!$response) {
        $msg = "Erro ao atualizar registro {$id}. " . Query::getLog(true)['errorMsg'];
        $log->setLog($msg, Log::LOG_ERROR);
        die($msg);
    }

    // Quebrando as observações
    $textLines = explode('<br>', $text);
    foreach ($textLines as $obs) {
        $response = Query::exec(
            'INSERT INTO notes (contactId, dateContact, obs) VALUES (:contactId, :dateContact, :obs)',
            [
                'contactId'   => $id,
                'dateContact' => $dateTime,
                'obs'         => $obs,
            ]
        );

        if (!$response) {
            $msg = "Erro ao inserir revisita do registro {$id}. " . Query::getLog(true)['errorMsg'];
            $log->setLog($msg, Log::LOG_ERROR);
            die($msg);
        }

        $qtInsert++;
    }
}

echo "$qtInsert observações de revisitas novas" . PHP_EOL;
$log->setLog('Transferencia de CSV para BD realizada', Log::LOG_SUCCESS);

unlink(TMPDIR . CSV_PREACHING);
unlink(TMPDIR . CSV_REVISITS);
