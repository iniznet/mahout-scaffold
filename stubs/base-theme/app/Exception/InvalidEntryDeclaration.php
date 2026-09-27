<?php

/**
 * An entry declaration the asset pipeline cannot register: the config file
 * returned data of the wrong shape. The registration is refused, never coerced
 * into the expected shape.
 */
declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

final class InvalidEntryDeclaration extends \UnexpectedValueException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function notAList(string $type): self
    {
        return new self(sprintf('config/entries.php must return a list of declarations, got %s.', $type));
    }

    public static function notADeclaration(string $type): self
    {
        return new self(sprintf('Every entry declaration must be an array, got %s.', $type));
    }
}
