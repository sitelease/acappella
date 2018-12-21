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

final class BuildCommand extends Command
{
    /** @var Gitlab */
    private $gitlab;

    /** @var GitlabRepositoryManager */
    private $manager;

    public function __construct(Gitlab $gitlab, GitlabRepositoryManager $manager)
    {
        $this->gitlab = $gitlab;
        $this->manager = $manager;

        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build the composer cache from Git')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Build composer server cache');

        $pager = new ResultPager($this->gitlab);

        $output->write('List all projects...');
//        $projects = $pager->fetchall($this->gitlab->projects, 'all');
        $projects = $pager->fetch($this->gitlab->projects, 'all');
        $output->writeln(' OK');

        ProgressBar::setFormatDefinition('custom', "%message% \n%current%/%max% [%bar%] %percent:3s%% \n");
        $progress = new ProgressBar($output, count($projects));
        $progress->setFormat('custom');

        foreach ($projects as $project) {
            $project = Project::fromArray($this->gitlab, $project);

            $progress->setMessage(sprintf('Parse project "%s"...', $project->name));

            foreach ($project->branches() as $branch) {
                try {
                    $this->manager->registerBranch($branch);

                } catch (\Exception $e) {
                    continue;
                }
            }

            foreach ($project->tags() as $tag) {
                try {
                    $this->manager->registerTag($tag);

                } catch (\Exception $e) {
                    continue;
                }
            }

            $progress->advance();

        }
        $progress->setMessage('Parse projects... OK');
        $progress->finish();

        $output->write('Persist JSON in cache...');
        $this->manager->save();
        $output->writeln(' OK');

        $output->writeln('Finished');
    }
}
