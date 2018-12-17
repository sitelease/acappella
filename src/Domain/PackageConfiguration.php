<?php

namespace CompoLab\Domain;

/**
 *
 * @property string $name
 * @property array  $source
 * @property string $version
 * @property array  $dist
 * @property array  $require
 * @property array  $autoload
 */
final class PackageConfiguration
{
    /** @var array */
    private $data = [];

    public function __construct(array $data)
    {
        $keys = array_keys($data);

        if (!in_array('name', $keys)) {
            throw new \RuntimeException('Malformed package configuration');
        }

        $this->data = $data;
    }

    public static function buildFromPath(string $path): self
    {
        if (!$json = file_get_contents($path)) {
            throw new \RuntimeException(sprintf('File "%s" is not readable'));
        }

        return self::buildFromJson($json);
    }

    public static function buildFromJson(string $json): self
    {
        if (!$data = json_decode($json, true)) {
            throw new \RuntimeException('Impossible to decode JSON string as array');
        }

        return new self($data);
    }

    public function _toArray(): array
    {
        return $this->data;
    }

    /**
     *
     * @param string $name
     * @return string|array
     */
    public function __get($name)
    {
        $name = str_replace('-', '_', $name);

        if (!isset($this->data[$name])) {
            throw new \RuntimeException(sprintf('The is no "%s" property in composer.json'));
        }

        return $this->data[$name];
    }
}
