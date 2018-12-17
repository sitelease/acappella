<?php

namespace CompoLab\Tests\Domain\ValueObject;

use CompoLab\Domain\ValueObject\File;
use PHPUnit\Framework\TestCase;

final class FileTest extends TestCase
{
    public function testFromDir()
    {
        $this->expectException(\InvalidArgumentException::class);
        new File(__DIR__ . '/../../../data');
    }

    public function testBadPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        new File(__DIR__ . '/../../../data/foobar');
    }

    public function testRealpath()
    {
        $dir = new File(__DIR__ . '/../../../data/packages.json');
        self::assertRegExp('|^.+tests/data/packages.json$|', (string) $dir);
    }
}
