<?php

namespace Acappella\Tests\Domain\ValueObject;

use Acappella\Domain\ValueObject\Version;
use PHPUnit\Framework\TestCase;

final class VersionTest extends TestCase
{
    public function testString()
    {
        self::assertEquals('foo', (string) new Version('foo'));
    }

    public function testBuildFromFeatureBranch()
    {
        self::assertEquals('dev-master', (string) Version::buildFromString('master'));
    }

    public function testBuildFromVersionBranch()
    {
        self::assertEquals('2.0.x-dev', (string) Version::buildFromString('2.0'));
        self::assertEquals('10.x-dev', (string) Version::buildFromString('10.x'));
    }

    public function testBuildFromTag()
    {
        self::assertEquals('v1.2.3', (string) Version::buildFromString('v1.2.3'));
        self::assertEquals('4.5.6', (string) Version::buildFromString('4.5.6'));
    }
}
