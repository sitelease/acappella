<?php

namespace CompoLab\Domain\Utils;

trait JsonConvertibleTrait
{
    /** @var array */
    private $objPropertiesCache = [];

    /** @var array */
    private $objArrayHiddenKeys = [
        'objPropertiesCache',
        'objArrayHiddenKeys',
    ];

    private function hideArrayKey($key)
    {
        $this->objArrayHiddenKeys[] = $key;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->$offset)) {
            throw new \RuntimeException(sprintf('There is no "%s" property on object %s', $offset, get_class($this)));
        }
    }

    public function offsetSet(/** @scrutinizer ignore-unused */ $offset, /** @scrutinizer ignore-unused */ $value)
    {
        throw new \RuntimeException('Array access is read-only');
    }

    public function offsetUnset(/** @scrutinizer ignore-unused */ $offset)
    {
        throw new \RuntimeException('Array access is read-only');
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->getObjectProperties());
    }

    private function getObjectProperties(): array
    {
        if (!empty($this->objPropertiesCache)) {
            return $this->objPropertiesCache;
        }

        $reflection = new \ReflectionClass(self::class);

        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            if (in_array($property->getName(), $this->objArrayHiddenKeys)) {
                continue;
            }

            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($this);
        }

        return $this->objPropertiesCache = $properties;
    }

    public function jsonSerialize()
    {
        return array_filter($this->_toArray());
    }

    public function _toArray(): array
    {
        $array = [];
        foreach ($this as $key => $value) {
            if ($value instanceof JsonConvertible) {
                $array[$key] = $value->_toArray();

            } elseif (is_object($value) and method_exists($value, '__toString')) {
                $array[$key] = (string) $value;

            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }
}
