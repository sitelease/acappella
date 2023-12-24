<?php

namespace Acappella\Domain\ValueObject;

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
        // https://github.com/composer/semver/blob/1d09200268e7d1052ded8e5da9c73c96a63d18f5/src/VersionParser.php#L216-L216
        if (preg_match('{^v?(\d++)(\.(?:\d++|[xX*]))?(\.(?:\d++|[xX*]))?(\.(?:\d++|[xX*]))?$}i', $string, $matches)) {
            $version = '';
            for ($i = 1; $i < 5; $i++) {
                $version .= isset($matches[$i]) ? str_replace(['*', 'X'], 'x', $matches[$i]) : '';
                if (! isset($matches[$i])) {
                    break;
                }
            }

            // do nothing if version already ends with ".x", otherwise append ".x"
            $needle = '.x';
            $needle_len = strlen($needle);
            if ($needle_len === 0 || substr_compare($version, $needle, -$needle_len) === 0) {
            } else {
                $version .= '.x';
            }

            return new self($version.'-dev');
        }

        // Transform feature branch (eg. "master") to composer style (eg. "dev-master")
        return new self(sprintf('dev-%s', $string));
    }

    public function __toString()
    {
        return $this->value;
    }
}
