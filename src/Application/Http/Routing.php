<?php

namespace CompoLab\Application\Http;

use CompoLab\Application\GitlabRepositoryManager;
use CompoLab\Application\Http\Controller\GitlabController;
use Gitlab\Client as Gitlab;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class Routing extends RouteCollection
{
    public function __construct(Gitlab $gitlab, GitlabRepositoryManager $repositoryManager)
    {
        $this->add('gitlab', new Route('/gitlab', [
            '_controller' => [new GitlabController($gitlab, $repositoryManager), 'handle']
        ]));
    }
}
