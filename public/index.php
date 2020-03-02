<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Acappella\Application\Http\Kernel as AcappellaKernel;
use Acappella\Infrastructure\Services;
use Symfony\Component\HttpFoundation\Request;

$services = Services::getInstance();

$kernel = new AcappellaKernel(
    $services->gitea,
    $services->manager
);

$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
