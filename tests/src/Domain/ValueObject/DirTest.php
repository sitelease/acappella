<?php

namespace Acappella\Tests\Domain\ValueObject;

use Acappella\Domain\ValueObject\Dir;
use PHPUnit\Framework\TestCase;

final class DirTest extends TestCase
{
    public function testFromFile()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Dir(__DIR__ . '/../../../data/packages.json');
    }

    public function testBadPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Dir(__DIR__ . '/../../../data/foobar');
    }

    public function testRealpath()
    {
        $dir = new Dir(__DIR__ . '/../../../data');
        self::assertRegExp('|^.+tests/data$|', (string) $dir);
    }

    public function testPathGetter()
    {
        $dir = new Dir(__DIR__ . '/../../../cache');
        $path = $dir->getPath('/archives');
        self::assertRegExp('|^.+tests/cache/archives$|', $path);
    }
}
