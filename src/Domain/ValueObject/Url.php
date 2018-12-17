<?php

namespace CompoLab\Domain\ValueObject;

final class Url
{
    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        if (!$this->validateUrl($value)) {
            throw new \InvalidArgumentException(sprintf('URL "%s" is not supported', $value));
        }

        $this->value = $value;
    }

    private function validateUrl(string $url): bool
    {
        // Validate SSH URLs
        if (preg_match('/^git@[-a-z0-9\.]+:[\w-]+\/[\w-]+\.git$/', $url)) {
            return true;
        }

        // Validate HTTP URLs
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        return false;
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
