<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;

/**
 * The stub layers one generation composes, in copy order.
 *
 * `common` is the host-neutral skeleton — the analyzer configuration, the licence
 * and conduct files, the issue templates, the quality workflow, the npm
 * dependencies and the preload file. The host layer follows it and carries what
 * differs between a theme and a plugin: the package manifest, the entry point and
 * the identity file. A CSS preset, a JS preset and, for a theme only, a mode
 * overlay follow those two. Presets are directories on disk, not packages: the
 * accepted values are exactly the directories present, and anything else is
 * refused before any file is written.
 */
final readonly class StubTree
{
    private const string COMMON = 'common';

    public function __construct(private string $root)
    {
    }

    public static function fromPackage(): self
    {
        return new self(dirname(__DIR__, 2).'/stubs');
    }

    /**
     * The layer directories, in copy order.
     *
     * A mode is refused for a host that has no hierarchy to overlay: the flag
     * either means something for this assembly or the invocation is wrong, and
     * silently dropping it would let a plugin claim a template mode it never uses.
     *
     * @return list<string>
     */
    public function layers(Host $host, string $css, string $js, ?string $mode): array
    {
        if ($host->usesModes() && null === $mode) {
            throw InvalidInvocation::modeRequired($host->value);
        }

        $host->checkMode($mode);

        $layers = [
            ['preset', self::COMMON],
            ['host', $host->baseLayer()],
            ['css', 'presets/css/'.$css],
            ['js', 'presets/js/'.$js],
        ];

        if ($host->usesModes()) {
            $layers[] = ['mode', 'modes/'.$mode];
        }

        $resolved = [];
        foreach ($layers as [$flag, $relative]) {
            $path = $this->root.'/'.$relative;

            if (!is_dir($path)) {
                if ('preset' === $flag || 'host' === $flag) {
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
        $directory = match ($kind) {
            'css' => $this->root.'/presets/css',
            'js' => $this->root.'/presets/js',
            'mode' => $this->root.'/modes',
            default => $this->root,
        };

        $entries = is_dir($directory) ? scandir($directory) : false;

        if (false === $entries) {
            return [];
        }

        $names = [];
        foreach ($entries as $entry) {
            if ('.' !== $entry && '..' !== $entry && is_dir($directory.'/'.$entry) && ('host' !== $kind || str_starts_with($entry, 'base-'))) {
                $names[] = $entry;
            }
        }

        sort($names);

        return $names;
    }
}
