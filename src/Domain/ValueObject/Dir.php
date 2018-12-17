<?php

namespace CompoLab\Domain\ValueObject;

final class Dir
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        if (!is_dir($value)) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a valid directory', $value));
        }

        if (!is_readable($value)) {
            throw new \InvalidArgumentException(sprintf('Dir "%s" is not readable', $value));
        }

        if (!is_writable($value)) {
            throw new \InvalidArgumentException(sprintf('Dir "%s" is not writable', $value));
        }

        $this->value = realpath($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getPath(string $path): string
    {
        return realpath(
            sprintf('%s/%s', $this->value, ltrim($path, '/'))
        );
    }

    public function __toString()
    {
        return $this->value;
    }
}
