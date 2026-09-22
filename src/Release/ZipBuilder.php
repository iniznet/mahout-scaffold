<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Release;

use Iniznet\Mahout\Scaffold\Exception\SourceMissing;

/**
 * The release zip's builder. The default exclusion set is the contract's --
 * tests, planning docs, dev tooling and source maps are never shipped -- and
 * a call may add to it, but never subtract from it. Files land under the
 * slug's own directory, WordPress's expected install layout.
 */
final readonly class ZipBuilder
{
    /** The family-standard exclusions; every generated theme ships them. */
    public const array DEFAULT_EXCLUDES = [
        '.git',
        'tests',
        'tasks',
        'docs/planning',
        'node_modules',
        'build',
        'vendor',
        '.github',
        '.pi',
        '.phpstan-cache',
    ];

    /** The suffixes nothing ships, whatever the caller asks for. */
    private const array ALWAYS_EXCLUDED_SUFFIXES = ['.map', '.phpunit.result.cache'];

    public function __construct(
        private string $sourceDirectory,
        private string $slug,
        private string $version,
        private string $outputDirectory,
    ) {
    }

    /**
     * One artefact, one build. The exclusion terms are matched against the
     * relative path's head directory and, for files, the whole relative path.
     *
     * @param list<string> $extraExcludes additional relative-path terms
     *
     * @throws SourceMissing when the source or the destination is unusable
     */
    public function build(array $extraExcludes = []): ReleaseResult
    {
        if (!is_dir($this->sourceDirectory)) {
            throw SourceMissing::directory($this->sourceDirectory);
        }

        if (!is_dir($this->outputDirectory) && !mkdir($this->outputDirectory, 0775, true) && !is_dir($this->outputDirectory)) {
            throw SourceMissing::destinationUnwritable($this->outputDirectory);
        }

        $zipPath = sprintf('%s/%s-%s.zip', $this->outputDirectory, $this->slug, $this->version);
        $zip = new \ZipArchive();

        if (true !== $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw SourceMissing::destinationUnwritable($zipPath);
        }

        $files = $this->addTree($zip, [...self::DEFAULT_EXCLUDES, ...$extraExcludes]);
        $zip->close();

        return new ReleaseResult($zipPath, $files);
    }

    /**
     * @param list<string> $excludes
     *
     * @return int the files shipped
     */
    private function addTree(\ZipArchive $zip, array $excludes): int
    {
        $files = 0;

        foreach ($this->files($this->sourceDirectory) as $pathname) {
            // Windows pathnames hand back backslash separators; the archive
            // layout, and the exclusion match against it, is forward-slash.
            $relative = ltrim(str_replace('\\', '/', substr($pathname, strlen(rtrim($this->sourceDirectory, '/\\')))), '/');

            if ($this->excluded($relative, $excludes)) {
                continue;
            }

            $zip->addFile($pathname, $this->slug.'/'.$relative);
            ++$files;
        }

        return $files;
    }

    /**
     * The tree's files, one absolute pathname each, depth first.
     *
     * @return list<string>
     */
    private function files(string $directory): array
    {
        $entries = @scandir($directory);

        if (false === $entries) {
            return [];
        }

        $paths = [];

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $pathname = $directory.'/'.$entry;

            if (is_dir($pathname)) {
                $paths = [...$paths, ...$this->files($pathname)];

                continue;
            }

            $paths[] = $pathname;
        }

        return $paths;
    }

    /** @param list<string> $excludes */
    private function excluded(string $relative, array $excludes): bool
    {
        foreach (self::ALWAYS_EXCLUDED_SUFFIXES as $suffix) {
            if (str_ends_with($relative, $suffix)) {
                return true;
            }
        }

        $head = (string) strstr($relative, '/', true);

        foreach ($excludes as $term) {
            if ($head === $term || $relative === $term) {
                return true;
            }

            // A term with a separator, 'docs/planning', excludes everything
            // under it -- the planning docs' exclusion is a subtree, not a
            // single file.
            if (str_starts_with($relative, $term.'/')) {
                return true;
            }
        }

        return false;
    }
}
