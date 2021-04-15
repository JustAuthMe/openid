<?php

use PitouFW\Cache\OIDC;
use PitouFW\Core\Controller;
use PitouFW\Core\Request;
use PitouFW\Core\Utils;
use PitouFW\Model\OpenIDAuthModel;

function authorizationError(string $error, string $error_description = ''): void {
    global $params;

    $crafted_url = $params['redirect_uri'] .
        (str_contains('?', $params['redirect_uri']) ? '&' : '?') .
        'error=' . $error .
        (!empty($error_description) ? '&error_description=' . $error_description : '') .
        (isset($params['state']) ? '&state=' . $params['state'] : '');

    header('location: ' . $crafted_url);
    die;
}

if (Request::get()->getArg(1) === 'callback') {
    $redis = new \PitouFW\Core\Redis();
    $cache_key = OpenIDAuthModel::AUTH_ID_CACHE_PREFIX . $_GET['auth_id'];
    $params = $redis->get($cache_key, true);
    if ($params === false) {
        Controller::http404NotFound();
        Controller::renderApiError('server_error', 'OpenID authentication identifier not found');
    }

    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 403:
                Controller::http403Forbidden();
                authorizationError('access_denied', 'client_id or redirect_uri does not match any valid client');
                break;

            default:
                Controller::http500InternalServerError();
                authorizationError('server_error', 'An unknown error occured during authorization process');
        }
    }

    if (!isset($_GET['auth_id'], $_GET['access_token'])) {
        Controller::http400BadRequest();
        Controller::renderApiError('server_error', 'auth_id and access_token are mandatory parameters');
    }

    $params['auth_time'] = Utils::time();
    OpenIDAuthModel::saveAuthCode($_GET['access_token'], $params);
    $redis->del($cache_key);

    header('location: ' . $params['redirect_uri'] .
        (str_contains('?', $params['redirect_uri']) ? '&' : '?') .
        'code=' . $_GET['access_token'] .
        (isset($params['state']) ? '&state=' . $params['state'] : '')
    );
    die;
}

$params = !empty($_POST) ? $_POST : $_GET;

if (isset($params['prompt']) && $params['prompt'] === 'none') {
    Controller::http422UnprocessableEntity();
    Controller::renderApiError('interaction_required', 'JustAuthMe login needs to interact with the end-user every single time');
}

if (!isset($params['redirect_uri'])) {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_request_uri', null, $params['state'] ?? '');
}

if (!isset($params['scope'], $params['response_type'], $params['client_id'])) {
    Controller::http400BadRequest();
    authorizationError('invalid_request', 'scope, response_type and client_id are mandatory parameters');
}

$scopes = explode(',', $params['scope']);
if (!in_array('openid', $scopes)) {
    Controller::http422UnprocessableEntity();
    authorizationError('invalid_scope', '"openid" scope is mandatory');
}

if (!in_array($params['response_type'], OIDC::RESPONSE_TYPES_SUPPORTED)) {
    Controller::http422UnprocessableEntity();
    authorizationError('unsupported_response_type', 'Supported response types are: ' . implode(', ', OIDC::RESPONSE_TYPES_SUPPORTED));
}

$openid_auth_id = Utils::generateToken();
OpenIDAuthModel::saveAuthId($openid_auth_id, $params);

$openid_auth_hash = OpenIDAuthModel::hashAuthId($openid_auth_id);
header('location: ' . JAM_CORE . 'auth?app_id=' . $params['client_id'] .
    '&redirect_url=' . $params['redirect_uri'] .
    '&openid_auth=' . $openid_auth_id .
    '&openid_hash=' . $openid_auth_hash
);
die;
