<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidSlug;

/**
 * The host slug: the text domain, the hook prefix and the constant prefix all
 * derive from it, so it is validated once and derived from here only. A theme and
 * a plugin are named by the same rule and by the same character set.
 *
 * A value constructor: it holds no state beyond its own value and resolves no
 * collaborator, which is the contract's permitted static shape.
 */
final readonly class Slug
{
    private const string PATTERN = '/^[a-z](?:[a-z0-9]|-(?=[a-z0-9]))*$/';

    private const int MAXIMUM_LENGTH = 60;

    private function __construct(private string $value)
    {
    }

    /** @throws InvalidSlug when the value is not a slug */
    public static function fromString(string $value): self
    {
        if (1 !== preg_match(self::PATTERN, $value) || strlen($value) > self::MAXIMUM_LENGTH) {
            throw InvalidSlug::fromInput($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    /** my-theme → MyTheme */
    public function namespaceRoot(): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $this->value)));
    }

    /** my-theme → MY_THEME */
    public function constantPrefix(): string
    {
        return str_replace('-', '_', strtoupper($this->value));
    }

    /** my-theme → My Theme */
    public function displayName(): string
    {
        return ucwords(str_replace('-', ' ', $this->value));
    }
}
