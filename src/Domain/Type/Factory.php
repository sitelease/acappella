<?php

namespace Acappella\Domain\Type;

use Acappella\Exception\AcappellaException;

final class Factory
{
    public static function buildFromString(string $string): Type
    {
        if (preg_match('/^git$/i', $string)) {
            return new Git;
        }

        if (preg_match('/^tar$/i', $string)) {
            return new Tar;
        }

        throw new AcappellaException(sprintf('Impossible to create a type from string "%s"', $string));
    }
}
