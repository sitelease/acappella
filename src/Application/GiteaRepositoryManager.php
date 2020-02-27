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
// use Gitea\Model\Commit;
use Gitea\Model\Repository;
use Gitea\Model\Branch;
use Gitea\Model\Tag;
use Gitea\Client;
// use Gitea\ResultPager;

final class GiteaRepositoryManager
{
    /** @var RepositoryCache */
    private $repositoryCache;

    public function __construct(RepositoryCache $repositoryCache)
    {
        $this->repositoryCache = $repositoryCache;
    }

    public function registerRepository(Repository $repository)
    {
        // Catch exceptions as a branch or tag
        // may contain a version without any
        // composer.json file

        $gitea = $repository->getClient();
        $repositoryName = $repository->getFullName();

        $branches = $repository->branches();
        if ($branches) {
            print("Processing branches for ".$repositoryName."\n");
            foreach ($branches as $branch) {
                try {
                    print("Processing ".$branch->getName()."\n");
                    $this->registerBranch($branch);

                } catch (\Exception $e) {
                    continue;
                }
            }
        } else{
            throw new \RuntimeException(sprintf('Impossible to get branches from repository "%s"',
                $repositoryName));
        }

        $tags = $repository->tags();
        if ($tags) {
            print("Processing tags for ".$repositoryName."\n");
            foreach ($tags as $tag) {
                try {
                    print("Processing ".$tag->getName()."\n");
                    $this->registerTag($tag);

                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        return true;
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

    /** TODO: Update */
    public function deleteRepository(Repository $repository)
    {
        foreach ($repository->branches() as $branch) {
            $this->deleteBranch($branch);
        }

        foreach ($repository->tags() as $tag) {
            $this->deleteTag($tag);
        }
    }

    /** TODO: Update */
    public function deleteBranch(Branch $branch)
    {
        $this->repositoryCache->removePackage(
            $this->getPackageFromBranch($branch)
        );
    }

    /** TODO: Update */
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

    /** TODO: Update */
    private function getPackageFromBranch(Branch $branch): Package
    {
        $branchName = $branch->getName();

        $branch->searchRequestChain();

        $composerJson = $this->getComposerJson(
            $branch->getClient(), $branchName
        );

        $version = Version::buildFromString($branchName);

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($branch->repository, $branch->commit),
            $this->getDist($composerJson['name'], $version, $branch->commit)
        );
    }

    /** TODO: Update */
    private function getPackageFromTag(Tag $tag): Package
    {
        $composerJson = $this->getComposerJson($tag->commit);

        $version = Version::buildFromString($tag->name);

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($tag->repository, $tag->commit),
            $this->getDist($composerJson['name'], $version, $tag->commit)
        );
    }

    /** TODO: Update */
    private function getSource(Repository $repository, Commit $commit): Source
    {
        return new Source(
            new Git,
            new Url($repository->ssh_url_to_repo),
            new Reference($commit->id)
        );
    }

    /** TODO: Update */
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

    /** TODO: Update */
    private function getComposerJson(Repository $repository, string $gitRef): array
    {
        $jsonString = $client->repositories()->getRawFile(
            $repository->getOwner(),
            $repository->getName(),
            $gitRef
        );
        // ->getRawFile($commit->repository->id, 'composer.json', $commit->id)

        if (!$jsonArray = json_decode($jsonString, true)) {
            throw new \RuntimeException(sprintf('Impossible to get composer.json from repository %d (ref: %s)',
                $commit->repository->id,
                $commit->id));
        }

        if (!in_array('name', array_keys($jsonArray))) {
            throw new \RuntimeException(sprintf('Malformed composer.json from repository %d (ref: %s)',
                $commit->repository->id,
                $commit->id));
        }

        return $jsonArray;
    }

    /** TODO: Update */
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

    /** TODO: Update */
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
                    $commit->repository->id,
                    ['sha' => $commit->id]
                )
            );

        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Impossible to put content to %s (%s)', $path, $e->getMessage()));
        }
    }
}
