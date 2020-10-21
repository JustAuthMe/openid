<?php

namespace PitouFW\Core;

abstract class Controller {
    public static function __callStatic(string $name, array $arguments): void {
        if (substr($name, 0, 4) == 'http') {
            $errCode = substr($name, 4, 3);
            $errMsg = preg_replace("#([A-Z])#", " $1", substr($name, 7));
            header("HTTP/1.1 $errCode$errMsg");
        }
    }

    public static function renderView(string $path, ?string $layout = 'json.php'): void {
        if (!is_null($layout)) {
            $layout = file_exists(VIEWS . $layout) ? VIEWS . $layout : VIEWS . 'mainView.php';
        }

        $file = VIEWS.$path.'.php';
        if (file_exists($file) ) {
            $appView = $file;
            $dataToExtract = Data::get()->getData();
            extract($dataToExtract);
            if (!is_null($layout)) {
                require_once $layout;
            }
            else {
                require_once $appView;
            }
        }
        else {
            self::http500InternalServerError();
        }
    }

    public static function renderApiError(string $code, ?string $description = null, ?string $state = null): void {
        Logger::logError($code . ': ' . $description);
        Data::get()->add('error', $code);
        if ($description !== null) {
            Data::get()->add('error_description', $description);
        }
        if ($state !== null) {
            Data::get()->add('state', $state);
        }
        Controller::renderView('json', null);
        die;
    }

    public static function renderApiSuccess(): void {
        Controller::renderView('json', null);
        die;
    }

    public static function sendNoCacheHeaders(): void {
        header('Cache-Control: no-store');
        header('Pragma: no-cache');
    }
}
