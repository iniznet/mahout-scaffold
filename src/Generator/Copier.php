<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\StubUnreadable;

/**
 * Copies one stub layer onto the target, replacing tokens in every file's
 * bytes and in every file's name - a stub path that names the starter slug
 * names the generated slug instead. Directories are walked explicitly; empty
 * directories are carried by the .gitkeep files the stub tree ships.
 *
 * Two names are excluded: PRESET.md is the preset's own documentation and
 * npm.json is a dependency fragment the composition root merges, so neither
 * is theme content and neither reaches what the scaffold generates.
 */
final class Copier
{
    /** The names that are scaffold vocabulary, never theme content. */
    public const array EXCLUDED = ['PRESET.md', 'npm.json'];

    /**
     * @return int the number of files written
     */
    public function copy(string $source, string $target, TokenSet $tokens): int
    {
        $written = 0;
        foreach ($this->entries($source) as $name) {
            if ('.' === $name || '..' === $name || in_array($name, self::EXCLUDED, true)) {
                continue;
            }

            $from = $source.'/'.$name;
            $to = $target.'/'.TokenReplacer::replace($name, $tokens);

            if (is_dir($from)) {
                $this->ensureDirectory($to);
                $written += $this->copy($from, $to, $tokens);

                continue;
            }

            $contents = file_get_contents($from);

            if (false === $contents) {
                throw StubUnreadable::at($from);
            }

            $this->ensureDirectory(dirname($to));

            if (false === file_put_contents($to, TokenReplacer::replace($contents, $tokens))) {
                throw StubUnreadable::at($to);
            }

            ++$written;
        }

        return $written;
    }

    /** @return list<string> */
    private function entries(string $directory): array
    {
        $entries = @scandir($directory);

        if (false === $entries) {
            throw StubUnreadable::at($directory);
        }

        return $entries;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw StubUnreadable::at($path);
        }
    }
}
