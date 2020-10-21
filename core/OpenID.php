<?php


namespace PitouFW\Core;


class OpenID {
    public static function init() {
        $apiCall = new ApiCall();
        $apiCall->setUrl(APP_URL . '.well-known/openid-configuration')
            ->exec();

        $output = '<?php' . "\n\n" .
            '/* Auto-generated at ' . date('Y-m-d H:i:s') . ' */' . "\n\n" .
            'namespace PitouFW\Cache;' . "\n\n" .
            'class OIDC {' . "\n";
        foreach ($apiCall->responseObj() as $key => $value) {
            $output .= "\t" . 'const ' . strtoupper($key) . ' = ' .
                (is_string($value) ? '"' .
                    str_replace('"', '\"', $value) . '";' :
                    (is_array($value) ?
                        str_replace("\n", "\n\t",
                            json_encode($value, JSON_PRETTY_PRINT)) . ';' : 'null;'
                    )
                ) . "\n";
        }
        $output .= '}' . "\n";

        file_put_contents(ROOT . 'cache/OIDC.php', $output);
    }
}