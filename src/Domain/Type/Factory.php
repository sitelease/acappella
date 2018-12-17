<?php

namespace CompoLab\Domain\Type;

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

        throw new \RuntimeException(sprintf('Impossible to create a type from string "%s"', $string));
    }
}
