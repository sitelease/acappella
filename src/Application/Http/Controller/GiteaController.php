<?php

namespace CompoLab\Application\Http\Controller;

use CompoLab\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Gitea\Model\Repository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class GiteaController
{
    /** @var Gitea */
    private $gitea;

    /** @var GiteaRepositoryManager */
    private $repositoryManager;

    public function __construct(Gitea $gitea, GiteaRepositoryManager $repositoryManager)
    {
        $this->gitea = $gitea;
        $this->repositoryManager = $repositoryManager;
    }

    public function handle(Request $request): Response
    {
        $event = json_decode($request->getContent(), true);

        if (!isset($event['repository_id'])) {
            throw new BadRequestHttpException('Missing repository_id from body');
        }

        if (!isset($event['event_name'])) {
            throw new BadRequestHttpException('Missing event_name from body');
        }

        if (!isset($event['repository_id']) or !$repository = Repository::fromArray(
            $this->gitea,
            $this->gitea->repositories()->show($event['repository_id']))
        ){
            throw new BadRequestHttpException('Impossible te retrieve a Gitea repository from the request');
        }

        switch ($event['event_name']) {
            case 'repository_destroy':
                $this->repositoryManager->deleteRepository($repository);
                break;

            case 'push':
            case 'tag_push':
                $this->repositoryManager->registerRepository($repository);
                break;

            default: return new JsonResponse([
                'status' => 200,
                'message' => 'CompoLab has NOT handled the Gitea event',
                'repository_id' => $event['repository_id'],
                'event_name' => $event['event_name'],
            ]);
        }

        $this->repositoryManager->save();

        return new JsonResponse([
            'status' => 200,
            'message' => 'CompoLab has successfully handled the Gitea event',
            'repository_id' => $event['repository_id'],
            'event_name' => $event['event_name'],
        ]);
    }
}
