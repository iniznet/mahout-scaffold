<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;
use Iniznet\Mahout\Scaffold\Exception\StubUnreadable;
use Iniznet\Mahout\Scaffold\Exception\TargetNotEmpty;

/**
 * The generation: validate everything, then copy the stub layers in order and
 * replace the tokens. Every refusal is decided before the first file is
 * written; a half-written theme is impossible by construction.
 */
final readonly class Scaffold
{
    private StubTree $tree;

    public function __construct(?StubTree $tree = null)
    {
        $this->tree = $tree ?? StubTree::fromPackage();
    }

    /**
     * @throws InvalidFlagValue when a flag value names no stub directory
     * @throws TargetNotEmpty   when the target exists and is not empty
     * @throws StubLayerMissing when a required layer is absent
     * @throws StubUnreadable   when the stub tree or the target cannot be read or written
     */
    public function generate(
        string $slugValue,
        string $css,
        string $js,
        string $mode,
        ?string $namespace,
        string $workingDirectory,
    ): GenerationResult {
        $slug = Slug::fromString($slugValue);
        $tokens = null === $namespace
            ? TokenSet::fromSlug($slug)
            : TokenSet::fromSlugWithNamespace($slug, $namespace);

        $layers = $this->tree->layers($css, $js, $mode);

        $target = rtrim($workingDirectory, '/\\').'/'.$slug->value();

        if (is_dir($target) && [] !== (scandir($target) ?: [])) {
            $existing = scandir($target);

            if (false === $existing) {
                throw TargetNotEmpty::at($target);
            }

            $occupied = [];
            foreach ($existing as $entry) {
                if ('.' !== $entry && '..' !== $entry) {
                    $occupied[] = $entry;
                }
            }

            if ([] !== $occupied) {
                throw TargetNotEmpty::at($target);
            }
        }

        if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) {
            throw TargetNotEmpty::at($target);
        }

        $files = 0;
        $copier = new Copier();
        foreach ($layers as $layer) {
            $files += $copier->copy($layer, $target, $tokens);
        }

        $files += $this->writePackageManifest($layers, $target, $tokens);
        $files += $this->refreshLockHash($target);

        return new GenerationResult(
            target: $target,
            layers: [$css, $js, $mode],
            files: $files,
        );
    }

    /**
     * The shipped lock pins the manifest it was produced from; the theme's
     * manifest is rewritten by the token replacement, so the digest is
     * recomputed here. A lock whose digest names another manifest makes every
     * composer command warn about staleness on a fresh generation.
     *
     * @throws StubUnreadable when the lock cannot be read or written
     */
    private function refreshLockHash(string $target): int
    {
        $lockPath = $target.'/composer.lock';

        if (!is_file($lockPath)) {
            return 0;
        }

        $lock = json_decode((string) file_get_contents($lockPath), true, 16, JSON_THROW_ON_ERROR);

        if (!is_array($lock)) {
            throw StubUnreadable::at($lockPath);
        }

        $lock['content-hash'] = ManifestHash::of((string) file_get_contents($target.'/composer.json'));

        if (false === file_put_contents($lockPath, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n")) {
            throw StubUnreadable::at($lockPath);
        }

        return 1;
    }

    /**
     * The generated theme's package manifest is composed, not copied: base
     * owns the fragment and a preset may contribute one, so no preset can
     * silently drop another's dependencies. The fragments are scaffold
     * vocabulary - the copier never copies them.
     *
     * @param list<string> $layers
     *
     * @throws StubUnreadable when a fragment cannot be read
     */
    private function writePackageManifest(array $layers, string $target, TokenSet $tokens): int
    {
        $manifest = [];
        foreach ($layers as $layer) {
            $fragment = $layer.'/npm.json';

            if (!is_file($fragment)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($fragment), true, 16, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw StubUnreadable::at($fragment);
            }

            $manifest = $this->mergeManifest($manifest, $decoded);
        }

        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $encoded) {
            throw StubUnreadable::at($target.'/package.json');
        }

        if (false === file_put_contents($target.'/package.json', TokenReplacer::replace($encoded, $tokens)."\n")) {
            throw StubUnreadable::at($target.'/package.json');
        }

        return 1;
    }

    /**
     * @param array<mixed> $manifest
     * @param array<mixed> $fragment
     *
     * @return array<mixed>
     */
    private function mergeManifest(array $manifest, array $fragment): array
    {
        foreach ($fragment as $key => $value) {
            if (isset($manifest[$key]) && is_array($manifest[$key]) && is_array($value)) {
                /** @var array<mixed> $merged */
                $merged = $manifest[$key];
                $manifest[$key] = $this->mergeManifest($merged = $manifest[$key], $value);

                continue;
            }

            $manifest[$key] = $value;
        }

        return $manifest;
    }
}
