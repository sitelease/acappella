<?php

namespace Acappella\Application\Http\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ExceptionController
{
    public function handle(FlattenException $exception): Response
    {
        return new JsonResponse([
            'status' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
        ], $exception->getStatusCode());
    }
}
