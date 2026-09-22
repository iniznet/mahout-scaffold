<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The editor seam. A feature's schema declares its field groups through the
 * field package's Contracts\FieldRegistry, which the field provider already
 * registered under its contract id; the six-step recipe's first step happens
 * in a feature module, not here. The empty theme declares no groups.
 */
final class EditorProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        // Field groups are declared by features, one StorageTarget per field.
    }

    public function boot(Container $container): void
    {
        // Nothing attaches yet; the editor slices belong to the feature work.
    }
}
