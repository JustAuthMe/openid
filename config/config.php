<?php

if (file_exists(__DIR__ . '/host.dist.php')) {
    require_once __DIR__ . '/host.dist.php';
} else {
    require_once __DIR__ . '/host.dev.php';
}


const PROD_ENV = ENV_NAME === 'prod';

define('POST', !isset($argc) && $_SERVER['REQUEST_METHOD'] === 'POST');
define('WEBROOT', !isset($argc) ? str_replace('index.php', '', $_SERVER['SCRIPT_NAME']) : '');

const JAM_CALLBACK_DEFAULT = APP_URL . 'user/jam';
const CORE = ROOT . 'core/';
const APP = ROOT . 'app/';
const CACHE = ROOT . 'cache/';
const MODELS = APP . 'models/';
const VIEWS = APP . 'views/';
const CONTROLLERS = APP . 'controllers/';

const RELEASE_NAME = DEPLOYED_REF . '-' . DEPLOYED_COMMIT;
