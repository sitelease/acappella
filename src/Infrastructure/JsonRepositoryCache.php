<?php

namespace CompoLab\Infrastructure;

use CompoLab\Domain\Package;
use CompoLab\Domain\Repository;
use CompoLab\Domain\RepositoryCache;

final class JsonRepositoryCache implements RepositoryCache
{
    /** @var Repository */
    private $repository;

    /** @var int */
    private $jsonOptions;

    public function __construct(Repository $repository, int $jsonOptions = JSON_PRETTY_PRINT)
    {
        $this->repository = $repository;
        $this->jsonOptions = $jsonOptions;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function addPackage(Package $package)
    {
        $this->upsertPackage($package);
    }

    public function editPackage(Package $package)
    {
        $this->upsertPackage($package);
    }

    public function removePackage(Package $package)
    {
        $this->repository->removePackage($package);
    }

    private function upsertPackage(Package $package)
    {
        $this->repository->addPackage($package);
    }

    public function refresh()
    {
        $jsonPath = $this->repository->getIndexFile();

        if (!file_put_contents($jsonPath, json_encode($this->repository, $this->jsonOptions))) {
            throw new \RuntimeException(sprintf('Impossible to save repository to %s', $jsonPath));
        }
    }

    public function count()
    {
        return count($this->repository);
    }
}
