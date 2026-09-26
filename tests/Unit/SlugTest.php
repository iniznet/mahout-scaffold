<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\InvalidSlug;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use PHPUnit\Framework\TestCase;

final class SlugTest extends TestCase
{
    public static function validSlugs(): array
    {
        return [
            'simple' => ['acme'],
            'hyphenated' => ['my-theme'],
            'with digits' => ['theme2'],
            'single letter' => ['a'],
        ];
    }

    /** @dataProvider validSlugs */
    public function testItAcceptsAValidSlug(string $value): void
    {
        self::assertSame($value, Slug::fromString($value)->value());
    }

    public static function invalidSlugs(): array
    {
        return [
            'empty' => [''],
            'uppercase' => ['MyTheme'],
            'leading digit' => ['1theme'],
            'leading hyphen' => ['-theme'],
            'trailing hyphen' => ['theme-'],
            'underscore' => ['my_theme'],
            'space' => ['my theme'],
            'two hyphens inside is fine but slashes are not' => ['my/theme'],
            'longer than sixty' => [str_repeat('a', 61)],
        ];
    }

    /** @dataProvider invalidSlugs */
    public function testItRejectsAnInvalidSlug(string $value): void
    {
        $this->expectException(InvalidSlug::class);

        Slug::fromString($value);
    }

    public function testEveryDerivationStartsFromTheSlug(): void
    {
        $slug = Slug::fromString('my-theme');

        self::assertSame('MyTheme', $slug->namespaceRoot());
        self::assertSame('MY_THEME', $slug->constantPrefix());
        self::assertSame('My Theme', $slug->displayName());
    }

    public function testADigitOnlyTailKeepsItsCase(): void
    {
        $slug = Slug::fromString('press-42');

        self::assertSame('Press42', $slug->namespaceRoot());
        self::assertSame('PRESS_42', $slug->constantPrefix());
        self::assertSame('Press 42', $slug->displayName());
    }
}
