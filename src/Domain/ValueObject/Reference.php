<?php

namespace Acappella\Domain\ValueObject;

final class Reference
{
    /** @var string */
    private $checksum;

    public function __construct(string $checksum)
    {
        $this->checksum = $checksum;
    }

    public function __toString()
    {
        return $this->checksum;
    }
}
