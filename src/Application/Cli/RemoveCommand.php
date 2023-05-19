<?php

namespace Acappella\Application\Cli;

use Acappella\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Gitea\Model\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RemoveCommand extends Command
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
            ->setName('remove')
            ->setDescription('Remove a Gitea repository in Acappella')
            ->setHelp(
                'This command will remove a specific package from the'
                .' packages.json file, and delete any associated package archives in the'
                .' web-accessible cache directory.'
            )
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'The name of the composer package'
            )
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'The package version to remove',
                null
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $gitea = $this->gitea;

        $repository = $input->getArgument('repository');
        $version = $input->getArgument('version');

        try {
            $output->writeln(' OK');

            $output->write('Removing Acappella composer package...');
            $this->repositoryManager->removePackage($repository, $version);
            $this->repositoryManager->save();
            $output->writeln(' OK');

            $output->writeln('Finished');
        } catch (\Throwable $e) {
            $output->writeln(' ERROR');
            $output->write('A problem was encountered when trying to remove the package');
        }
    }
}
