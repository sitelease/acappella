<?php

namespace CompoLab\Application\Cli;

use CompoLab\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SyncCommand extends Command
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
            ->setName('sync')
            ->setDescription('Sync the CompoLab cache with Gitea')
            ->setHelp('This command will list all Gitea repositories (accessible with the specified token), generate a complete packages.json based on this list and download all package archives into the web-accessible cache directory.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('List all repositories...');

        $gitea = $this->gitea;
        $repositories = $gitea->repositories()->all();

        $output->writeln(' OK');

        ProgressBar::setFormatDefinition('custom', "%message% \n%current%/%max% [%bar%] %percent:3s%% \n");
        $progress = new ProgressBar($output, count($repositories));
        $progress->setFormat('custom');

        // Progress bar redraw settings
        $progress->setRedrawFrequency(100);
        $progress->minSecondsBetweenRedraws(0.1);
        $progress->maxSecondsBetweenRedraws(0.5);

        foreach ($repositories as $repository) {
            $progress->setMessage(sprintf('Parse repository "%s"...', $repository->getFullName()));

            $this->repositoryManager->registerRepository($repository);
            $progress->advance();
        }

        $progress->setMessage('Parse repositories... OK');
        $progress->finish();

        $output->write('Persist JSON in cache...');
        // print("\n Count -> ".$this->repositoryManager->count());
        $this->repositoryManager->save();
        $output->writeln(' OK');

        $output->writeln('Finished');
    }
}
