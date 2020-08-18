<?php

/**
 * Application entrypoint.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

date_default_timezone_set('UTC');

$app = new Mini\App();

$response = $app->run($request = Mini\Http\Request::createFromGlobals());
$response->send();
