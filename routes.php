<?php
/**
 * Created by PhpStorm.
 * User: Peter
 * Date: 18/11/2018
 * Time: 15:12
 */

const ROUTES = [
    '.well-known' => [
        'openid-configuration' => 'openid_configuration'
    ],
    'home' => 'home',
    'registration' => 'registration',
    'authorization' => 'authorization',
    'token' => 'token',
    'jwks' => 'jwks'
];