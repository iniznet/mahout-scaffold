<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;
use PHPUnit\Framework\TestCase;

final class TokenSetTest extends TestCase
{
    public function testTheTokensDeriveFromTheSlug(): void
    {
        $tokens = TokenSet::fromSlug(Slug::fromString('my-theme'), Host::Theme);

        self::assertSame('my-theme', $tokens->slug);
        self::assertSame('MyTheme', $tokens->namespaceRoot);
        self::assertSame('MY_THEME', $tokens->constantPrefix);
        self::assertSame('My Theme', $tokens->displayName);
        self::assertSame('theme', $tokens->host);
        self::assertSame('wordpress-theme', $tokens->composerType);
    }

    public function testAPluginCarriesTheHostItWasGivenNotTheDefault(): void
    {
        $tokens = TokenSet::fromSlug(Slug::fromString('my-plugin'), Host::Plugin);

        self::assertSame('plugin', $tokens->host);
        self::assertSame('wordpress-plugin', $tokens->composerType);
        self::assertSame('My Plugin', $tokens->displayName, 'the display name is a host name, not a theme-only derivation.');
    }

    public function testAnExplicitNamespaceReplacesTheDerivedOne(): void
    {
        $tokens = TokenSet::fromSlugWithNamespace(Slug::fromString('my-theme'), Host::Theme, 'House');

        self::assertSame('House', $tokens->namespaceRoot);
        self::assertSame('my-theme', $tokens->slug);
        self::assertSame('MY_THEME', $tokens->constantPrefix);
        self::assertSame('My Theme', $tokens->displayName);
    }

    public static function invalidNamespaces(): array
    {
        return [
            'empty' => [''],
            'leading lowercase' => ['myTheme'],
            'leading digit' => ['9Theme'],
            'two segments' => ['My\\Theme'],
            'underscore' => ['My_Theme'],
            'hyphen' => ['My-Theme'],
        ];
    }

    /**
     * @dataProvider invalidNamespaces
     */
    public function testANamespaceThatIsNotOneSegmentIsRefused(string $namespace): void
    {
        $this->expectException(InvalidFlagValue::class);

        TokenSet::fromSlugWithNamespace(Slug::fromString('my-theme'), Host::Theme, $namespace);
    }
}
