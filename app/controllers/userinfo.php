<?php

use PitouFW\Core\Controller;
use PitouFW\Core\Data;
use PitouFW\Model\OpenIDAuthModel;

$params = !empty($_POST) ? $_POST : $_GET;

$access_token = $_SERVER['HTTP_AUTHORIZATION'] ??
    ($_SERVER['Authorization'] ??
        apache_request_headers()['authorization'] ?? null);
if ($access_token === null) {
    $access_token = $params['access_token'] ?? null;
} else {
    $access_token = preg_replace("#^Bearer\s+#", '', $access_token);
}

if ($access_token === null) {
    Controller::http400BadRequest();
    header('WWW-Authenticate: error="invalid_token", error_description="Access token not found"');
    Controller::renderApiError('invalid_token', 'Access_token not found');
}

$redis = new \PitouFW\Core\Redis();
$cache_key = OpenIDAuthModel::ACCESS_TOKEN_CACHE_PREFIX . $access_token;
$params = $redis->get($cache_key, true);
if ($params === false) {
    Controller::http404NotFound();
    header('WWW-Authenticate: error="invalid_token", error_description="Access token does not exists or has expired"');
    Controller::renderApiError('invalid_token', 'Access token does not exists or has expired');
}

$data = ['sub' => $params['jam_id']];
if (isset($params['email'])) {
    $data['email'] = $params['email'];
    $data['email_verified'] = true;
}
if (isset($params['firstname'])) {
    $data['given_name'] = $params['firstname'];
    $data['name'] = $params['firstname'];
}
if (isset($params['lastname'])) {
    $data['family_name'] = $params['lastname'];
    $data['name'] = trim(($data['name'] ?? '') . ' ' . $data['lastname']);
}
if (isset($params['birthdate'])) {
    $data['birthdate'] = $params['birthdate'];
}
if (isset($params['birthlocation'])) {
    $data['birth_location'] = $params['birth_location'];
}
if (isset($params['avatar'])) {
    $data['picture'] = $params['avatar'];
}
if (isset($params['address1'])) {
    $data['address'] = [
        'formatted' => $params['address1'] . ', ',
        'street_address' => $params['address1'] . ', '
    ];
}
if (isset($params['address2'])) {
    @$data['address']['formatted'] .= $params['address2'] . ', ';
    @$data['address']['street_address'] .= $params['address_2'];
}
if (isset($params['postal_code'])) {
    @$data['address']['formatted'] .= $params['postal_code'] . ' ';
    $data['address']['postal_code'] = $params['postal_code'];
}
if (isset($params['city'])) {
    @$data['address']['formatted'] .= $params['city'] . ', ';
    $data['address']['locality'] = $params['city'];
}
if (isset($params['state'])) {
    @$data['address']['formatted'] .= $params['state'] . ', ';
    $data['address']['region'] = $params['state'];
}
if (isset($params['country'])) {
    @$data['address']['formatted'] .= $params['country'];
    $data['address']['country'] = $params['country'];
}
if (isset($data['address'])) {
    $data['address']['formatted'] = trim($data['address']['formatted'], ', ');
    $data['address']['street_address'] = trim($data['address']['street_address'], ', ');
}

Data::get()->setData($data);
