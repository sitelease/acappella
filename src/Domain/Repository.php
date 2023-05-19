<?php

namespace Acappella\Domain;

use Acappella\Domain\ValueObject\Dir;
use Acappella\Domain\ValueObject\File;
use Acappella\Domain\ValueObject\Url;
use Acappella\Exception\AcappellaException;

final class Repository implements \Countable, \JsonSerializable
{
    public const INDEX = 'packages.json';

    /** @var Url */
    private $baseUrl;

    /** @var Dir */
    private $cachePath;

    /** @var Package[] */
    private $packages = [];

    /** @var int */
    private $count = 0;

    public function __construct(Url $baseUrl, Dir $cachePath, array $packages = [])
    {
        $this->baseUrl = $baseUrl;
        $this->cachePath = $cachePath;

        foreach ($packages as $package) {
            $this->addPackage($package);
        }
    }

    public function getBaseUrl(): Url
    {
        return $this->baseUrl;
    }

    public function getUrl(string $uri): Url
    {
        return new Url(
            sprintf(
                '%s/%s',
                rtrim($this->baseUrl, '/'),
                ltrim($uri, '/')
            )
        );
    }

    public function getIndexUrl(): Url
    {
        return $this->getUrl(self::INDEX);
    }

    public function getCachePath(): Dir
    {
        return $this->cachePath;
    }

    public function getFile(string $path): File
    {
        return new File(
            sprintf(
                '%s/%s',
                $this->cachePath,
                ltrim($path, '/')
            )
        );
    }

    public function getIndexFile(): File
    {
        return new File(
            sprintf('%s/%s', $this->cachePath, self::INDEX)
        );
    }

    public function addPackage(Package $package)
    {
        if (!isset($this->packages[$package->getName()])) {
            $this->packages[$package->getName()] = [];
        }

        $this->packages[$package->getName()][(string) $package->getVersion()] = $package;
        $this->count++;
    }

    public function removePackage(Package $package)
    {
        if (!isset($this->packages[$package->getName()])) {
            return;
        }

        if (!isset($this->packages[$package->getName()][(string) $package->getVersion()])) {
            return;
        }

        unset($this->packages[$package->getName()][(string) $package->getVersion()]);
        $this->count--;

        if (empty($this->packages[$package->getName()])) {
            unset($this->packages[$package->getName()]);
        }
    }

    /**
     *
     * @return Package[]
     */
    public function getPackages(): array
    {
        $packages = [];
        foreach ($this->packages as $vendors) {
            foreach ($vendors as $package) {
                $packages[] = $package;
            }
        }

        return $packages;
    }

    public static function buildFromPath(Url $baseUrl, Dir $cachePath, string $path): self
    {
        // print("buildFromPath() called \n");
        // print("baseUrl -> $baseUrl \n");
        // print("cachePath -> $cachePath \n");
        // print("path -> $path \n");

        if (!$json = file_get_contents($path)) {
            throw new AcappellaException(sprintf('File "%s" is not readable', $path));
        }

        return self::buildFromJson($baseUrl, $cachePath, $json);
    }

    public static function buildFromJson(Url $baseUrl, Dir $cachePath, string $json): self
    {
        if (!$data = json_decode($json, true)) {
            throw new AcappellaException('Impossible to decode JSON string as array');
        }

        if (!isset($data['packages'])) {
            throw new AcappellaException('Malformed JSON');
        }

        $repository = new self($baseUrl, $cachePath);

        foreach ($data['packages'] as $packages) {
            foreach ($packages as $package) {
                $repository->addPackage(Package::buildFromArray((string) $cachePath, $package));
            }
        }

        return $repository;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function jsonSerialize()
    {
        return [
            'packages' => $this->packages
        ];
    }
}
