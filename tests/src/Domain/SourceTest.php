<?php

namespace Acappella\Tests\Domain;

use Acappella\Domain\Source;
use PHPUnit\Framework\TestCase;

final class SourceTest extends TestCase
{
    public function testBuildFromArray()
    {
        $source = Source::buildFromArray([
            'type'      => 'git',
            'url'       => 'git@gitea.my-website.com:vendor/repository.git',
            'reference' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
        ]);

        $sourceArray = $source->_toArray();

        $this->assertEquals('git', $sourceArray['type']);
        $this->assertEquals('git@gitea.my-website.com:vendor/repository.git', $sourceArray['url']);
        $this->assertEquals('6a6e0ea9479c821d4b5728c0d3c9840e71085e82', $sourceArray['reference']);
    }
}
