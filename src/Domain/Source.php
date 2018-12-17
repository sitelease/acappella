<?php

namespace CompoLab\Domain;

use CompoLab\Domain\Type\Factory;
use CompoLab\Domain\Type\Git;
use CompoLab\Domain\Type\Type;
use CompoLab\Domain\Utils\JsonConvertible;
use CompoLab\Domain\Utils\JsonConvertibleTrait;
use CompoLab\Domain\ValueObject\Reference;
use CompoLab\Domain\ValueObject\Url;

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
            isset($data['type']) ? Factory::buildFromString($data['type']) : new Git,
            new Url($data['url']),
            new Reference($data['reference'])
        );
    }
}
