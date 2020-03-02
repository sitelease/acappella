<?php

namespace CompoLab\Application\Cli;

use CompoLab\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Gitea\Model\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends Command
{
    /** @var Gitea */
    private $gitea;

    /** @var GiteaRepositoryManager */
    private $repositoryManager;

    public function __construct(Gitea $gitea, GiteaRepositoryManager $repositoryManager)
    {
        $this->gitea = $gitea;
        $this->repositoryManager = $repositoryManager;

        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update a Gitea repository in CompoLab')
            ->setHelp('This command will update a specific repository (tags and branches) in the packages.json file, and download associated package archives into the web-accessible cache directory.')
            ->addArgument('repository', InputArgument::REQUIRED, 'Repository ID (can be found from Gitea in repository settings')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $repositoryId = (int) $input->getArgument('repository');
        $output->write(sprintf('Find repository %d from Gitea...', $repositoryId));
        $repository = (new Repository($repositoryId, $this->gitea))->show();
        $output->writeln(' OK');

        $output->write('Update repository in CompoLab...');
        $this->repositoryManager->registerRepository($repository);
        $this->repositoryManager->save();
        $output->writeln(' OK');

        $output->writeln('Finished');
    }
}
