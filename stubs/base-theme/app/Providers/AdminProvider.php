<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The admin seam. Every metabox, menu page, notice and list column the theme
 * registers is named here and nowhere else. The empty theme registers none:
 * attaching the field layer's save handler, the metaboxes and the field route
 * is the admin slice's composition work.
 */
final class AdminProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // The empty theme registers no admin screens.
    }

    public function boot(Container $container): void
    {
        // No admin registration at file scope, and none here yet.
    }
}
