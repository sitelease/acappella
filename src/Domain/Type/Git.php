<?php

namespace CompoLab\Domain\Type;

final class Git implements Type
{
    public function __toString()
    {
        return 'git';
    }
}
