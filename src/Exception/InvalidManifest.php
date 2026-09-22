<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * A composer manifest is not the JSON object a digest or a lock refresh needs.
 */
final class InvalidManifest extends \UnexpectedValueException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function notAnObject(): self
    {
        return new self('The composer manifest is not a JSON object');
    }
}
