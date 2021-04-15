<?php

use PitouFW\Cache\OIDC;
use PitouFW\Core\ApiCall;
use PitouFW\Core\Controller;
use PitouFW\Core\Data;
use PitouFW\Core\Utils;
use PitouFW\Model\OpenIDAuthModel;

if (!POST) {
    Controller::http405MethodNotAllowed();
    Controller::renderApiError('invalid_request', 'Only POST requests are allowed on this endpoint');
}

if (!isset($_POST['grant_type'], $_POST['code'], $_POST['redirect_uri'])) {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_request', 'grant_type, code and redirect_uri are mandatory parameters');
}

if (!in_array($_POST['grant_type'], OIDC::GRANT_TYPES_SUPPORTED)) {
    Controller::http422UnprocessableEntity();
    Controller::renderApiError('unsupported_grant_type', 'Unsupported grant type. Supported grant types are: ' . implode(', ', OIDC::GRANT_TYPES_SUPPORTED));
}

$redis = new \PitouFW\Core\Redis();
$cache_key = OpenIDAuthModel::AUTH_CODE_CACHE_PREFIX . $_POST['code'];
$params = $redis->get($cache_key, true);
if ($params === false) {
    Controller::http404NotFound();
    Controller::renderApiError('invalid_grant', 'Authorization code not found');
}

if ($_POST['redirect_uri'] !== $params['redirect_uri']) {
    Controller::http403Forbidden();
    Controller::renderApiError('invalid_redirect_uri', 'The provided redirect uri must be identical to the one passed in the authorization request');
}

$secret = $_SERVER['HTTP_AUTHORIZATION'] ??
    ($_SERVER['Authorization'] ??
        apache_request_headers()['authorization'] ?? null);
if ($secret === null) {
    $secret = $_POST['client_secret'] ?? null;
} else {
    $secret = preg_replace("#^Basic\s+#", '', $secret);
}

if ($secret === null) {
    Controller::http401Unauthorized();
    Controller::renderApiError('unauthorized_client', 'Client secret not found');
}

$apiCall = new ApiCall();
$apiCall->setUrl(JAM_API . 'data?access_token=' . $_POST['code'] . '&secret=' . $secret)->exec();
$response = $apiCall->responseObj();

switch ($apiCall->responseCode()) {
    case 200:
        $access_token = Utils::generateToken();
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        $payload = [
            'iss' => OIDC::ISSUER,
            'sub' => $response->jam_id,
            'aud' => $params['client_id'],
            'exp' => OpenIDAuthModel::AUTH_CODE_CACHE_TTL,
            'iat' => Utils::time(),
            'auth_time' => $params['auth_time']
        ];
        $jwt = base64_encode(json_encode($header)) .
            base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $jwt, $secret);
        $id_token = $jwt . '.' . $signature;

        if (isset($params['nonce'])) {
            $data['nonce'] = $params['nonce'];
        }

        $redis->set(OpenIDAuthModel::ACCESS_TOKEN_CACHE_PREFIX . $access_token, $response, OpenIDAuthModel::ACCESS_TOKEN_CACHE_TTL);

        Data::get()->add('access_token', $access_token);
        Data::get()->add('token_type', 'Bearer');
        Data::get()->add('expires_in', OpenIDAuthModel::AUTH_CODE_CACHE_TTL);
        Data::get()->add('id_token', $id_token);
        break;

    case 400:
        Controller::http400BadRequest();
        Controller::renderApiError('invalid_request', $response?->message);
        break;

    case 401:
        Controller::http401Unauthorized();
        Controller::renderApiError('unauthorized_client', $response?->message);
        break;

    case 404:
        Controller::http404NotFound();
        Controller::renderApiError('invalid_grant', $response?->message);
        break;

    default:
        Controller::http500InternalServerError();
        Controller::renderApiError('server_error', $response?->message);
}
