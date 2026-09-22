<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;
use Iniznet\Mahout\Scaffold\Generator\StubTree;
use PHPUnit\Framework\TestCase;

final class StubTreeTest extends TestCase
{
    public function testTheLayersComposeInCopyOrder(): void
    {
        $layers = StubTree::fromPackage()->layers('tailwind', 'stimulus', 'block');

        self::assertSame(
            ['base', 'presets/css/tailwind', 'presets/js/stimulus', 'modes/block'],
            array_map(
                static fn (string $path): string => str_replace(dirname(__DIR__, 2).'/stubs/', '', $path),
                $layers,
            ),
        );
    }

    public static function unknownValues(): array
    {
        return [
            'css' => ['css', 'bootstrap'],
            'js' => ['js', 'jquery'],
            'mode' => ['mode', 'hybrid'],
        ];
    }

    /** @dataProvider unknownValues */
    public function testAnUnknownValueIsRefusedWithTheAvailableList(string $flag, string $value): void
    {
        $css = 'css' === $flag ? $value : 'native';
        $js = 'js' === $flag ? $value : 'native';
        $mode = 'mode' === $flag ? $value : 'classic';

        try {
            StubTree::fromPackage()->layers($css, $js, $mode);

            self::fail('The generator must refuse an unknown flag value.');
        } catch (InvalidFlagValue $refusal) {
            self::assertStringContainsString('Unknown --'.$flag.' value', $refusal->getMessage());

            if ('mode' !== $flag) {
                self::assertStringContainsString('native', $refusal->getMessage());
            }
        }
    }

    public function testTheAvailableListComesFromTheDirectoriesOnDisk(): void
    {
        try {
            StubTree::fromPackage()->layers('nope', 'native', 'classic');

            self::fail('The generator must refuse an unknown css preset.');
        } catch (InvalidFlagValue $refusal) {
            self::assertSame(['css-modules', 'native', 'tailwind'], $refusal->allowed());
        }
    }

    public function testAMissingBaseIsALayerFailureNotAFlagRefusal(): void
    {
        $tree = new StubTree(sys_get_temp_dir().'/mahout-scaffold-empty-'.uniqid());

        $this->expectException(StubLayerMissing::class);

        $tree->layers('native', 'native', 'classic');
    }
}
