<?php

namespace Acappella\Application\Http;

use Acappella\Application\GiteaRepositoryManager;
use Acappella\Application\Http\Controller\GiteaController;
use Gitea\Client as Gitea;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class Routing extends RouteCollection
{
    public function __construct(Gitea $gitea, GiteaRepositoryManager $repositoryManager)
    {
        $this->add('gitea', new Route('/gitea', [
            '_controller' => [new GiteaController($gitea, $repositoryManager), 'handle']
        ]));
    }
}
