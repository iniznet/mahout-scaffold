<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidEntryDeclaration;
use Iniznet\Howdah\Support\FileManifest;
use Iniznet\Howdah\Support\PluginPaths;
use Iniznet\Mahout\Assets\AssetsConfig;
use Iniznet\Mahout\Assets\DevMode;
use Iniznet\Mahout\Assets\EntryList;
use Iniznet\Mahout\Assets\EntryPoint;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * Declares the two build artifacts the asset pipeline consumes: the manifest and
 * the classmap. One line differs from the theme's provider of the same name, and
 * it is the whole reason the asset path belongs to the host rather than to the
 * package: the base URL is the plugin's own build directory.
 *
 * The entry list comes from config/entries.php, the one explicit list; nothing
 * scans the filesystem and nothing globs. A declaration of the wrong shape is
 * refused rather than coerced, because a starter that guesses at an entry ships
 * the guess to every project generated from it.
 */
final class AssetsProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $paths = $container->get(PluginPaths::class);

        $container->set(ClassResolver::fromClassmapFile($paths->path('build/classmap.json')));

        $declarations = require $paths->path('config/entries.php');

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
            manifest: new FileManifest($paths->path('build/manifest.json')),
            entries: new EntryList($entries),
            baseUrl: $paths->buildUrl(),
            devMode: DevMode::fromConstant(),
        ));
    }

    public function boot(Container $container): void
    {
        // The package's AssetsProvider attaches the enqueue hooks; the config above
        // is its only input. Nothing is attached here.
    }
}
