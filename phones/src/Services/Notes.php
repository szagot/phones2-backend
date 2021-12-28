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

                $contactId = $uri->getParam('contactId', FILTER_VALIDATE_INT);
                $date = $uri->getParam('contactDate');
                $text = $uri->getParam('text');

                $note = $this->create($contactId, $date, $text);
                if (!$note) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success($this->amendNotes($note, true), $uri->getMethod());
                break;

            case Output::METHOD_PATCH:
                // TODO
                Output::success([], $uri->getMethod());
                // *****

                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $resident = trim($uri->getParam('resident'));
                $publisher = trim($uri->getParam('publisher'));
                $dayOfWeek = $uri->getParam('dayOfWeek', FILTER_VALIDATE_INT);
                $period = $uri->getParam('period', FILTER_VALIDATE_INT);

                $user = $this->update($uri->getFirstUrlParam(), $resident, $publisher, $dayOfWeek, $period);
                if (!$user) {
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
        $notes = Query::exec('SELECT * FROM notes WHERE contactId = :id', [
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

    /**
     * Utilizada também no serviço /myuser
     */
    public function update(int $id, $resident = null, $publisher = null, int $dayOfWeek = null, int $period = null)
    {
        if (!empty($resident)) {
            if (strlen($resident) < 3) {
                $this->error = 'Informe um nome válido para o Morador. Precisa ter mais que 2 letras.';
                return false;
            }
        }

        if (!empty($publisher)) {
            if (strlen($publisher) < 3) {
                $this->error = 'Informe um nome válido para o Publicador. Precisa ter mais que 2 letras.';
                return false;
            }
        }

        if (!empty($dayOfWeek)) {
            if ($dayOfWeek < 1 || $dayOfWeek > 7) {
                $this->error = 'Informe um dia da semana válido. Deve ser um número de 1 a 7, sendo 1 = Domingo.';
                return false;
            }
        }

        if (!empty($period)) {
            if ($period < 1 || $period > 3) {
                $this->error = 'Informe um período válido. Deve ser um número de 1 a 3, sendo 1 = Manhã, 2 = Tarde e 3 = Noite.';
                return false;
            }
        }


        /** @var Contact $user Verifica se o usuario existe */
        $contact = Query::exec("SELECT * FROM contacts WHERE id = :id", [
            'id' => $id,
        ], Contact::class)[0] ?? null;

        if (!$contact) {
            $this->error = "Não existe um contato com o número {$id}";
            return false;
        }

        $fields = [
            'id' => $id,
        ];

        $textFields = '';
        if (!empty($resident) && $resident != $contact->getResident()) {
            $contact->setResident($resident);
            $fields['resident'] = $contact->getResident();
            $textFields .= (empty($textFields) ? '' : ', ') . 'resident = :resident';
        }
        if (!empty($publisher) && $publisher != $contact->getPublisher()) {
            $contact->setPublisher($publisher);
            $fields['publisher'] = $contact->getPublisher();
            $textFields .= (empty($textFields) ? '' : ', ') . 'publisher = :publisher';
        }
        if (!empty($dayOfWeek) && $dayOfWeek != $contact->getDayOfWeek()) {
            $contact->setDayOfWeek($dayOfWeek);
            $fields['dayOfWeek'] = $contact->getDayOfWeek();
            $textFields .= (empty($textFields) ? '' : ', ') . 'dayOfWeek = :dayOfWeek';
        }
        if (!empty($period) && $period != $contact->getPeriod()) {
            $contact->setPeriod($period);
            $fields['period'] = $contact->getPeriod();
            $textFields .= (empty($textFields) ? '' : ', ') . 'period = :period';
        }

        // Se não foram necessária alterações
        if (empty($textFields)) {
            return true;
        }

        $response = Query::exec(
            "UPDATE contacts SET {$textFields} WHERE id = :id",
            $fields
        );

        if (!$response) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return $contact;
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
            'dateContact' => $note->getDateContact()->format('Y-m-d H:i:s'),
            'brazilDate' => $note->getDateContact()->format('d/m/Y H:i'),
            'text' => $note->getObs(),
        ];

        if($withContact){
            $return['contactId'] = $note->getContactId();
        }

        return $return;
    }
}
