<?php

namespace CompoLab\Tests\Domain\ValueObject;

use CompoLab\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function testBadUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Url('gitlab.my-website.com');
    }

    public function testHttpUrl()
    {
        $url = new Url('https://gitlab.my-website.com');
        self::assertRegExp('/^https:/', (string) $url);
    }

    public function testSshUrl()
    {
        $url = new Url('git@gitlab.my-website.com:vendor/project.git');
        self::assertRegExp('/^git.*git$/', (string) $url);
    }
}
