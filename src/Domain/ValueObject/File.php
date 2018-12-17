<?php

namespace CompoLab\Domain\ValueObject;

final class File
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        if (!is_file($value)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a valid file', $value));
        }

        if (!is_readable($value)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not readable', $value));
        }

        if (!is_writable($value)) {
            throw new \InvalidArgumentException(sprintf('File "%s" is not writable', $value));
        }

        $this->value = realpath($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
