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
                // TODO
                Output::success([], $uri->getMethod());
                // *****

                $ddd = $uri->getParam('ddd', FILTER_VALIDATE_INT);
                $prefix = $uri->getParam('prefix', FILTER_VALIDATE_INT);
                $sufixStart = $uri->getParam('sufixStart', FILTER_VALIDATE_INT);
                $sufixEnd = $uri->getParam('sufixEnd', FILTER_VALIDATE_INT);

                $contacts = $this->create($ddd, $prefix, $sufixStart, $sufixEnd);
                if (!$contacts) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success(array_map(array($this, 'amendSimpleContact'), $contacts), $uri->getMethod());
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

    private function get($idContact)
    {
        $notes = Query::exec('SELECT * FROM notes WHERE contactId = :id', [
            'id' => $idContact,
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

    private function create(int $ddd, int $prefix, int $sufixStart, int $sufixEnd = null)
    {
        if (strlen($ddd) != 2) {
            $this->error = 'Informe um DDD válido';
            return false;
        }

        if (strlen($prefix) < 4 || strlen($prefix) > 5) {
            $this->error = 'Informe um prefixo válido';
            return false;
        }

        if (strlen($sufixStart) != 4) {
            $this->error = 'Informe um sufixo válido';
            return false;
        }

        if (empty($sufixEnd)) {
            $sufixEnd = $sufixStart;
        } elseif (strlen($sufixEnd) != 4 || $sufixEnd < $sufixStart) {
            $this->error = 'Informe um sufixo de faixa válido. Precisa ser maior ou igual ao sufixo de início da faixa.';
            return false;
        }

        $contacts = [];
        $index = $sufixStart;

        do {
            $id = $ddd . $prefix . $index;

            // Verifica se o contato ja existe
            $contact = Query::exec('SELECT * FROM contacts WHERE id = :id', [
                'id' => $id,
            ], Contact::class)[0] ?? null;

            if ($contact) {
                $this->error = 'O contato já existe. Tel: ' . $id;
                continue;
            }

            $contact = new Contact();
            $contact
                ->setDDD($ddd)
                ->setPrefix($prefix)
                ->setSufix($index)
                ->generateId();

            $response = Query::exec(
                'INSERT INTO contacts (id, ddd, prefix, sufix) VALUES (:id, :ddd, :prefix, :sufix)',
                [
                    'id'  => $contact->getId(),
                    'ddd' => $contact->getDDD(),
                    'prefix'  => $contact->getPrefix(),
                    'sufix' => $contact->getSufix(),
                ]
            );

            if (!$response) {
                $this->error = Query::getLog(true)['errorMsg'];
                return false;
            }

            $contacts[] = $contact;
        } while ($sufixEnd > $index++);

        return $contacts;
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
    private function amendNotes(Note $note)
    {
        return [
            'id' => (int) $note->getId(),
            'dateContact' => $note->getDateContact()->format('Y-m-d H:i:s'),
            'brazilDate' => $note->getDateContact()->format('d/m/Y H:i'),
            'text' => $note->getObs(),
        ];
    }
}
