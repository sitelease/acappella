<?php

namespace Acappella\Application;

use Acappella\Domain\Dist;
use Acappella\Domain\Package;
use Acappella\Domain\PackageConfiguration;
use Acappella\Domain\RepositoryCache;
use Acappella\Domain\Source;
use Acappella\Domain\Type\Git;
use Acappella\Domain\Type\Tar;
use Acappella\Domain\ValueObject\Reference;
use Acappella\Domain\ValueObject\Url;
use Acappella\Domain\ValueObject\Version;
use Acappella\Exception\AcappellaException;
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
            // print("Processing branches for ".$repositoryName."\n");
            foreach ($branches as $branch) {
                try {
                    // print("Processing ".$branch->getName()."\n");
                    $this->registerBranch($branch);

                } catch (\Exception $e) {
                    if ($e instanceof AcappellaException) {
                        // print("AcappellaException Detected");
                        throw $e;
                    } else {
                        // print("Branch could not be processed \n");
                        // print("Error message -> ".$e->getMessage()."\n");
                        continue;
                    }
                }
            }
        } else{
            throw new AcappellaException(sprintf('Impossible to get branches from repository "%s"',
                $repositoryName));
        }

        $tags = $repository->tags();
        if ($tags) {
            // print("Processing tags for ".$repositoryName."\n");
            foreach ($tags as $tag) {
                try {
                    // print("Processing ".$tag->getName()."\n");
                    $this->registerTag($tag);

                } catch (\Exception $e) {
                    if ($e instanceof AcappellaException) {
                        // print("AcappellaException Detected");
                        throw $e;
                    } else {
                        // print("Branch could not be processed \n");
                        // print("Error message -> ".$e->getMessage()."\n");
                        continue;
                    }
                }
            }
        }
    }

    public function registerBranch(Branch $branch)
    {
        // print("registerBranch() -> ");
        $this->repositoryCache->addPackage(
            $this->getPackageFromBranch($branch)
        );
    }

    public function registerTag(Tag $tag)
    {
        // print("registerTag() -> ");
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

    public function count()
    {
        $this->repositoryCache->count();
    }

    /** WIP */
    private function getPackageFromBranch(Branch $branch): Package
    {
        // print("getPackageFromBranch() -> ");
        $branchName = $branch->getName();

        $repository = $branch->searchRequestChain(Repository::class);

        if (!$repository) {
            throw new AcappellaException(sprintf('Impossible to get Repository object from branch request chain (branch: %s)',
                $branchName
            ));
        }

        $commit = $branch->getCommit();

        if (!$commit) {
            throw new AcappellaException(sprintf('Impossible to get Commit object from branch (branch: %s)',
                $branchName
            ));
        }

        $commitSha = $commit->getId();

        $composerJson = $this->getComposerJson($repository, $branchName);

        $version = Version::buildFromString($branchName);

        // print("\n");
        // print("Composer Name -> ".$composerJson['name']."\n");
        // print("branchName -> $branchName"."\n");
        // print("commitSha -> $commitSha"."\n");
        // print("version -> $version"."\n");
        // print("\n");

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($repository, $commitSha),
            $this->getDist($repository, $composerJson['name'], $version, $commitSha)
        );
    }

    /** TODO: Update */
    private function getPackageFromTag(Tag $tag): Package
    {
        // print("getPackageFromTag() -> ");
        $tagName = $tag->getName();


        $repository = $tag->searchRequestChain(Repository::class);

        if (!$repository) {
            throw new AcappellaException(sprintf('Impossible to get Repository object from tag request chain (tag: %s)',
                $tagName
            ));
        }

        $commitSha = $tag->getCommitSha();

        if (!$commitSha) {
            throw new AcappellaException(sprintf('Impossible to get commit SHA from tag (tag: %s)',
                $tagName
            ));
        }

        $composerJson = $this->getComposerJson($repository, $tagName);

        $version = Version::buildFromString($tagName);

        // print("\n");
        // print("Composer Name -> ".$composerJson['name']."\n");
        // print("tagName -> $tagName"."\n");
        // print("commitSha -> $commitSha"."\n");
        // print("version -> $version"."\n");
        // print("\n");

        return new Package(
            $composerJson['name'],
            $version,
            new PackageConfiguration($composerJson),
            $this->getSource($repository, $commitSha),
            $this->getDist($repository, $composerJson['name'], $version, $commitSha)
        );
    }

    private function getSource(Repository $repository, string $commitSha): Source
    {
        // print("getSource() -> ");
        return new Source(
            new Git,
            new Url($repository->getSshUrl()),
            new Reference($commitSha)
        );
    }

    private function getDist(Repository $repository, string $name, Version $version, string $commitSha): Dist
    {
        // print("getDist() -> ");
        $archivePath = $this->getArchivePath($repository, $name, $version, $commitSha);

        return new Dist(
            new Tar,
            $this->repositoryCache->getRepository()->getUrl($archivePath),
            new Reference($commitSha),
            $this->repositoryCache->getRepository()->getFile($archivePath)
        );
    }

    private function getComposerJson(Repository $repository, string $gitRef): array
    {
        // print("getComposerJson() -> ");
        // print("\n");
        $client = $repository->getClient();
        $owner = $repository->getOwner();
        // print("\n");
        // print("Owner -> ".$owner->getUsername()."\n");
        // print("Name -> ".$repository->getName()."\n");
        // print("Ref -> ".$gitRef."\n");
        // print("\n");
        $jsonString = $client->repositories()->getFileContents(
            $owner->getUsername(),
            $repository->getName(),
            "composer.json",
            $gitRef
        );

        if ($jsonString && is_string($jsonString)) {
            try {
                $jsonArray = json_decode($jsonString, true);
                if (!is_array($jsonArray) || !in_array('name', array_keys($jsonArray))) {
                    throw new \RuntimeException;
                }
            } catch (\Exception $e) {
                throw new \RuntimeException(sprintf('Malformed composer.json from repository %s (ref: %s)',
                    $repository->getName(),
                    $gitRef
                ));
            }
        } else {
            throw new \RuntimeException(sprintf('Impossible to get composer.json from repository %s (ref: %s)',
                $repository->getName(),
                $gitRef
            ));
        }

        return $jsonArray;
    }

    private function getArchivePath(Repository $repository, string $name, Version $version, string $commitSha): string
    {
        // print("getArchivePath() -> ");
        $archivePath = Dist::buildArchivePath($name, $version, new Reference($commitSha));

        try {
            // This will check if the archive path exists and is a file
            $this->repositoryCache->getRepository()->getFile($archivePath);

        } catch (\Exception $e) {
            $this->createArchive($repository, $archivePath, $commitSha);
        }

        return $archivePath;
    }

    private function createArchive(Repository $repository, string $path, string $commitSha)
    {
        // print("createArchive() -> ");
        $path = sprintf('%s/%s',
            $this->repositoryCache->getRepository()->getCachePath(),
            ltrim($path, '/'));

            $archive = $repository->archive($commitSha);

            if (!$archive) {
                throw new AcappellaException(sprintf('Impossible to get archive from repository %s for commit %s',
                $repository->getName(),
                $commitSha));
            }
        try {
            if (!is_dir($dir = pathinfo($path, PATHINFO_DIRNAME))) {
                mkdir($dir, 0755, true);
            }

            file_put_contents(
                $path,
                $archive
            );

        } catch (\Exception $e) {
            throw new AcappellaException(sprintf('Impossible to put content to %s (%s)', $path, $e->getMessage()));
        }
    }
}
