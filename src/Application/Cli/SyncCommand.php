<?php

namespace CompoLab\Application\Cli;

use CompoLab\Application\GitlabRepositoryManager;
use Gitlab\Client as Gitlab;
use Gitlab\Model\Project;
use Gitlab\ResultPager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncCommand extends Command
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
            ->setName('sync')
            ->setDescription('Sync the CompoLab cache with GitLab')
            ->setHelp('This command will list all GitLab projects (accessible with the specified token), generate a complete packages.json based on this list and download all package archives into the web-accessible cache directory.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('List all projects...');

        $pager = new ResultPager($this->gitlab);
        $projects = $pager->fetchall($this->gitlab->projects, 'all');

        $output->writeln(' OK');

        ProgressBar::setFormatDefinition('custom', "%message% \n%current%/%max% [%bar%] %percent:3s%% \n");
        $progress = new ProgressBar($output, count($projects));
        $progress->setFormat('custom');

        foreach ($projects as $project) {
            $progress->setMessage(sprintf('Parse project "%s"...', $project->name));

            $this->repositoryManager->registerProject(
                Project::fromArray($this->gitlab, $project)
            );

            $progress->advance();
        }

        $progress->setMessage('Parse projects... OK');
        $progress->finish();

        $output->write('Persist JSON in cache...');
        $this->repositoryManager->save();
        $output->writeln(' OK');

        $output->writeln('Finished');
    }
}
