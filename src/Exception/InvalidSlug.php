<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * The theme slug is not one the family's naming rules accept.
 */
final class InvalidSlug extends \InvalidArgumentException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function fromInput(string $value): self
    {
        return new self(sprintf(
            '"%s" is not a valid theme slug: lowercase letters, digits and hyphens, starting with a letter, at most 60 characters.',
            $value,
        ));
    }
}
