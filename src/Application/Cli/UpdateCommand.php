<?php

namespace Acappella\Application\Cli;

use Acappella\Application\GiteaRepositoryManager;
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
            ->setDescription('Update a Gitea repository in Acappella')
            ->setHelp('This command will update a specific repository (tags and branches) in the packages.json file, and download associated package archives into the web-accessible cache directory.')
            ->addArgument('repository', InputArgument::REQUIRED, 'Repository name or ID (can be found from Gitea in repository settings)')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $gitea = $this->gitea;
        $arg1 = $input->getArgument('repository');

        $repository = false;
        if (ctype_digit($arg1)) {
            $repoID = (int) $arg1;
            $output->write(sprintf('Finding Gitea repository with ID of "%d"...', $repoID));
            $repository = $gitea->repositories()->getById($repoID);
        } else {
            $packageFullName = $arg1;
            $output->write(sprintf('Finding Gitea repository "%s"...', $packageFullName));
            if (strpos($packageFullName, '/') !== false) {
                $packageNameArray = explode("/", $packageFullName);
                $owner = $packageNameArray[0];
                $repoName = $packageNameArray[1];

                // Convert composer vendor name to owner name
                if ($owner === "sitelease") {
                    $owner = "Sitelease";
                }
            } else {
                $owner = "Sitelease";
                $repoName = $packageFullName;
            }
            $repository = $gitea->repositories()->getByName($owner, $repoName);
        }

        if ($repository){
            $output->writeln(' OK');

            $output->write('Updating Acappella repository package...');
            $this->repositoryManager->registerRepository($repository);
            $this->repositoryManager->save();
            $output->writeln(' OK');

            $output->writeln('Finished');
        } else {
            $output->writeln(' ERROR');
            $output->write('A problem was encountered when trying to retrieve the repository');
        }
    }
}
