<?php

namespace CompoLab\Tests\Utils;

use GuzzleHttp\Psr7\Response;
use Http\Client\HttpClient;
use Psr\Http\Message\RequestInterface;

class DummyHttpClient implements HttpClient
{
    /**
     *
     * @var string
     */
    private $body;

    public function __construct(string $body = null)
    {
        $this->body = $body;
    }

    public function sendRequest(RequestInterface $request)
    {
        return new Response(200, [], $this->body);
    }
}
