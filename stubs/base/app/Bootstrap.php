<?php

/**
 * The composition root.
 *
 * Every provider and module is named here, in order, and nowhere else. There
 * is no config file between this list and the registrations it produces.
 */

declare(strict_types=1);

namespace Iniznet\Howdah;

use Iniznet\Howdah\Exception\NotBooted;
use Iniznet\Howdah\Providers\AdminProvider;
use Iniznet\Howdah\Providers\AssetsProvider;
use Iniznet\Howdah\Providers\ContentProvider;
use Iniznet\Howdah\Providers\EditorProvider;
use Iniznet\Howdah\Providers\ThemeProvider;
use Iniznet\Howdah\Render\Document;
use Iniznet\Howdah\Support\ClassResolver;
use Iniznet\Mahout\Assets\AssetsProvider as AssetsPackageProvider;
use Iniznet\Mahout\Content\ContentProvider as ContentPackageProvider;
use Iniznet\Mahout\Db\DbProvider;
use Iniznet\Mahout\Fields\FieldsProvider;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Kernel;

final class Bootstrap
{
    private static ?Kernel $kernel = null;

    /**
     * Boot the theme exactly once. functions.php is the only caller in
     * production; the test bootstrap is the only caller in the suite.
     */
    public static function run(): void
    {
        $kernel = Kernel::inWordPress(self::class);

        $kernel->provider(ThemeProvider::class);
        $kernel->provider(AssetsProvider::class);
        $kernel->provider(AssetsPackageProvider::class);
        $kernel->provider(DbProvider::class);
        $kernel->provider(FieldsProvider::class);
        $kernel->provider(ContentPackageProvider::class);
        $kernel->provider(ContentProvider::class);
        $kernel->provider(EditorProvider::class);
        $kernel->provider(AdminProvider::class);

        $kernel->boot();

        self::$kernel = $kernel;
    }

    /**
     * The declared service graph, resolved by the keys the composition root
     * registered them under.
     */
    public static function services(): Container
    {
        return self::kernel()->services();
    }

    /**
     * The render boundary every request resolves through. index.php is the
     * only template that calls it.
     */
    public static function render(): void
    {
        echo new Document(self::services()->get(ClassResolver::class))->render();
    }

    private static function kernel(): Kernel
    {
        if (!self::$kernel instanceof Kernel) {
            throw NotBooted::beforeRender();
        }

        return self::$kernel;
    }
}
