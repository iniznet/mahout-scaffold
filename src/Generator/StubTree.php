<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;

/**
 * The stub layers one generation composes, in copy order.
 *
 * base is the framework-neutral skeleton; a CSS preset, a JS preset and a mode
 * overlay follow. Presets are directories on disk, not packages: the accepted
 * values are exactly the directories present, and anything else is refused
 * before any file is written.
 */
final readonly class StubTree
{
    private const string BASE = 'base';

    public function __construct(private string $root)
    {
    }

    public static function fromPackage(): self
    {
        return new self(dirname(__DIR__, 2).'/stubs');
    }

    /** @return list<string> the layer directories, in copy order */
    public function layers(string $css, string $js, string $mode): array
    {
        $layers = [
            ['base', self::BASE],
            ['css', 'presets/css/'.$css],
            ['js', 'presets/js/'.$js],
            ['mode', 'modes/'.$mode],
        ];

        $resolved = [];
        foreach ($layers as [$flag, $relative]) {
            $path = $this->root.'/'.$relative;

            if (!is_dir($path)) {
                if ('base' === $flag) {
                    throw StubLayerMissing::forLayer($relative, $path);
                }

                throw InvalidFlagValue::forValue($flag, $relative, $this->available($flag));
            }

            $resolved[] = $path;
        }

        return $resolved;
    }

    /** @return list<string> */
    private function available(string $kind): array
    {
        $directory = 'base' === $kind ? $this->root : match ($kind) {
            'css' => $this->root.'/presets/css',
            'js' => $this->root.'/presets/js',
            default => $this->root.'/modes',
        };

        $entries = is_dir($directory) ? scandir($directory) : false;

        if (false === $entries) {
            return [];
        }

        $names = [];
        foreach ($entries as $entry) {
            if ('.' !== $entry && '..' !== $entry && is_dir($directory.'/'.$entry)) {
                $names[] = $entry;
            }
        }

        sort($names);

        return $names;
    }
}
