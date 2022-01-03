<?php

/**
 * Serviços de usuário do sistema
 */

namespace App\Services;

use App\Auth\JWT;
use App\Config\InCall;
use App\Config\Output;
use App\Models\Contact;
use Sz\Config\Uri;
use Sz\Conn\Query;

class Contacts implements iServices
{
    private $error = '';

    public function run(Uri $uri)
    {
        switch ($uri->getMethod()) {

            case Output::METHOD_GET:

                if (!empty($uri->getFirstUrlParam())) {
                    $inCall = new InCall();
                    $contactId = $uri->getFirstUrlParam();
                    $auth = $uri->getHeader('Authorization');
                    $userMail = JWT::getUid($auth);

                    if (is_numeric($contactId)) {
                        // GET Contact

                        // Verificando se o número está liberado
                        if ($userInCall = $inCall->inCall($userMail, (int) $contactId)) {
                            Output::error("O número $contactId já está em uso pelo usuário $userInCall", $uri->getMethod());
                        }
                        $inCall->addCall($userMail, (int) $contactId);

                        Output::success($this->get($contactId), $uri->getMethod());
                    } elseif ($contactId == 'call') {
                        // GET Contact Call
                        Output::success($this->getCall(), $uri->getMethod());
                    } elseif ($contactId == 'revisits') {
                        // GET Contact Revisits
                        Output::success($this->getRevisits(), $uri->getMethod());
                    } elseif ($contactId == 'free' && !empty($uri->getSecondUrlParam())) {
                        // GET Contact Free - Libera um contato pra uso
                        Output::success([
                            'free' => $inCall->freeNumber($userMail, (int) $uri->getSecondUrlParam())
                        ], $uri->getMethod());
                    } elseif ($contactId == 'update' && !empty($uri->getSecondUrlParam())) {
                        // GET Contact Update - Atualiza data de um contato
                        Output::success([
                            'update' => $this->updateTimestampContact((int) $uri->getSecondUrlParam())
                        ], $uri->getMethod());
                    }

                    Output::error('Requisição inválida', $uri->getMethod());
                }

                // GET All Contacts
                Output::success($this->getAll(), $uri->getMethod());
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

                if (empty($uri->getFirstUrlParam()) || !is_numeric($uri->getFirstUrlParam())) {
                    Output::error('Requisição inválida', $uri->getMethod());
                }

                $resident = trim($uri->getParam('resident'));
                $publisher = trim($uri->getParam('publisher'));
                $dayOfWeek = $uri->getParam('dayOfWeek', FILTER_VALIDATE_INT);
                $period = $uri->getParam('period', FILTER_VALIDATE_INT);

                $contact = $this->update($uri->getFirstUrlParam(), $resident, $publisher, $dayOfWeek, $period);
                if (!$contact) {
                    Output::error($this->error, $uri->getMethod());
                }

                Output::success([], $uri->getMethod());
                break;

            default:

                Output::error('Requisição inválida', $uri->getMethod());
        }
    }

    /**
     * Retorna todos os contatos
     */
    private function getAll()
    {
        $contacts = Query::exec(
            'SELECT id, ddd, prefix, sufix, updatedAt, resident FROM contacts ORDER BY id',
            [],
            Contact::class
        );

        return empty($contacts) ? [] : array_map(array($this, 'amendSimpleContact'), $contacts);
    }

    /**
     * Retorna apenas contatos passíveis de ligação
     */
    private function getCall()
    {
        $contacts = Query::exec(
            'SELECT id, ddd, prefix, sufix, updatedAt, resident FROM contacts WHERE updatedAt <= :oldContact ORDER BY id',
            ['oldContact' => date('Y-m-d H:i:s', strtotime(OLD_CONTACT))],
            Contact::class
        );

        return empty($contacts) ? [] : array_map(array($this, 'amendSimpleContact'), $contacts);
    }

    /**
     * Retorna apenas contatos com revisitas
     */
    private function getRevisits()
    {
        $contacts = Query::exec(
            'SELECT id, ddd, prefix, sufix, updatedAt, resident, publisher FROM contacts WHERE COALESCE(resident, "") <> "" ORDER BY id',
            [],
            Contact::class
        );

        return empty($contacts) ? [] : array_map(array($this, 'amendRevisits'), $contacts);
    }

