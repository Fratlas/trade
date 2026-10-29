<?php
declare(strict_types=1);
class JwtHandler {
    private static function b64(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
    private static function decodeB64(string $value) {
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) return false;
        return base64_decode(strtr($value, '-_', '+/'), true);
    }
    public static function encode(array $payload): string {
        $header=self::b64(json_encode(['typ'=>'JWT','alg'=>'HS256']));
        $payload['iat']=time();
        $payload['exp']=time()+JWT_EXPIRY;
        $body=self::b64(json_encode($payload));
        $sig=self::b64(hash_hmac('sha256',$header.'.'.$body,JWT_SECRET,true));
        return $header.'.'.$body.'.'.$sig;
    }
    public static function decode(string $token): ?array {
        $parts=explode('.',$token);
        if (count($parts)!==3) return null;
        [$header,$body,$sig]=$parts;
        $h=self::decodeB64($header); $p=self::decodeB64($body);
        if ($h===false || $p===false) return null;
        $headerData=json_decode($h,true);
        $payload=json_decode($p,true);
        if (!is_array($headerData) || ($headerData['alg']??null)!=='HS256' || ($headerData['typ']??null)!=='JWT') return null;
        if (!is_array($payload) || !isset($payload['exp'],$payload['user_id']) || !is_numeric($payload['exp']) || $payload['exp']<=time()) return null;
        $expected=self::b64(hash_hmac('sha256',$header.'.'.$body,JWT_SECRET,true));
        if (!hash_equals($expected,$sig)) return null;
        return $payload;
    }
}
