<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CompoLab\Application\Http\Kernel as CompoLabKernel;
use CompoLab\Infrastructure\Services;
use Symfony\Component\HttpFoundation\Request;

$services = Services::getInstance();

$kernel = new CompoLabKernel(
    $services->gitea,
    $services->manager
);

$request = Request::createFromGlobals();

$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
