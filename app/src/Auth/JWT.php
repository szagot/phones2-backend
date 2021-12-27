<?php

/**
 * Gerador e verificador de Token
 * 
 * Vide: https://jwt.io/
 */

namespace App\Auth;

class JWT
{

    private static $key = 'App';
    private static $typ = 'JWT';
    private static $alg = 'HS256';

    /**
     * Gera o token baseado no ID (pode ser um email, ou qualquer identificador)
     *
     * @param string $id
     * @param string $obs
     * @return string
     */
    public static function generateBearer(string $id, string $obs = '')
    {
        $header = [
            'typ' => self::$typ,
            'alg' => self::$alg,
        ];

        $payload = [
            'exp' => (new \DateTime(TOKEN_EXPIRES))->getTimestamp(),
            'uid' => $id,
            'obs' => $obs
        ];

        $header = base64_encode(json_encode($header));
        $payload = base64_encode(json_encode($payload));

        $sign = base64_encode(hash_hmac('sha256', $header . "." . $payload, self::$key, true));

        return 'Bearer ' . $header . '.' . $payload . '.' . $sign;
    }

    /**
     * Verifica se o token é válido
     *
     * @param string $bearer
     * @return boolean|string Se tudo estiver ok, retorna o UID logado
     */
    public static function isActive($bearer)
    {
        $decode = self::decodeToken($bearer);
        $sign = $decode['sign'];
        $header = $decode['header'];
        $payload = $decode['payload'];
        $signCompare = base64_encode(hash_hmac('sha256', $header . "." . base64_encode(json_encode($payload)), self::$key, true));

        if ($sign != $signCompare) {
            return false;
        }

        // Ainda está dentro do prazo?
        $exp = self::getExpires($bearer);
        $now = new \DateTime('now');
        if ($exp < $now) {
            return false;
        }

        return true;
    }

    /**
     * Devolve o UID do token
     *
     * @param string $bearer
     * @return string
     */
    public static function getUid($bearer)
    {
        $payload = self::decodeToken($bearer)['payload'] ?? null;
        return $payload['uid'] ?? null;
    }

    /**
     * Devolve o UID do token
     *
     * @param string $bearer
     * @return string
     */
    public static function getObs($bearer)
    {
        $payload = self::decodeToken($bearer)['payload'] ?? null;
        return $payload['obs'] ?? null;
    }

    /**
     * Devolve a data de expiração
     *
     * @param string $bearer
     * @return \DateTime
     */
    public static function getExpires($bearer)
    {
        $payload = self::decodeToken($bearer)['payload'] ?? null;
        $exp = new \DateTime();
        if (isset($payload['exp'])) {
            $exp->setTimestamp($payload['exp']);
        }
        return $exp;
    }

    private static function decodeToken($bearer)
    {
        $token = str_replace('Bearer ', '', $bearer);
        @list($header, $payload, $sign) = explode('.', $token);
        $payload = @json_decode(base64_decode($payload), true);
        return [
            'sign' => $sign,
            'header' => $header,
            'payload' => $payload,
        ];
    }
}
