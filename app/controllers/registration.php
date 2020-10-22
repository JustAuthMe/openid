<?php

use PitouFW\Cache\OIDC;
use PitouFW\Core\ApiCall;
use PitouFW\Core\Controller;
use PitouFW\Core\Data;

if (!POST) {
    Controller::http405MethodNotAllowed();
    Controller::renderApiError('invalid_method', 'Only POST requests are allowed on this endpoint');
}

$license_key = $_GET['license_key'] ??
    ($_GET['access_token'] ??
        $_POST['access_token'] ?? null);

if ($license_key === null) {
    $license_key = preg_replace("#^Bearer\s+#", '',
        $_SERVER['HTTP_AUTHORIZATION'] ??
            ($_SERVER['Authorization'] ??
                apache_request_headers()['authorization'] ?? ''
            )
    );
    $license_key = $license_key === '' ? null : $license_key;
}

$apiCall = new ApiCall();
$apiCall->setUrl(JAM_API . 'license/' . $license_key)
    ->setCustomHeader(['X-Access-Token: ' . JAM_INTERNAL_API_KEY])
    ->exec();

if ($apiCall->responseCode() !== 200) {
    Controller::http401Unauthorized();
    Controller::renderApiError('invalid_license_key', 'This license key is invalid or has already been used.');
}

if (!isset($_POST['redirect_uris']) || !is_array($_POST['redirect_uris'])) {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_redirect_uri', 'required_uris must be an array and contains at least one valid URI');
}

$redirect_url = $_POST['redirect_uris'][0];
if (strpos($redirect_url, 'https://') !== 0) {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_redirect_uri', 'Redirect URIs must start by https:// scheme');
}

$domain = parse_url($redirect_url, PHP_URL_HOST);
$client_name = $domain;
$client_uri = 'https://' . $domain . '/';

if (isset($_POST['response_types'])) {
    if (!is_array($_POST['response_types'])) {
        Controller::http400BadRequest();
        Controller::renderApiError('invalid_response_type', 'response_types must be an array');
    } elseif (!in_array('code', $_POST['response_types'])) {
        Controller::http400BadRequest();
        Controller::renderApiError('invalid_response_type', 'Only "code" response_type is supported');
    }
}

if (isset($_POST['grant_types'])) {
    if (!is_array($_POST['grant_types'])) {
        Controller::http400BadRequest();
        Controller::renderApiError('invalid_grant_type', 'grant_types must be an array');
    } elseif (!in_array('authorization_code', $_POST['response_types'])) {
        Controller::http400BadRequest();
        Controller::renderApiError('invalid_grant_type', 'Only "authorization_code" grant_type is supported');
    }
}

// TODO : handle contacts

$client_name = $_POST['client_name'] ?? $domain;
$logo_uri = $_POST['logo_uri'] ?? 'https://static.justauth.me/medias/client.png';
$client_uri = $_POST['client_uri'] ?? $client_uri;

if (isset($_POST['subject_type']) && $_POST['subject_type'] !== 'pairwise') {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_subject_type', 'Only pairwise Subject type is supported');
}

if (isset($_POST['token_endpoint_auth_method']) && !in_array($_POST['token_endpoint_auth_method'], OIDC::TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED)) {
    Controller::http400BadRequest();
    Controller::renderApiError('invalid_token_endpoint_auth_method', 'The supported authentication methods are: ' .
        json_encode(OIDC::TOKEN_ENDPOINT_AUTH_METHODS_SUPPORTED));
}

$apiCall = new ApiCall();
$apiCall->setUrl(JAM_API . 'client_app')
    ->setCustomHeader(['X-Access-Token: ' . JAM_INTERNAL_API_KEY])
    ->setMethod('POST')
    ->setPostParams([
        'domain' => $domain,
        'name' => $client_name,
        'logo' => $logo_uri,
        'redirect_url' => $redirect_url,
        'data' => json_encode(['email!', 'firstname', 'lastname', 'birthdate', 'avatar'])
    ])
    ->exec();
$client_app = clone $apiCall->responseObj()->client_app;

if ($apiCall->responseCode() !== 200) {
    header($apiCall->responseHeader()['Status']);
    Controller::renderApiError('backend_error', $apiCall->responseObj()->message);
}

$apiCall = new ApiCall();
$apiCall->setUrl(JAM_API . 'license/' . $license_key)
    ->setCustomHeader(['X-Access-Token: ' . JAM_INTERNAL_API_KEY])
    ->setMethod('PUT')
    ->exec();

Data::get()->add('client_id', $client_app->app_id);
Data::get()->add('client_secret', $client_app->secret);
Data::get()->add('client_id_issued_at', time());
Data::get()->add('client_secret_expires_at', 0);
Data::get()->add('client_name', $client_name);
Data::get()->add('client_uri', $client_uri);
Data::get()->add('logo_uri', $logo_uri);
Data::get()->add('redirect_uris', [$redirect_url]);
if (isset($_POST['response_types'])) {
    Data::get()->add('response_types', $_POST['response_types']);
}
if (isset($_POST['subject_type'])) {
    Data::get()->add('subject_type', $_POST['subject_type']);
}
if (isset($_POST['token_endpoint_auth_method'])) {
    Data::get()->add('token_endpoint_auth_method', $_POST['token_endpoint_auth_method']);
}
