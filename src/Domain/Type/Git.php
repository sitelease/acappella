<?php

namespace Acappella\Domain\Type;

final class Git implements Type
{
    public function __toString()
    {
        return 'git';
    }
}
