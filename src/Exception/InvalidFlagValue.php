<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * A flag value is not one of the values the scaffold declares.
 */
final class InvalidFlagValue extends \InvalidArgumentException implements ScaffoldException
{
    /** @param list<string> $allowed */
    private function __construct(string $message, private readonly array $allowed)
    {
        parent::__construct($message);
    }

    /** @param list<string> $allowed */
    public static function forValue(string $flag, string $value, array $allowed): self
    {
        return new self(
            sprintf('Unknown --%s value "%s". Allowed: %s', $flag, $value, implode(', ', $allowed)),
            $allowed,
        );
    }

    /** @return list<string> */
    public function allowed(): array
    {
        return $this->allowed;
    }
}
