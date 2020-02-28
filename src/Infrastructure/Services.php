<?php

namespace CompoLab\Infrastructure;

use CompoLab\Application\GiteaRepositoryManager;
use Gitea\Client as Gitea;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use CompoLab\Exception\CompoLabException;

/**
 *
 * @property GiteaRepositoryManager $manager
 * @property Gitea $gitea
 */
final class Services
{
    const
        CONFIG_DIRS = [__DIR__ . '/../../config'],
        CONFIG_NAME = 'services.yml';

    /** @var self */
    private static $instance;

    /** @var ContainerInterface */
    private $services;

    public function __construct(ContainerInterface $services)
    {
        $this->services = $services;
    }

    public static function getInstance(bool $cacheEnabled = true): self
    {
        if (self::$instance) {
            return self::$instance;
        }

        $cachePath = sprintf('%s/compolab_services_cache.php', sys_get_temp_dir());

        if ($cacheEnabled and file_exists($cachePath)) {
            // If cache exists, use it...
            if (!is_readable($cachePath)) {
                throw new CompoLabException(sprintf('Services cache "%s" is not readable', $cachePath));
            }

            require_once $cachePath;
            $services = new \ProjectServiceContainer;

        } else {
            // ... otherwise compile & cache services
            $services = new ContainerBuilder;

            $loader = new YamlFileLoader($services, new FileLocator(self::CONFIG_DIRS));
            $loader->load(self::CONFIG_NAME);

            $services->compile(true);

            // Compile and cache production config
            if ($cacheEnabled) {
                if (!is_dir($cacheDir = dirname($cachePath))) {
                    mkdir($cacheDir, 0777, true);
                }

                file_put_contents($cachePath, (new PhpDumper($services))->dump());
            }
        }

        return self::$instance = new self($services);
    }

    public function getParameter(string $name)
    {
        return $this->services->getParameter($name);
    }

    /**
     *
     * @param $service
     * @return bool
     */
    public function __isset($service)
    {
        return $this->services->has($service);
    }

    /**
     *
     * @param $service
     * @return mixed
     * @throws \Exception
     */
    public function __get($service)
    {
        return $this->services->get($service);
    }
}
