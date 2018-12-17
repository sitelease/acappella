<?php

namespace CompoLab\Domain\ValueObject;

final class Version
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function buildFromString(string $string): self
    {
        // Keep tag names (eg. "v1.2.3") for versioning
        if (preg_match('/^v?\d+\.\d+\.\d+$/', $string)) {
            return new self($string);
        }

        // Transform version branch (eg. "2.0") to composer style (eg. "2.0.x-dev")
        if (preg_match('/^\d+\.\d+$/', $string)) {
            return new self(sprintf('%s.x-dev', $string));
        }

        // Transform feature branch (eg. "master") to composer style (eg. "dev-master")
        return new self(sprintf('dev-%s', $string));
    }

    public function __toString()
    {
        return $this->value;
    }
}
