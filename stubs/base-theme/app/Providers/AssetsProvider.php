<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidEntryDeclaration;
use Iniznet\Howdah\Support\FileManifest;
use Iniznet\Mahout\Assets\AssetsConfig;
use Iniznet\Mahout\Assets\DevMode;
use Iniznet\Mahout\Assets\EntryList;
use Iniznet\Mahout\Assets\EntryPoint;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * Declares the two build artifacts the asset pipeline consumes: the manifest
 * and the classmap. The package's own AssetsProvider, named after this one in
 * the composition root, reads the config and attaches the enqueue hooks.
 *
 * The entry list comes from config/entries.php, the one explicit list; nothing
 * scans the filesystem and nothing globs.
 */
final class AssetsProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $root = dirname(__DIR__, 2);

        $container->set(ClassResolver::fromClassmapFile($root . '/build/classmap.json'));

        $declarations = require $root . '/config/entries.php';

        if (!\is_array($declarations)) {
            throw InvalidEntryDeclaration::notAList(\get_debug_type($declarations));
        }

        $entries = [];

        foreach ($declarations as $declaration) {
            if (!\is_array($declaration)) {
                throw InvalidEntryDeclaration::notADeclaration(\get_debug_type($declaration));
            }

            $row = [];

            foreach ($declaration as $key => $value) {
                if (!\is_string($key)) {
                    throw InvalidEntryDeclaration::notADeclaration('a non-string key');
                }

                $row[$key] = $value;
            }

            $entries[] = EntryPoint::fromArray($row);
        }

        $container->set(new AssetsConfig(
            manifest: new FileManifest($root . '/build/manifest.json'),
            entries: new EntryList($entries),
            baseUrl: untrailingslashit(get_theme_file_uri('build')),
            devMode: DevMode::fromConstant(),
        ));
    }

    public function boot(Container $container): void
    {
        // The package's AssetsProvider attaches the enqueue hooks; the config
        // above is its only input. Nothing is attached here.
    }
}
