<?php

namespace Acappella\Tests\Domain\ValueObject;

use Acappella\Domain\ValueObject\Reference;
use PHPUnit\Framework\TestCase;

final class ReferenceTest extends TestCase
{
    public function testString()
    {
        self::assertEquals('foo', (string) new Reference('foo'));
    }
}
