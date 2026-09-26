<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;

/**
 * The token map one generation replaces: the slug, the namespace root, the
 * constant prefix, the display name and what kind of host this is. Every
 * replacement the scaffold performs is a member of this map, in this order.
 *
 * The host travels with the tokens because two files are named by it rather than
 * by the slug: the Composer type, which WordPress's installer reads, and nothing
 * else. A theme and a plugin are the same skeleton with a different answer to that
 * one question, so the answer is a token instead of a second tree.
 */
final readonly class TokenSet
{
    private const string NAMESPACE_PATTERN = '/^[A-Z][A-Za-z0-9]*$/';

    private function __construct(
        public string $slug,
        public string $namespaceRoot,
        public string $constantPrefix,
        public string $displayName,
        public string $host,
        public string $composerType,
    ) {
    }

    public static function fromSlug(Slug $slug, Host $host): self
    {
        return new self(
            slug: $slug->value(),
            namespaceRoot: $slug->namespaceRoot(),
            constantPrefix: $slug->constantPrefix(),
            displayName: $slug->displayName(),
            host: $host->value,
            composerType: $host->composerType(),
        );
    }

    /** @throws InvalidFlagValue when the explicit namespace is not a single namespace segment */
    public static function fromSlugWithNamespace(Slug $slug, Host $host, string $namespace): self
    {
        if (1 !== preg_match(self::NAMESPACE_PATTERN, $namespace)) {
            throw InvalidFlagValue::forValue('namespace', $namespace, ['a single namespace segment, e.g. MyTheme']);
        }

        return new self(
            slug: $slug->value(),
            namespaceRoot: $namespace,
            constantPrefix: $slug->constantPrefix(),
            displayName: $slug->displayName(),
            host: $host->value,
            composerType: $host->composerType(),
        );
    }
}