    private function get($id)
    {
        $contact = Query::exec('SELECT * FROM contacts WHERE id = :id', [
            'id' => $id,
        ], Contact::class)[0] ?? null;

        return empty($contact) ? [] : $this->amendContact($contact);
    }

    private function updateTimestampContact($contactId)
    {
        $return = Query::exec(
            'UPDATE contacts SET updatedAt = NOW() WHERE id = :id',
            ['id' => $contactId]
        );

        if (!$return) {
            $this->error = Query::getLog(true)['errorMsg'];
            return false;
        }

        return true;
    }


    private function delete($id)
    {
        $contact = Query::exec('SELECT * FROM contacts WHERE id = :id', [
            'id' => $id,
        ], Contact::class)[0] ?? null;

        if (!$contact) {
            $this->error = 'Não existe contato com o ID ' . $id;
            return false;
        }

        $return = Query::exec('DELETE FROM contacts WHERE id = :id', [
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
                'INSERT INTO contacts (id, ddd, prefix, sufix, updatedAt) VALUES (:id, :ddd, :prefix, :sufix, :updatedAt)',
                [
                    'id'  => $contact->getId(),
                    'ddd' => $contact->getDDD(),
                    'prefix'  => $contact->getPrefix(),
                    'sufix' => $contact->getSufix(),
                    'updatedAt' => date('Y-m-d H:i:s', strtotime(OLD_CONTACT)),
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

        /** @var Contact $contact Verifica se o contato existe */
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
     * @param Contact $contact
     * @return array
     */
    private function amendContact(Contact $contact)
    {
        return [
            'id' => (int) $contact->getId(),
            'ddd' => (int) $contact->getDDD(),
            'prefix' => (int) $contact->getPrefix(),
            'sufix' => (int) $contact->getSufix(),
            'formatted' => "({$contact->getDDD()}) {$contact->getPrefix()}-{$contact->getSufix()}",
            'international' => INT_PREFIX . $contact->getId(),
            'resident' => $contact->getResident(),
            'publisher' => $contact->getPublisher(),
            'dayOfWeek' => (int) $contact->getDayOfWeek(),
            'dayOfWeekText' => $contact->getDayOfWeek(true),
            'period' => (int) $contact->getPeriod(),
            'periodText' => $contact->getPeriod(true),
            'updatedAt' => $contact->getUpdatedAt()->format('Y-m-d\TH:i'),
            'brazilDate' => $contact->getUpdatedAt()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Faz a conversão de contato pro formato de devolução simplificado
     *
     * @param Contact $contact
     * @return array
     */
    private function amendSimpleContact(Contact $contact)
    {
        return [
            'phone' => (int) $contact->getId(),
            'formatted' => "({$contact->getDDD()}) {$contact->getPrefix()}-{$contact->getSufix()}",
            'international' => INT_PREFIX . $contact->getId(),
            'updatedAt' => $contact->getUpdatedAt()->format('Y-m-d\TH:i'),
            'brazilDate' => $contact->getUpdatedAt()->format('d/m/y H:i'),
            'allowCall' => $contact->getUpdatedAt()->format('Y-m-d H:i') <= date('Y-m-d H:i', strtotime(OLD_CONTACT)),
            'hasRevisit' => !empty($contact->getResident()),
        ];
    }

    /**
     * Faz a conversão de contato pro formato de drevisitas
     *
     * @param Contact $contact
     * @return array
     */
    private function amendRevisits(Contact $contact)
    {
        return [
            'phone' => (int) $contact->getId(),
            'formatted' => "({$contact->getDDD()}) {$contact->getPrefix()}-{$contact->getSufix()}",
            'international' => INT_PREFIX . $contact->getId(),
            'resident' => $contact->getResident(),
            'publisher' => $contact->getPublisher(),
            'updatedAt' => $contact->getUpdatedAt()->format('Y-m-d\TH:i'),
            'brazilDate' => $contact->getUpdatedAt()->format('d/m/y H:i'),
        ];
    }
}
