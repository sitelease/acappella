<?php

namespace CompoLab\Tests\Application;

use CompoLab\Application\GitlabRepositoryManager;
use CompoLab\Domain\Repository;
use CompoLab\Domain\ValueObject\Dir;
use CompoLab\Domain\ValueObject\Url;
use CompoLab\Infrastructure\JsonRepositoryCache;
use CompoLab\Tests\Utils\DummyHttpClient;
use Gitlab\Client;
use Gitlab\HttpClient\Builder;
use Gitlab\Model\Branch;
use Gitlab\Model\Project;
use Gitlab\Model\Tag;
use PHPUnit\Framework\TestCase;

final class GitlabRepositoryManagerTest extends TestCase
{
    /** @var JsonRepositoryCache */
    private static $cache;

    /** @var GitlabRepositoryManager */
    private static $manager;

    public static function setUpBeforeClass()
    {
        self::$cache = new JsonRepositoryCache(new Repository(
            new Url('https://composer.my-website.com'),
            new Dir(__DIR__ . '/../../cache')
        ));

        self::$manager = new GitlabRepositoryManager(self::$cache);
    }

    public function testAddProject()
    {
        $client = new Client(new Builder(
            new DummyHttpClient(file_get_contents(__DIR__ . '/../../data/composer.json'))
        ));

        $project = Project::fromArray($client, [
            'id' => 1,
            'name' => 'project',
            'description' => null,
            'web_url' => 'https://composer.my-website.com/vendor/project',
            'avatar_url' => null,
            'git_ssh_url' => 'git@composer.my-website.com:project.git',
            'git_http_url' => 'https://composer.my-website.com/vendor/project.git',
            'namespace' => 'default',
            'visibility_level' => 0,
            'path_with_namespace' => 'vendor/project',
            'default_branch' => 'master',
            'ci_config_path' => null,
            'homepage' => 'https://composer.my-website.com/vendor/project',
            'url' => 'git@composer.my-website.com:project.git',
            'ssh_url' => 'git@composer.my-website.com:project.git',
            'http_url' => 'https://composer.my-website.com/vendor/project.git',
            'ssh_url_to_repo' => 'https://composer.my-website.com/vendor/project.git',
        ]);

        $branch = Branch::fromArray($client, $project, [
            'name' => '2.0',
            'commit' => [
                'id' => '6a6e0ea9479c821d4b5728c0d3c9840e71085e82',
            ],
        ]);

        $tag = Tag::fromArray($client, $project, [
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
