<?php

namespace CompoLab\Application\Http\Controller;

use CompoLab\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Gitea\PushEvent;
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
        $client = $this->getClient();
        $secret = $client->getPushEventSecret();
        $requestServer = $request->server->all();
        $requestBody = $request->getContent();


        $validRequest = $event::validateRequest($requestServer, $requestBody, $secret);

        if ($validRequest) {
            $event = PushEvent::fromJson(
                $client,
                $client,
                json_decode($requestBody)
            );

            $repository = $event->getRepository();

            if (!$repository) {
                throw new BadRequestHttpException('No repository data found in the body');
            }

            if (!$repository->getId()) {
                throw new BadRequestHttpException('No repository id found in the body');
            }

            if (!$repository->getFullName()) {
                throw new BadRequestHttpException('No repository full_name found in the body');
            }

            // $repositoryObj = Repository::fromArray(
            //     $client,
            //     $client->repositories()->getByName($repository->getFullName())
            // );
            // if (!$repositoryObj){
            //     throw new BadRequestHttpException('Impossible to retrieve a Gitea repository from the request');
            // }

            // switch ($event['event_name']) {
            //     case 'repository_destroy':
            //         $this->repositoryManager->deleteRepository($repository);
            //         break;

            //     case 'push':
            //     case 'tag_push':
            //         $this->repositoryManager->registerRepository($repository);
            //         break;

            //     default: return new JsonResponse([
            //         'status' => 200,
            //         'message' => 'CompoLab has NOT handled the Gitea event',
            //         'repository_id' => $event['repository_id'],
            //         'event_name' => $event['event_name'],
            //     ]);
            // }

            $this->repositoryManager->registerRepository($repository);
            $this->repositoryManager->save();

            return new JsonResponse([
                'status' => 200,
                'message' => 'CompoLab has successfully handled the Gitea event',
                'repository_id' => $repository->getId(),
                'event_name' => $repository->getFullName(),
            ]);
        }
    }

    public function getClient()
    {
        return $this->gitea;
    }

    public function setClient(object $object): self
    {
        $this->gitea = $object;
        return $this;
    }
}
