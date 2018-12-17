<?php

namespace CompoLab\Tests\Infrastructure;

use CompoLab\Domain\Package;
use CompoLab\Domain\Repository;
use CompoLab\Domain\ValueObject\Dir;
use CompoLab\Domain\ValueObject\Url;
use CompoLab\Infrastructure\JsonRepositoryCache;
use PHPUnit\Framework\TestCase;

final class JsonRepositoryCacheTest extends TestCase
{
    /** @var JsonRepositoryCache */
    private static $cache;

    public static function setUpBeforeClass()
    {
        self::$cache = new JsonRepositoryCache(new Repository(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__ . '/../../cache')
        ));
    }

    public function testGetRepository()
    {
        self::assertInstanceOf(Repository::class, self::$cache->getRepository());
    }

    public function testAddPackage()
    {
        $this->assertEquals(0, count(self::$cache));

        $json = json_decode(file_get_contents(__DIR__ . '/../../data/composer.json'), true);

        $package = Package::buildFromArray(__DIR__ . '/../../cache', array_merge($json, [
            'version'       => 'dev-master',
            'source'        => [
                'type'      => 'git',
                'url'       => 'git@gitlab.my-website.com:vendor/project.git',
                'reference' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            ],
        ]));

        self::$cache->addPackage($package);

        $this->assertEquals(1, count(self::$cache));
    }

    /**
     *
     * @depends testAddPackage
     */
    public function testRefresh()
    {
        self::$cache->refresh();

        $json = json_decode(file_get_contents(__DIR__ . '/../../cache/packages.json'), true);

        $this->assertEquals(
            '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            $json['packages']['vendor/project']['dev-master']['source']['reference']
        );
    }

    public static function tearDownAfterClass()
    {
        // Clean packages for future tests
        file_put_contents(__DIR__ . '/../../cache/packages.json', '{}');
    }
}
