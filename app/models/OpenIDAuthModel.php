<?php


namespace PitouFW\Model;


use PitouFW\Core\Redis;
use stdClass;

class OpenIDAuthModel {
    const AUTH_ID_CACHE_PREFIX = 'openid_auth_id_';
    const AUTH_ID_CACHE_TTL = 3600; // 1 hour

    const AUTH_CODE_CACHE_PREFIX = 'openid_auth_code_';
    const AUTH_CODE_CACHE_TTL = 60; // 1 min

    const ACCESS_TOKEN_CACHE_PREFIX= 'openid_access_token_';
    const ACCESS_TOKEN_CACHE_TTL = 60; // 1 min

    /**
     * @param string $auth_id
     * @param array|stdClass $params
     */
    public static function saveAuthId(string $auth_id, array|stdClass $params): void {
        $redis = new Redis();
        $redis->set(self::AUTH_ID_CACHE_PREFIX . $auth_id, $params, self::AUTH_ID_CACHE_TTL);
    }

    /**
     * @param string $auth_id
     * @return string
     */
    public static function hashAuthId(string $auth_id): string {
        return hash_hmac('sha512', $auth_id, INTERNAL_API_KEY);
    }

    public static function saveAuthCode(string $auth_code, array|stdClass $params): void {
        $redis = new Redis();
        $redis->set(self::AUTH_CODE_CACHE_PREFIX . $auth_code, $params, self::AUTH_CODE_CACHE_TTL);
    }
}
