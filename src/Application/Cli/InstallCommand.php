<?php

namespace Acappella\Application\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

final class InstallCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install and configure Acappella')
            ->setHelp('This command help to create a valid configuration, store it into the config/settings.yml file and create the empty public/packages.json file.')
            ->addOption('reset', 'r', InputOption::VALUE_NONE, 'Erase existing package index or archives')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question'); /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */

        $root = realpath(__DIR__ . '/../../..');
        $packagesJson = sprintf('%s/public/packages.json', $root);
        $packageArchives = sprintf('%s/public/archives', $root);

        if ($input->getOption('reset')) {
            $validateReset = $helper->ask($input, $output, new ConfirmationQuestion(
                'Are you sure you want to erase all repository packages (y/n)? ',
                false
            ));
            if (!$validateReset) {
                $output->writeln('Aborting installation.');
                return;
            }

            $output->write('Delete public/packages.json... ');
            unlink($packagesJson);
            $output->writeln('OK');

            $output->write('Empty public/archives... ');
            $this->rmdir($packageArchives);
            $output->writeln('OK');
        }

        $composerURL = $helper->ask($input, $output, (new Question(
            'Enter the Acappella server URL (eg. https://composer.my-website.com): '
        ))->setNormalizer(function ($value) {
            return trim((string) $value);
        })->setValidator(function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Invalid URL');
            }
            return $value;
        }));

        $composerPublicDirPath = $helper->ask($input, $output, (new Question(
            'Enter the path to Acappella `public` directory (leave empty to auto configure): ',
            sprintf('%s/public', $root)
        ))->setNormalizer(function ($value) {
            return trim((string) $value);
        })->setValidator(function ($value) {
            if (!is_dir($value)) {
                throw new \InvalidArgumentException('Invalid directory path');
            }
            return $value;
        }));

        $giteaURL = $helper->ask($input, $output, (new Question(
            'Enter your Gitea server URL (eg. https://gitea.my-website.com): '
        ))->setNormalizer(function ($value) {
            return trim((string) $value);
        })->setValidator(function ($value) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Invalid URL');
            }
            return $value;
        }));

        $giteaToken = $helper->ask($input, $output, (new Question(
            'Enter a valid Gitea authentication token (url_token method): '
        ))->setNormalizer(function ($value) {
            return trim((string) $value);
        })->setHidden(true));

        $output->write('Create config/settings.yml... ');
        file_put_contents(sprintf('%s/config/settings.yml', $root), Yaml::dump([
            'parameters' => [
                'composer.url'  => $composerURL,
                'composer.dir'  => $composerPublicDirPath,
                'gitea.url'    => $giteaURL,
                'gitea.token'  => $giteaToken,
                'gitea.method' => 'url_token',
            ]
        ]));
        $output->writeln('OK');

        if (!file_exists($packagesJson)) {
            $output->write('Create public/packages.json... ');
            file_put_contents($packagesJson, json_encode(['packages' => []]));
            $output->writeln('OK');
        }

        if (!is_writable($packagesJson) or !is_readable($packagesJson)) {
            $output->write('Make public/packages.yml writable... ');
            chmod($packagesJson, 0777);
            $output->writeln('OK');
        }

        if (!is_writable($packageArchives)) {
            $output->write('Make public/archives writable... ');
            chmod($packageArchives, 0777);
            $output->writeln('OK');
        }

        $output->writeln('Finished');
    }

    private function rmdir($dir, bool $rmCurrent = false)
    {
        foreach (array_diff(scandir($dir), ['.', '..', '.gitkeep']) as $file) {
            if (is_dir($path = "$dir/$file")) {
                $this->rmdir($path, true);
            } else {
                unlink($path);
            }
        }

        if ($rmCurrent) {
            rmdir($dir);
        }
    }
}
