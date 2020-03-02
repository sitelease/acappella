<?php

namespace Acappella\Domain;

interface RepositoryCache extends \Countable
{
    public function getRepository(): Repository;
    public function addPackage(Package $package);
    public function editPackage(Package $package);
    public function removePackage(Package $package);
    public function refresh();
}
