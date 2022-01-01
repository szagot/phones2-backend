<?php

/**
 * Serviços de usuário do sistema
 */

namespace App\Services;

use App\Config\Output;
use App\Models\Contact;
use App\Models\Note;
use Sz\Config\Uri;
use Sz\Conn\Query;

class Notes implements iServices
{
    private $error = '';

    public function run(Uri $uri)
    {
        switch ($uri->getMethod()) {

            case Output::METHOD_GET:

                if (!empty($uri->getFirstUrlParam()) && is_numeric($uri->getFirstUrlParam())) {
                    // GET Notes {idContact}
                    Output::success($this->get($uri->getFirstUrlParam()), $uri->getMethod());
                }

                Output::error('Requisição inválida', $uri->getMethod());
                break;

            case Output::METHOD_DELETE:

                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                if (!$this->delete($uri->getFirstUrlParam())) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            case Output::METHOD_POST:
                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $date = trim($uri->getParam('contactDate'));
                $text = trim($uri->getParam('text'));

                $note = $this->create($uri->getFirstUrlParam(), $date, $text);
                if (!$note) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success($this->amendNotes($note, true), $uri->getMethod());
                break;

            case Output::METHOD_PATCH:
                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $date = trim($uri->getParam('contactDate'));
                $text = trim($uri->getParam('text'));

                $note = $this->update($uri->getFirstUrlParam(), $date, $text);
                if (!$note) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            default:

                Output::error('Requisição inválida', $uri->getMethod());
        }
    }

    private function get($contactId)
    {
        $notes = Query::exec('SELECT * FROM notes WHERE contactId = :id ORDER BY dateContact DESC', [
            'id' => $contactId,
        ], Note::class);

        return empty($notes) ? [] : array_map(array($this, 'amendNotes'), $notes);
    }


    private function delete($id)
    {
        $note = Query::exec('SELECT * FROM notes WHERE id = :id', [
            'id' => $id,
        ], Note::class)[0] ?? null;

        if (!$note) {
            $this->error = 'Não existe uma observação com o ID ' . $id;
            return false;
        }

        $return = Query::exec('DELETE FROM notes WHERE id = :id', [
            'id' => $id,
        ]);

        if (!$return) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return true;
    }

    private function create(int $contactId, $contactDate, $text)
    {
        if (strlen($contactId) < 10 || strlen($contactId) > 11) {
            $this->error = 'Informe um telefone de contato válido. Deve conter 10 ou 11 dígitos, incluindo o DDD.';
            return false;
        }

        if (!preg_match(REGEX_DATE, $contactDate)) {
            $this->error = 'Informe uma data de contato válida. Deve estar no formato 2099-12-31 23:59';
            return false;
        }

        if (strlen($text) < 5) {
            $this->error = 'Informe uma observação válida. Deve ter pelo menos 5 letras.';
            return false;
        }

        // Verificando se o contato existe
        $contact = Query::exec('SELECT * FROM contacts WHERE id = :id', [
            'id' => $contactId,
        ], Contact::class)[0] ?? null;

        if (!$contact) {
            $this->error = 'O telefone de contato informado não existe. Tel: ' . $contactId;
            return false;
        }

        $note = new Note();
        $note
            ->setContactId($contactId)
            ->setDateContact(new \DateTime($contactDate))
            ->setObs($text);

        $response = Query::exec(
            'INSERT INTO notes (contactId, dateContact, obs) VALUES (:contactId, :dateContact, :obs)',
            [
                'contactId'  => $note->getContactId(),
                'dateContact' => $note->getDateContact()->format('Y-m-d H:i:s'),
                'obs'  => $note->getObs(),
            ]
        );

        if (!$response) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        $note->setId(Query::getLog(true)['lastId']);

        return $note;
    }

    public function update(int $id, $dateContact = null, $text = null)
    {
        if (!empty($id)) {
            if (!is_numeric($id)) {
                $this->error = 'Informe um ID de observação válido.';
                return false;
            }
        }

        if (!empty($dateContact)) {
            if (!preg_match(REGEX_DATE, $dateContact)) {
                $this->error = 'Informe uma data de contato válida. Deve estar no formato 2099-12-31 23:59';
                return false;
            }

            try {
                $dateContact = new \DateTime($dateContact);               
            } catch (\Exception $e) {
                $this->error = 'Informe uma data de contato válida. Deve estar no formato 2099-12-31 23:59. ' . $e->getMessage();
                return false;
            }
        }

        if (!empty($text)) {
            if (strlen($text) < 5) {
                $this->error = 'Informe uma observação válida. Deve ter pelo menos 5 letras.';
                return false;
            }
        }

        /** @var Note $note Verifica se a nota existe */
        $note = Query::exec("SELECT * FROM notes WHERE id = :id", [
            'id' => $id,
        ], Note::class)[0] ?? null;

        if (!$note) {
            $this->error = "Não existe uma nota com o id {$id}";
            return false;
        }

        $fields = [
            'id' => $id,
        ];

        $textFields = '';
        if (!empty($dateContact) && $dateContact != $note->getDateContact()) {
            $note->setDateContact($dateContact);
            $fields['dateContact'] = $note->getDateContact()->format('Y-m-d H:i:s');
            $textFields .= (empty($textFields) ? '' : ', ') . 'dateContact = :dateContact';
        }
        if (!empty($text) && $text != $note->getObs()) {
            $note->setObs($text);
            $fields['obs'] = $note->getObs();
            $textFields .= (empty($textFields) ? '' : ', ') . 'obs = :obs';
        }
        // Se não foram necessária alterações
        if (empty($textFields)) {
            return true;
        }

        $response = Query::exec(
            "UPDATE notes SET {$textFields} WHERE id = :id",
            $fields
        );

        if (!$response) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return $note;
    }

    /**
     * Faz a conversão de contato pro formato de devolução
     *
     * @param Note $note
     * @return array
     */
    private function amendNotes(Note $note, bool $withContact = false)
    {
        $return = [
            'id' => (int) $note->getId(),
            'dateContact' => $note->getDateContact()->format('Y-m-d\TH:i'),
            'brazilDate' => $note->getDateContact()->format('d/m/Y H:i'),
            'text' => $note->getObs(),
        ];

        if($withContact){
            $return['contactId'] = $note->getContactId();
        }

        return $return;
    }
}
