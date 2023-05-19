<?php

namespace Acappella\Domain;

use Acappella\Domain\Type\Factory;
use Acappella\Domain\Type\Tar;
use Acappella\Domain\Type\Type;
use Acappella\Domain\Utils\JsonConvertible;
use Acappella\Domain\Utils\JsonConvertibleTrait;
use Acappella\Domain\ValueObject\File;
use Acappella\Domain\ValueObject\Reference;
use Acappella\Domain\ValueObject\Url;
use Acappella\Domain\ValueObject\Version;
use Acappella\Exception\AcappellaException;

final class Dist implements JsonConvertible
{
    use JsonConvertibleTrait;

    public const ARCHIVE_EXT = 'tar.gz';

    /** @var Type */
    private $type;

    /** @var Url */
    private $url;

    /** @var Reference */
    private $reference;

    /** @var File */
    private $localPath;

    /** @var string */
    private $shasum;

    public function __construct(Type $type, Url $url, Reference $reference, File $localPath, string $shasum = null)
    {
        $this->type = $type;
        $this->url = $url;
        $this->reference = $reference;
        $this->localPath = $localPath;
        $this->shasum = $shasum;

        if (is_null($shasum) and !$shasum = sha1_file((string) $localPath)) {
            throw new AcappellaException(sprintf('Impossible to compute SHA checksum on file %s', $localPath));
        }
        $this->shasum = $shasum;

        $this->hideArrayKey('localPath');
    }

    public function getLocalPath(): File
    {
        return $this->localPath;
    }

    public static function buildArchivePath(
        string $name,
        Version $version,
        Reference $reference,
        string $cachePath = null
    ): string {
        return sprintf(
            '%s/archives/%s/%s/%s.%s',
            rtrim($cachePath, '/'),
            $name,
            $version,
            $reference,
            self::ARCHIVE_EXT
        );
    }

    public static function buildFromArray(
        string $cachePath,
        string $packageName,
        Version $packageVersion,
        array $data
    ): self {
        return new self(
            isset($data['type']) ? Factory::buildFromString($data['type']) : new Tar(),
            new Url($data['url']),
            $reference = new Reference($data['reference']),
            new File(self::buildArchivePath(
                $packageName,
                $packageVersion,
                $reference,
                $cachePath
            )),
            isset($data['shasum']) ? $data['shasum'] : null
        );
    }
}
