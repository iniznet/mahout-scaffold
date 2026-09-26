<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Drift;

use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;
use Iniznet\Mahout\Scaffold\Exception\StubLayerMissing;
use Iniznet\Mahout\Scaffold\Exception\StubUnreadable;
use Iniznet\Mahout\Scaffold\Generator\Copier;
use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\ManifestHash;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenReplacer;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;

/**
 * The drift gate: every framework-neutral file a generated host carries matches
 * its counterpart in `stubs/common` or the host's own layer, once the host's
 * declared identity is substituted into the stub — the same replacement the
 * generation performed, reconstructed from the two facts the host declares about
 * itself: the text domain, which core reads from the stylesheet for a theme and
 * from the main plugin file for a plugin, and the composer.json PSR-4 prefix.
 *
 * The host is an argument rather than a guess. Deriving it from the tree would
 * mean the gate chose which rules applied to a directory by looking at what was
 * in it, which is how a half-finished migration reads as clean.
 *
 * A theme that edited a base file has drifted and the gate says so. A base
 * path that a CSS preset, a JS preset or a mode overrides is preset-owned,
 * not framework-neutral, and is likewise not the stub's business; extra
 * theme-owned files (features, components) are not either.
 */
final class DriftCheck
{
    /**
     * @throws InvalidInvocation when the host declares no usable identity
     * @throws StubLayerMissing  when a stub layer is not on disk
     * @throws StubUnreadable    when a stub or host file cannot be read
     */
    public function compare(string $hostPath, Host $host): DriftReport
    {
        $stubs = dirname(__DIR__, 2).'/stubs';
        $layers = [$stubs.'/common', $stubs.'/'.$host->baseLayer()];

        foreach ($layers as $layer) {
            if (!is_dir($layer)) {
                throw StubLayerMissing::forLayer(basename($layer), $layer);
            }
        }

        $root = rtrim($hostPath, '/\\');
        $tokens = $this->identity($root, $host);
        $overridden = $this->presetOverriddenFiles();
        $files = [];

        // One pass over the union of the two layers, each path attributed to the
        // layer that owns it, so a file is compared exactly once and the failure
        // names the layer a fix belongs to.
        $owned = [];
        foreach ($layers as $layer) {
            foreach ($this->relativeFiles($layer, '') as $relative) {
                $owned[$relative] = $layer;
            }
        }

        ksort($owned);

        foreach ($owned as $relative => $layer) {
            if (isset($overridden[$relative])) {
                continue;
            }

            // A stub path that names the starter slug names the host's slug
            // in the generated tree; the name participates in the same map.
            $themePath = $root.'/'.TokenReplacer::replace($relative, $tokens);

            if (!is_file($themePath)) {
                $files[] = new DriftFile($relative, DriftStatus::Missing);

                continue;
            }

            $stubContents = file_get_contents($layer.'/'.$relative);
            $themeContents = file_get_contents($themePath);

            if (false === $stubContents || false === $themeContents) {
                throw StubUnreadable::at($relative);
            }

            $expected = TokenReplacer::replace($stubContents, $tokens);

            if ('composer.lock' === $relative) {
                $expected = $this->withHostDigest($expected, $root);
            }

            $files[] = new DriftFile(
                $relative,
                $expected === $themeContents ? DriftStatus::Identical : DriftStatus::Drifted,
            );
        }

        return new DriftReport($files);
    }

    /**
     * The host's declared identity: the text domain is the slug; the PSR-4
     * prefix's last segment is the namespace root, which is where the
     * generation's explicit --namespace override lands.
     *
     * A theme's identity file is named by the host kind. A plugin's is named by
     * the slug it has not yet declared, so the main file is found by its header
     * instead: exactly one root-level file carrying both `Plugin Name:` and
     * `Text Domain:`. Two of them is a refused drift run, not a picked one.
     */
    private function identity(string $root, Host $host): TokenSet
    {
        $declared = null;

        if (Host::Theme === $host) {
            $style = @file_get_contents($root.'/style.css');

            if (false !== $style && 1 === preg_match('/^Text Domain:\s*(\\S+)\\s*$/m', $style, $matches)) {
                $declared = $matches[1];
            }
        } else {
            foreach (glob($root.'/*.php') ?: [] as $file) {
                $contents = (string) @file_get_contents($file);

                if (1 === preg_match('/^Plugin Name:/mi', $contents) && 1 === preg_match('/^Text Domain:\s*(\\S+)/mi', $contents, $matches)) {
                    if (null !== $declared) {
                        throw InvalidInvocation::undeclaredIdentity($root, 'two files carrying a Plugin Name header');
                    }

                    $declared = $matches[1];
                }
            }
        }

        if (null === $declared) {
            throw InvalidInvocation::undeclaredIdentity($root, $host->identityFile('<slug>'));
        }

        $slug = Slug::fromString($declared);

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
            ? TokenSet::fromSlug($slug, $host)
            : TokenSet::fromSlugWithNamespace($slug, $host, $namespace);
    }

    /**
     * The lock's digest is composed data - a digest of the theme's own
     * manifest, which the generation rewrote - so the stub's digest is
     * replaced by the theme manifest's before the byte comparison.
     *
     * @throws StubUnreadable when the host's manifest cannot be read
     */
    private function withHostDigest(string $expectedLock, string $root): string
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
     * tree on disk. A shared path that appears here is composed by a preset,
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
