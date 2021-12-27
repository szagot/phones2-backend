<?php

/**
 * Configura a saída do serviço
 */

namespace App\Config;

class Output
{

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';

    /**
     * Mensagem em caso de sucesso
     *
     * @param array $data
     * @param string $method
     * @param array $headers ['chave' => 'valor']
     * @param boolean $isAuth
     */
    public static function success(array $data = [], $method = self::METHOD_GET, $headers = [], $isAuth = false)
    {
        self::setMessage($data, $method, false, $headers, $isAuth);
    }
    /**
     * Mensagem em caso de falha
     *
     * @param string $errorMessage
     * @param string $method
     * @param array $headers ['chave' => 'valor']
     * @param boolean $isAuth
     */
    public static function error(string $errorMessage, $method = self::METHOD_GET, $headers = [], $isAuth = false)
    {
        self::setMessage([
            'message' => $errorMessage,
            'status' => $isAuth ? 401 : ($method == self::METHOD_GET ? 404 : 400),
            'timestamp' => (new \DateTime('now'))->getTimestamp(),
        ], $method, true, $headers, $isAuth);
    }

    private static function setMessage(array $data = [], $method = self::METHOD_GET, $isError = false, $headers = [], $isAuth = false)
    {
        header('Content-Type: application/json');

        if ($isAuth) {
            header('access-control-expose-headers: Authorization');
        }

        if (!empty($headers)) {
            foreach ($headers as $key => $header) {
                header("{$key}: {$header}");
            }
        }

        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' ';
        if ($isAuth && $isError) {
            header($protocol . '401 Unauthorized');
        } else {
            switch ($method) {
                case self::METHOD_POST:
                    header($protocol . ($isError ? '400 Bad Request' : '201 Created'));
                    break;

                case self::METHOD_PUT:
                case self::METHOD_PATCH:
                case self::METHOD_DELETE:
                    header($protocol . ($isError ? '400 Bad Request' : '204 No Content'));
                    break;

                default:
                    header($protocol . ($isError ? '404 Not Found' : '200 OK'));
            }
        }

        if (!empty($data)) {
            echo json_encode($data);
        }

        exit;
    }
}
