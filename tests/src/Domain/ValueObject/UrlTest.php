<?php

namespace CompoLab\Tests\Domain\ValueObject;

use CompoLab\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function testBadUrl()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Url('gitea.my-website.com');
    }

    public function testHttpUrl()
    {
        $url = new Url('https://gitea.my-website.com');
        self::assertRegExp('/^https:/', (string) $url);
    }

    public function testSshUrl()
    {
        $url = new Url('git@gitea.my-website.com:vendor/repository.git');
        self::assertRegExp('/^git.*git$/', (string) $url);
    }
}
