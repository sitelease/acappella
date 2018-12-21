<?php

namespace CompoLab\Application\Http;

use CompoLab\Application\GitlabRepositoryManager;
use CompoLab\Application\Http\Controller\ExceptionController;
use Gitlab\Client as Gitlab;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

final class Kernel extends HttpKernel
{
    public function __construct(Gitlab $gitlab, GitlabRepositoryManager $repositoryManager)
    {
        $matcher = new UrlMatcher(new Routing($gitlab, $repositoryManager), new RequestContext);

        $dispatcher = new EventDispatcher;
        $dispatcher->addSubscriber(new ExceptionListener([ExceptionController::class, 'handle']));
        $dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack));

        parent::__construct($dispatcher, new ControllerResolver, new RequestStack, new ArgumentResolver);
    }
}
