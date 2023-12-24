<?php

namespace Acappella\Tests\Domain;

use Acappella\Domain\Dist;
use Acappella\Domain\Repository;
use Acappella\Domain\ValueObject\Dir;
use Acappella\Domain\ValueObject\Reference;
use Acappella\Domain\ValueObject\Url;
use Acappella\Domain\ValueObject\Version;
use PHPUnit\Framework\TestCase;

final class DistTest extends TestCase
{
    private static $repository;

    public static function setUpBeforeClass()
    {
        self::$repository = new Repository(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__.'/../../cache')
        );
    }

    public function testBuildFromArray()
    {
        $dist = Dist::buildFromArray(__DIR__.'/../../cache', 'vendor/repository', Version::buildFromString('v1.2.3'), [
            'type' => 'tar',
            'url' => 'https://composer.my-website.com/archives/vendor/repository/v1.2.3/6a6e0ea9479c821d4b5728c0d3c9840e71085e82.tar.gz',
            'reference' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            'localPath' => __DIR__.'/../../cache/archives/vendor/repository/v1.2.3/6a6e0ea9479c821d4b5728c0d3c9840e71085e82.tar.gz',
        ]);

        $array = $dist->_toArray();

        $this->assertEquals('tar', $array['type']);
        $this->assertEquals('6a6e0ea9479c821d4b5728c0d3c9840e71085e82', $array['reference']);
        $this->assertEquals('4da053f3f10c255f9f15357cd5be012ebe4d6467', $array['shasum']);
    }

    public function testBuildArchivePath()
    {
        $path = Dist::buildArchivePath(
            'vendor/repository',
            Version::buildFromString('master'),
            new Reference('6a6e0ea9479c821d4b5728c0d3c9840e71085e82')
        );

        self::assertEquals('/archives/vendor/repository/dev-master/6a6e0ea9479c821d4b5728c0d3c9840e71085e82.tar.gz', $path);
    }
}
