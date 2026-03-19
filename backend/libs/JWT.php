<?php
class JWT {
    private static $secret_key = 'TuClaveSuperSecreta2026_Cambiala';
    private static $encrypt = 'HS256';
    private static $expiration = 3600; // 1 hora en segundos

    public static function encode($data) {
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$encrypt]);
        $payload = json_encode(array_merge($data, [
            'iat' => time(),
            'exp' => time() + self::$expiration
        ]));

        $base64_header = self::base64UrlEncode($header);
        $base64_payload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64_header . '.' . $base64_payload, self::$secret_key, true);
        $base64_signature = self::base64UrlEncode($signature);

        return $base64_header . '.' . $base64_payload . '.' . $base64_signature;
    }

    public static function decode($token) {
        $parts = explode('.', $token);
        if (count($parts) != 3) return null;

        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        
        // Verificar expiración
        if ($payload['exp'] < time()) return null;

        // Verificar firma
        $signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], self::$secret_key, true);
        $base64_signature = self::base64UrlEncode($signature);

        if ($base64_signature !== $parts[2]) return null;

        return $payload;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
?>