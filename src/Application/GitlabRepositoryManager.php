<?php

namespace CompoLab\Application;

use CompoLab\Domain\Dist;
use CompoLab\Domain\Package;
use CompoLab\Domain\PackageConfiguration;
use CompoLab\Domain\RepositoryCache;
use CompoLab\Domain\Source;
use CompoLab\Domain\Type\Git;
use CompoLab\Domain\Type\Tar;
use CompoLab\Domain\ValueObject\Reference;
use CompoLab\Domain\ValueObject\Url;
use CompoLab\Domain\ValueObject\Version;
use Gitlab\Client as Gitlab;
use Gitlab\Model\Branch;
use Gitlab\Model\Commit;
use Gitlab\Model\Project;
use Gitlab\Model\Tag;

final class GitlabRepositoryManager
{
    /** @var RepositoryCache */
    private $repositoryCache;

    /** @var Gitlab */
    private $gitlab;

    public function __construct(RepositoryCache $repositoryCache, Gitlab $gitlab)
    {
        $this->repositoryCache = $repositoryCache;
        $this->gitlab = $gitlab;
    }

    public function registerProject(Project $project)
    {
        // Catch exceptions as a branch or tag
        // may contain a version without any
        // composer.json file

        foreach ($project->branches() as $branch) {
            try {
                $this->registerBranch($branch);

            } catch (\Exception $e) {
                continue;
            }
        }

        foreach ($project->tags() as $tag) {
            try {
                $this->registerTag($tag);

            } catch (\Exception $e) {
                continue;
            }
        }
    }

    public function registerBranch(Branch $branch)
    {
        $this->repositoryCache->addPackage(
            $this->getPackageFromBranch($branch)
        );
    }

    public function registerTag(Tag $tag)
    {
        $this->repositoryCache->addPackage(
            $this->getPackageFromTag($tag)
        );
    }

    public function deleteBranch(Branch $branch)
    {
        $this->repositoryCache->removePackage(
            $this->getPackageFromBranch($branch)
        );
    }

    public function deleteTag(Tag $tag)
    {
        $this->repositoryCache->removePackage(
            $this->getPackageFromTag($tag)
        );
    }

    public function save()
    {
        $this->repositoryCache->refresh();
    }

    private function getPackageFromBranch(Branch $branch): Package
    {
        $composerJson = $this->getComposerJson($branch->commit);

        $version = Version::buildFromString($branch->name);

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($branch->project, $branch->commit),
            $this->getDist($composerJson['name'], $version, $branch->commit)
        );
    }

    private function getPackageFromTag(Tag $tag): Package
    {
        $composerJson = $this->getComposerJson($tag->commit);

        $version = Version::buildFromString($tag->name);

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($tag->project, $tag->commit),
            $this->getDist($composerJson['name'], $version, $tag->commit)
        );
    }

    private function getSource(Project $project, Commit $commit): Source
    {
        return new Source(
            new Git,
            new Url($project->ssh_url_to_repo),
            new Reference($commit->id)
        );
    }

    private function getDist(string $name, Version $version, Commit $commit): Dist
    {
        $archivePath = $this->getArchivePath($name, $version, $commit);

        return new Dist(
            new Tar,
            $this->repositoryCache->getRepository()->getUrl($archivePath),
            new Reference($commit->id),
            $this->repositoryCache->getRepository()->getFile($archivePath)
        );
    }

    private function getComposerJson(Commit $commit): array
    {
        $jsonString = (string) $commit
            ->getClient()
            ->repositoryFiles()
            ->getRawFile($commit->project->id, 'composer.json', $commit->id)
        ;

        if (!$jsonArray = json_decode($jsonString, true)) {
            throw new \RuntimeException(sprintf('Impossible to get composer.json from project %d (ref: %s)',
                $commit->project->id,
                $commit->id));
        }

        if (!in_array('name', array_keys($jsonArray))) {
            throw new \RuntimeException(sprintf('Malformed composer.json from project %d (ref: %s)',
                $commit->project->id,
                $commit->id));
        }

        return $jsonArray;
    }

    private function getArchivePath(string $name, Version $version, Commit $commit): string
    {
        $archivePath = Dist::buildArchivePath($name, $version, new Reference($commit->id));

        try {
            // This will check if the archive path exists and is a file
            $this->repositoryCache->getRepository()->getFile($archivePath);

        } catch (\Exception $e) {
            $this->createArchive($archivePath, $commit);
        }

        return $archivePath;
    }

    private function createArchive(string $path, Commit $commit)
    {
        $path = sprintf('%s/%s',
            $this->repositoryCache->getRepository()->getCachePath(),
            ltrim($path, '/'));

        try {
            if (!is_dir($dir = pathinfo($path, PATHINFO_DIRNAME))) {
                mkdir($dir, 0755, true);
            }

            file_put_contents(
                $path,
                $commit->getClient()->repositories()->archive(
                    $commit->project->id,
                    ['ref' => $commit->id]
                )
            );

        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Impossible to put content to %s (%s)', $path, $e->getMessage()));
        }
    }
}
