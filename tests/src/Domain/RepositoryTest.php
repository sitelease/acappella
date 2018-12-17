<?php

namespace CompoLab\Tests\Domain;

use CompoLab\Domain\Package;
use CompoLab\Domain\Repository;
use CompoLab\Domain\ValueObject\Dir;
use CompoLab\Domain\ValueObject\File;
use CompoLab\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    public function testBuildFromPath()
    {
        $repository = Repository::buildFromPath(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__ . '/../../cache'),
            __DIR__ . '/../../data/packages.json'
        );

        $this->assertInstanceOf(Url::class, $repository->getBaseUrl());
        $this->assertEquals('https://composer.my-website.com/foobar/baz', (string) $repository->getUrl('/foobar/baz'));
        $this->assertEquals('https://composer.my-website.com/packages.json', (string) $repository->getIndexUrl());

        $this->assertInstanceOf(Dir::class, $repository->getCachePath());
        $this->assertInstanceOf(File::class, $repository->getIndexFile());

        $this->assertEquals(4, count($repository));
        $this->assertInstanceOf(Package::class, $repository->getPackages()[0]);
    }
}
