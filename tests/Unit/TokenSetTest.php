<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;
use PHPUnit\Framework\TestCase;

final class TokenSetTest extends TestCase
{
    public function testTheTokensDeriveFromTheSlug(): void
    {
        $tokens = TokenSet::fromSlug(Slug::fromString('my-theme'));

        self::assertSame('my-theme', $tokens->slug);
        self::assertSame('MyTheme', $tokens->namespaceRoot);
        self::assertSame('MY_THEME', $tokens->constantPrefix);
        self::assertSame('My Theme', $tokens->themeName);
    }

    public function testAnExplicitNamespaceReplacesTheDerivedOne(): void
    {
        $tokens = TokenSet::fromSlugWithNamespace(Slug::fromString('my-theme'), 'House');

        self::assertSame('House', $tokens->namespaceRoot);
        self::assertSame('my-theme', $tokens->slug);
        self::assertSame('MY_THEME', $tokens->constantPrefix);
        self::assertSame('My Theme', $tokens->themeName);
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

        TokenSet::fromSlugWithNamespace(Slug::fromString('my-theme'), $namespace);
    }
}
