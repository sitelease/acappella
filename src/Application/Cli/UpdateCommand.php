<?php

namespace CompoLab\Application\Cli;

use CompoLab\Application\GitlabRepositoryManager;
use Gitlab\Client as Gitlab;
use Gitlab\Model\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateCommand extends Command
{
    /** @var Gitlab */
    private $gitlab;

    /** @var GitlabRepositoryManager */
    private $repositoryManager;

    public function __construct(Gitlab $gitlab, GitlabRepositoryManager $repositoryManager)
    {
        $this->gitlab = $gitlab;
        $this->repositoryManager = $repositoryManager;

        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Update a GitLab project in CompoLab')
            ->setHelp('This command will update a specific project (tags and branches) in the packages.json file, and download associated package archives into the web-accessible cache directory.')
            ->addArgument('project', InputArgument::REQUIRED, 'Project ID (can be found from GitLab in project settings')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = $input->getArgument('project');

        $output->write(sprintf('Find project %d from GitLab...', $projectId));

        $project = (new Project($projectId, $this->gitlab))->show();
        if (!$project) {
            throw new InvalidArgumentException(sprintf('Impossible to retrieve Gitlab project %d', $projectId));
        }

        $output->writeln(' OK');

        $output->write('Update project in CompoLab...');

        $this->repositoryManager->registerProject($project);
        $this->repositoryManager->save();

        $output->writeln(' OK');

        $output->writeln('Finished');
    }
}
