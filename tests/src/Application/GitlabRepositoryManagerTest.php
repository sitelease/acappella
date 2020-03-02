<?php

namespace Acappella\Tests\Application;

use Acappella\Application\GiteaRepositoryManager;
use Acappella\Domain\Repository;
use Acappella\Domain\ValueObject\Dir;
use Acappella\Domain\ValueObject\Url;
use Acappella\Infrastructure\JsonRepositoryCache;
use Acappella\Tests\Utils\DummyHttpClient;
use Gitea\Client;
use Gitea\HttpClient\Builder;
use Gitea\Model\Branch;
use Gitea\Model\Repository;
use Gitea\Model\Tag;
use PHPUnit\Framework\TestCase;

final class GiteaRepositoryManagerTest extends TestCase
{
    /** @var JsonRepositoryCache */
    private static $cache;

    /** @var GiteaRepositoryManager */
    private static $manager;

    public static function setUpBeforeClass()
    {
        self::$cache = new JsonRepositoryCache(new Repository(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__ . '/../../cache')
        ));

        self::$manager = new GiteaRepositoryManager(self::$cache);
    }

    public function testAddRepository()
    {
        $client = new Client(new Builder(
            new DummyHttpClient(file_get_contents(__DIR__ . '/../../data/composer.json'))
        ));

        $repository = Repository::fromArray($client, [
            'id' => 1,
            'name' => 'repository',
            'description' => null,
            'web_url' => 'https://composer.my-website.com/vendor/repository',
            'avatar_url' => null,
            'git_ssh_url' => 'git@composer.my-website.com:repository.git',
            'git_http_url' => 'https://composer.my-website.com/vendor/repository.git',
            'namespace' => 'default',
            'visibility_level' => 0,
            'path_with_namespace' => 'vendor/repository',
            'default_branch' => 'master',
            'ci_config_path' => null,
            'homepage' => 'https://composer.my-website.com/vendor/repository',
            'url' => 'git@composer.my-website.com:repository.git',
            'ssh_url' => 'git@composer.my-website.com:repository.git',
            'http_url' => 'https://composer.my-website.com/vendor/repository.git',
            'ssh_url_to_repo' => 'https://composer.my-website.com/vendor/repository.git',
        ]);

        $branch = Branch::fromArray($client, $repository, [
            'name' => '2.0',
            'commit' => [
                'id' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            ],
        ]);

        $tag = Tag::fromArray($client, $repository, [
            'name' => 'v1.2.3',
            'commit' => [
                'id' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            ],
        ]);

        self::$manager->registerBranch($branch);
        self::$manager->registerTag($tag);
        self::$manager->save();
        self::assertEquals(2, count(self::$cache));

        self::$manager->deleteTag($tag);
        self::$manager->save();
        self::assertEquals(1, count(self::$cache));
    }

    public static function tearDownAfterClass()
    {
        // Clean packages for future tests
        file_put_contents(__DIR__ . '/../../cache/packages.json', '{}');
    }
}
