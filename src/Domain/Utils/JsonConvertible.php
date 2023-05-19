<?php

namespace Acappella\Domain\Utils;

use ArrayAccess;
use IteratorAggregate;
use JsonSerializable;

interface JsonConvertible extends ArrayAccess, IteratorAggregate, JsonSerializable
{
    public function _toArray(): array;
}
