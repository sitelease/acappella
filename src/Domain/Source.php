<?php

namespace Acappella\Domain;

use Acappella\Domain\Type\Factory;
use Acappella\Domain\Type\Git;
use Acappella\Domain\Type\Type;
use Acappella\Domain\Utils\JsonConvertible;
use Acappella\Domain\Utils\JsonConvertibleTrait;
use Acappella\Domain\ValueObject\Reference;
use Acappella\Domain\ValueObject\Url;

final class Source implements JsonConvertible
{
    use JsonConvertibleTrait;

    /** @var Type */
    private $type;

    /** @var Url */
    private $url;

    /** @var Reference */
    private $reference;

    public function __construct(Type $type, Url $url, Reference $reference)
    {
        $this->type = $type;
        $this->url = $url;
        $this->reference = $reference;
    }

    public static function buildFromArray(array $data): self
    {
        return new self(
            isset($data['type']) ? Factory::buildFromString($data['type']) : new Git(),
            new Url($data['url']),
            new Reference($data['reference'])
        );
    }
}
