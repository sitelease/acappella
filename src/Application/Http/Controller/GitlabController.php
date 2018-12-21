<?php

namespace CompoLab\Application\Http\Controller;

use CompoLab\Application\GitlabRepositoryManager;
use Gitlab\Client as Gitlab;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class GitlabController
{
    /** @var Gitlab */
    private $gitlab;

    /** @var GitlabRepositoryManager */
    private $repositoryManager;

    public function __construct(Gitlab $gitlab, GitlabRepositoryManager $repositoryManager)
    {
        $this->gitlab = $gitlab;
        $this->repositoryManager = $repositoryManager;
    }

    public function handle(Request $request): Response
    {
        $event = json_decode($request->getContent(), true);

        if (!isset($event['project_id'])) {
            throw new BadRequestHttpException('Missing project_id from body');
        }

        if (!isset($event['event_name'])) {
            throw new BadRequestHttpException('Missing event_name from body');
        }

        if (!in_array($event['event_name'], ['push', 'tag_push'])) {
            return new JsonResponse([
                'status' => 200,
                'message' => 'CompoLab has NOT handled the Gitlab event',
                'project_id' => $event['project_id'],
                'event_name' => $event['event_name'],
            ]);
        }


        return new JsonResponse([
            'status' => 200,
            'message' => 'CompoLab has successfully handled the Gitlab event',
            'project_id' => $event['project_id'],
            'event_name' => $event['event_name'],
        ]);
    }
}
