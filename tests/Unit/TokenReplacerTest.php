<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenReplacer;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;
use PHPUnit\Framework\TestCase;

final class TokenReplacerTest extends TestCase
{
    private function tokens(): TokenSet
    {
        return TokenSet::fromSlug(Slug::fromString('my-theme'), Host::Theme);
    }

    public function testEveryTokenIsReplaced(): void
    {
        $replaced = TokenReplacer::replace('HOWDAH Howdah howdah MAHOUT THEME NAME', $this->tokens());

        self::assertSame('MY_THEME MyTheme my-theme My Theme', $replaced);
    }

    public function testTheReplacementsAreCaseSensitiveAndOrdered(): void
    {
        $replaced = TokenReplacer::replace('the howdah domain, the Howdah namespace, the HOWDAH constant', $this->tokens());

        self::assertSame('the my-theme domain, the MyTheme namespace, the MY_THEME constant', $replaced);
    }

    public function testTheScaffoldSOwnNamesAreUntouchable(): void
    {
        $source = 'iniznet/mahout-scaffold, Iniznet\\Mahout\\Scaffold, mahout new';

        self::assertSame($source, TokenReplacer::replace($source, $this->tokens()));
    }

    public function testWordsThatMerelyContainASlugAreReplacedToo(): void
    {
        // Replacement is byte-level; a stub that names a symbol after the
        // starter slug gets exactly what it asked for, so the stubs must not
        // do it. This test pins the behaviour.
        $replaced = TokenReplacer::replace('howdahs, Howdahs, HOWDAHS', $this->tokens());

        self::assertSame('my-themes, MyThemes, MY_THEMES', $replaced);
    }
}
