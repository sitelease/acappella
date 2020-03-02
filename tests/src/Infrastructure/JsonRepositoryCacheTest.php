<?php

namespace Acappella\Tests\Infrastructure;

use Acappella\Domain\Package;
use Acappella\Domain\Repository;
use Acappella\Domain\ValueObject\Dir;
use Acappella\Domain\ValueObject\Url;
use Acappella\Infrastructure\JsonRepositoryCache;
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

        self::$cache->addPackage(Package::buildFromArray(__DIR__ . '/../../cache', array_merge($json, [
            'version'       => 'dev-master',
            'source'        => [
                'type'      => 'git',
                'url'       => 'git@gitea.my-website.com:vendor/repository.git',
                'reference' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            ],
        ])));

        self::$cache->addPackage(Package::buildFromArray(__DIR__ . '/../../cache', array_merge($json, [
            'version'       => 'dev-feature',
            'source'        => [
                'type'      => 'git',
                'url'       => 'git@gitea.my-website.com:vendor/repository.git',
                'reference' => '8c7g1iu6249c789d4b6365c0d4c1205d36498i64',
            ],
        ])));

        self::$cache->addPackage(Package::buildFromArray(__DIR__ . '/../../cache', array_merge($json, [
            'version'       => 'dev-other-feature',
            'source'        => [
                'type'      => 'git',
                'url'       => 'git@gitea.my-website.com:vendor/repository.git',
                'reference' => '1x9f6xo4297r146w9c5469c0d2w8796x65398i10',
            ],
        ])));

        $this->assertEquals(3, count(self::$cache));
    }

    /**
     *
     * @depends testAddPackage
     */
    public function testRemovePackage()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/../../data/composer.json'), true);

        self::$cache->removePackage(Package::buildFromArray(__DIR__ . '/../../cache', array_merge($json, [
            'version'       => 'dev-feature',
            'source'        => [
                'type'      => 'git',
                'url'       => 'git@gitea.my-website.com:vendor/repository.git',
                'reference' => '8c7g1iu6249c789d4b6365c0d4c1205d36498i64',
            ],
        ])));

        $this->assertEquals(2, count(self::$cache));
    }

    /**
     *
     * @depends testRemovePackage
     */
    public function testRefresh()
    {
        self::$cache->refresh();

        $json = file_get_contents(__DIR__ . '/../../cache/packages.json');

        $this->assertEquals(2, count(Repository::buildFromJson(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__ . '/../../cache'),
            $json
        )));

        $this->assertEquals(
            '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            json_decode($json, true)['packages']['vendor/repository']['dev-master']['source']['reference']
        );
    }

    public static function tearDownAfterClass()
    {
        // Clean packages for future tests
        file_put_contents(__DIR__ . '/../../cache/packages.json', '{}');
    }
}
