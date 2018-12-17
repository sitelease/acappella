<?php

namespace CompoLab\Domain\Utils;

interface JsonConvertible extends \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    public function _toArray(): array;
}
