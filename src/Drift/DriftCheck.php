<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Drift;

use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;
use Iniznet\Mahout\Scaffold\Exception\StubUnreadable;
use Iniznet\Mahout\Scaffold\Generator\Copier;
use Iniznet\Mahout\Scaffold\Generator\ManifestHash;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenReplacer;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;

/**
 * The drift gate: every framework-neutral file a generated theme carries
 * matches its stubs/base counterpart once the theme's own declared identity
 * is substituted into the stub - the same replacement the generation
 * performed, reconstructed from the two facts the theme declares about
 * itself, the style.css text domain and the composer.json PSR-4 prefix.
 *
 * A theme that edited a base file has drifted and the gate says so. A base
 * path that a CSS preset, a JS preset or a mode overrides is preset-owned,
 * not framework-neutral, and is likewise not the stub's business; extra
 * theme-owned files (features, components) are not either.
 */
final class DriftCheck
{
    /**
     * @throws InvalidInvocation when the theme declares no usable identity
     * @throws StubLayerMissing  when the stub base is not on disk
     * @throws StubUnreadable    when a stub or theme file cannot be read
     */
    public function compare(string $theme): DriftReport
    {
        $base = dirname(__DIR__, 2).'/stubs/base';

        if (!is_dir($base)) {
            throw StubLayerMissing::forLayer('base', $base);
        }

        $root = rtrim($theme, '/\\');
        $tokens = $this->identity($root);
        $overridden = $this->presetOverriddenFiles();
        $files = [];

        foreach ($this->relativeFiles($base, '') as $relative) {
            if (isset($overridden[$relative])) {
                continue;
            }

            // A stub path that names the starter slug names the theme's slug
            // in the generated tree; the name participates in the same map.
            $themePath = $root.'/'.TokenReplacer::replace($relative, $tokens);

            if (!is_file($themePath)) {
                $files[] = new DriftFile($relative, DriftStatus::Missing);

                continue;
            }

            $stubContents = file_get_contents($base.'/'.$relative);
            $themeContents = file_get_contents($themePath);

            if (false === $stubContents || false === $themeContents) {
                throw StubUnreadable::at($relative);
            }

            $expected = TokenReplacer::replace($stubContents, $tokens);

            if ('composer.lock' === $relative) {
                $expected = $this->withThemeDigest($expected, $root);
            }

            $files[] = new DriftFile(
                $relative,
                $expected === $themeContents ? DriftStatus::Identical : DriftStatus::Drifted,
            );
        }

        return new DriftReport($files);
    }

    /**
     * The theme's declared identity: the text domain is the slug; the PSR-4
     * prefix's last segment is the namespace root, which is where the
     * generation's explicit --namespace override lands.
     */
    private function identity(string $root): TokenSet
    {
        $style = @file_get_contents($root.'/style.css');

        if (false === $style || 1 !== preg_match('/^Text Domain:\s*(\\S+)\\s*$/m', $style, $matches)) {
            throw InvalidInvocation::undeclaredIdentity($root);
        }

        $slug = Slug::fromString($matches[1]);

        $namespace = null;
        $composerPath = $root.'/composer.json';

        if (is_file($composerPath)) {
            $decoded = json_decode((string) @file_get_contents($composerPath), true, 16, JSON_THROW_ON_ERROR);

            $autoload = is_array($decoded) ? ($decoded['autoload'] ?? null) : null;

            if (is_array($autoload)) {
                $prefixes = $autoload['psr-4'] ?? [];

                if (is_array($prefixes)) {
                    foreach (array_keys($prefixes) as $prefix) {
                        if (is_string($prefix) && 1 === preg_match('/^(?:[A-Z][A-Za-z0-9]*\\\\)+$/', $prefix)) {
                            $segments = explode('\\', rtrim($prefix, '\\'));
                            $namespace = (string) end($segments);
                        }
                    }
                }
            }
        }

        return null === $namespace
            ? TokenSet::fromSlug($slug)
            : TokenSet::fromSlugWithNamespace($slug, $namespace);
    }

    /**
     * The lock's digest is composed data - a digest of the theme's own
     * manifest, which the generation rewrote - so the stub's digest is
     * replaced by the theme manifest's before the byte comparison.
     *
     * @throws StubUnreadable when the theme's manifest cannot be read
     */
    private function withThemeDigest(string $expectedLock, string $root): string
    {
        $stubLock = json_decode($expectedLock, true, 16, JSON_THROW_ON_ERROR);

        if (!is_array($stubLock) || !is_string($stubLock['content-hash'] ?? null)) {
            return $expectedLock;
        }

        $themeManifest = @file_get_contents($root.'/composer.json');

        if (false === $themeManifest) {
            throw StubUnreadable::at($root.'/composer.json');
        }

        return str_replace(
            $stubLock['content-hash'],
            ManifestHash::of($themeManifest),
            $expectedLock,
        );
    }

    /**
     * The relative paths any preset or mode layer may own, from the stub
     * tree on disk. A base path that appears here is composed by a preset,
     * and the gate says nothing about it.
     *
     * @return array<string, true>
     */
    private function presetOverriddenFiles(): array
    {
        $stubs = dirname(__DIR__, 2).'/stubs';
        $overridden = [];

        foreach (['presets/css', 'presets/js', 'modes'] as $kind) {
            $kindDirectory = $stubs.'/'.$kind;

            foreach (@scandir($kindDirectory) ?: [] as $preset) {
                if ('.' === $preset || '..' === $preset || !is_dir($kindDirectory.'/'.$preset)) {
                    continue;
                }

                foreach ($this->relativeFiles($kindDirectory.'/'.$preset, '') as $relative) {
                    $overridden[$relative] = true;
                }
            }
        }

        return $overridden;
    }

    /** @return list<string> */
    private function relativeFiles(string $directory, string $prefix): array
    {
        $entries = @scandir($directory);

        if (false === $entries) {
            throw StubUnreadable::at($directory);
        }

        $files = [];
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry || in_array($entry, Copier::EXCLUDED, true)) {
                continue;
            }

            $relative = '' === $prefix ? $entry : $prefix.'/'.$entry;

            if (is_dir($directory.'/'.$entry)) {
                $files = [...$files, ...$this->relativeFiles($directory.'/'.$entry, $relative)];

                continue;
            }

            $files[] = $relative;
        }

        sort($files);

        return $files;
    }
}
