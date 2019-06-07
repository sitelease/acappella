<?php

namespace CompoLab\Domain;

use CompoLab\Domain\Utils\JsonConvertible;
use CompoLab\Domain\Utils\JsonConvertibleTrait;
use CompoLab\Domain\ValueObject\Version;

final class Package implements JsonConvertible
{
    use JsonConvertibleTrait;

    /** @var string */
    private $name;

    /** @var Version */
    private $version;

    /** @var PackageConfiguration */
    private $configuration;

    /** @var Source */
    private $source;

    /** @var Dist */
    private $dist;

    public function __construct(
        string $name,
        Version $version,
        PackageConfiguration $packageConfiguration,
        ?Source $source,
        ?Dist $dist
    ){
        if (is_null($source) and is_null($dist)) {
            throw new \RuntimeException(sprintf('Package "%s" must have at least a source or a dist', $name));
        }

        $this->name = $name;
        $this->version = $version;
        $this->configuration = $packageConfiguration;
        $this->source = $source;
        $this->dist = $dist;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getDist(): Dist
    {
        return $this->dist;
    }

    public static function buildFromArray(string $cachePath, array $data): self
    {
        return new self(
            $data['name'],
            $version = new Version($data['version']),
            new PackageConfiguration($data),
            isset($data['source']) ? Source::buildFromArray($data['source']) : null,
            isset($data['dist']) ? Dist::buildFromArray($cachePath, $data['name'], $version, $data['dist']) : null
        );
    }

    public function _toArray(): array
    {
        return array_merge($this->configuration->_toArray(), [
            'name'    => $this->name,
            'version' => (string) $this->version,
            'source'  => $this->source,
            'dist'    => $this->dist,
        ]);
    }
}
