<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;
use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\StubTree;
use PHPUnit\Framework\TestCase;

final class StubTreeTest extends TestCase
{
    public function testTheLayersComposeInCopyOrder(): void
    {
        $layers = StubTree::fromPackage()->layers(Host::Theme, 'tailwind', 'stimulus', 'block');

        self::assertSame(
            ['common', 'base-theme', 'presets/css/tailwind', 'presets/js/stimulus', 'modes/block'],
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
            StubTree::fromPackage()->layers(Host::Theme, $css, $js, $mode);

            self::fail('The generator must refuse an unknown flag value.');
        } catch (InvalidFlagValue $refusal) {
            self::assertStringContainsString('Unknown --'.$flag.' value', $refusal->getMessage());

            if ('mode' !== $flag) {
                self::assertStringContainsString('native', $refusal->getMessage());
            }
        }
    }

    public function testAPluginComposesNoModeLayer(): void
    {
        $layers = StubTree::fromPackage()->layers(Host::Plugin, 'native', 'native', null);

        self::assertSame(
            ['common', 'base-plugin', 'presets/css/native', 'presets/js/native'],
            array_map(
                static fn (string $path): string => str_replace(dirname(__DIR__, 2).'/stubs/', '', $path),
                $layers,
            ),
            'a plugin has no hierarchy to overlay, so no mode layer is composed.',
        );
    }

    public function testAModePassedToAPluginIsRefusedRatherThanDropped(): void
    {
        try {
            StubTree::fromPackage()->layers(Host::Plugin, 'native', 'native', 'classic');
        } catch (InvalidInvocation $refusal) {
            self::assertStringContainsString('template mode', $refusal->getMessage());

            return;
        }

        self::fail('a flag that changes nothing must be refused, not ignored.');
    }

    public function testAThemeWithNoModeIsRefusedRatherThanDefaultedInsideTheTree(): void
    {
        try {
            StubTree::fromPackage()->layers(Host::Theme, 'native', 'native', null);
        } catch (InvalidInvocation $refusal) {
            self::assertStringContainsString('--mode must name one', $refusal->getMessage());

            return;
        }

        self::fail('the CLI owns defaults; the layer tree must not invent one.');
    }

    public function testTheAvailableListComesFromTheDirectoriesOnDisk(): void
    {
        try {
            StubTree::fromPackage()->layers(Host::Theme, 'nope', 'native', 'classic');

            self::fail('The generator must refuse an unknown css preset.');
        } catch (InvalidFlagValue $refusal) {
            self::assertSame(['css-modules', 'native', 'tailwind'], $refusal->allowed());
        }
    }

    public function testAMissingBaseIsALayerFailureNotAFlagRefusal(): void
    {
        $tree = new StubTree(sys_get_temp_dir().'/mahout-scaffold-empty-'.uniqid());

        $this->expectException(StubLayerMissing::class);

        $tree->layers(Host::Theme, 'native', 'native', 'classic');
    }
}
