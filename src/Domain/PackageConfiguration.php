<?php

namespace Acappella\Domain;

use Acappella\Exception\AcappellaException;

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
            throw new AcappellaException('Malformed package configuration - No name key found');
        }

        $this->data = $data;
    }

    public static function buildFromPath(string $path): self
    {
        if (!$json = file_get_contents($path)) {
            throw new AcappellaException(sprintf('File "%s" is not readable'));
        }

        return self::buildFromJson($json);
    }

    public static function buildFromJson(string $json): self
    {
        if (!$data = json_decode($json, true)) {
            throw new AcappellaException('Malformed JSON - Impossible to decode JSON string as array');
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
            throw new AcappellaException(sprintf('The is no "%s" property in composer.json'));
        }

        return $this->data[$name];
    }
}
