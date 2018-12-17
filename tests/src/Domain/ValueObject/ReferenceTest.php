<?php

namespace CompoLab\Tests\Domain\ValueObject;

use CompoLab\Domain\ValueObject\Reference;
use PHPUnit\Framework\TestCase;

final class ReferenceTest extends TestCase
{
    public function testString()
    {
        self::assertEquals('foo', (string) new Reference('foo'));
    }
}
