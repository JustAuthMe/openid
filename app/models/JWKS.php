<?php


namespace PitouFW\Model;


use PitouFW\Cache\OIDC;
use PitouFW\Core\ApiCall;
use PitouFW\Core\Redis;

class JWKS {
    const DEFAULT_ALG = 'RS256';

    const CACHE_KEY = 'jwks';
    const CACHE_TTL = 86400 * 366;

    private static function generateKeys(string $alg = self::DEFAULT_ALG): array {
        $keys = [
            'pub' => [],
            'pair' => []
        ];

        $apiCall = new ApiCall();
        $apiCall->setUrl('https://mkjwk.org/jwk/rsa?alg=' . $alg . '&use=sig&gen=sha256&size=2048');

        for ($i = 0; $i < 2; $i++) {
            $apiCall->exec();
            $keys['pub'][] = $apiCall->responseObj()->pub;
            $keys['pair'][] = $apiCall->responseObj()->jwk;
        }

        return $keys;
    }

    private static function getKeys(string $type = 'pub'): array {
        $keys = [
            'pub' => [],
            'pair' => []
        ];

        $redis = new Redis();
        $keys = $redis->get(self::CACHE_KEY, true);

        if ($keys === false) {
            foreach (OIDC::ID_TOKEN_SIGNING_ALG_VALUES_SUPPORTED as $alg) {
                $keys = self::generateKeys($alg);
            }

            $redis->set(self::CACHE_KEY, $keys, self::CACHE_TTL);
        }

        return $type === 'pair' ? $keys['pair'] : $keys['pub'];
    }

    public static function getPubKeys(): array {
        return self::getKeys('pub');
    }

    public static function getKeypairs() {
        return self::getKeys('pair');
    }
}
