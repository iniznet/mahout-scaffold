<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;

/**
 * The token map one generation replaces: the slug, the namespace root, the
 * constant prefix and the theme name. Every replacement the scaffold performs
 * is a member of this map, in this order.
 */
final readonly class TokenSet
{
    private const string NAMESPACE_PATTERN = '/^[A-Z][A-Za-z0-9]*$/';

    private function __construct(
        public string $slug,
        public string $namespaceRoot,
        public string $constantPrefix,
        public string $themeName,
    ) {
    }

    public static function fromSlug(Slug $slug): self
    {
        return new self(
            slug: $slug->value(),
            namespaceRoot: $slug->namespaceRoot(),
            constantPrefix: $slug->constantPrefix(),
            themeName: $slug->themeName(),
        );
    }

    /** @throws InvalidFlagValue when the explicit namespace is not a single namespace segment */
    public static function fromSlugWithNamespace(Slug $slug, string $namespace): self
    {
        if (1 !== preg_match(self::NAMESPACE_PATTERN, $namespace)) {
            throw InvalidFlagValue::forValue('namespace', $namespace, ['a single namespace segment, e.g. MyTheme']);
        }

        return new self(
            slug: $slug->value(),
            namespaceRoot: $namespace,
            constantPrefix: $slug->constantPrefix(),
            themeName: $slug->themeName(),
        );
    }
}
