<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Exception\InvalidFieldDeclaration;
use Iniznet\Howdah\Features\Fields\FieldPanels;
use Iniznet\Howdah\Features\Fields\OptionScreens;
use Iniznet\Howdah\Support\Request;
use Iniznet\Mahout\Fields\Admin\FieldStyles;
use Iniznet\Mahout\Fields\Contracts\FieldRegistry;
use Iniznet\Mahout\Fields\Contracts\OptionScreens as OptionScreensContract;
use Iniznet\Mahout\Fields\Contracts\Panels;
use Iniznet\Mahout\Fields\Contracts\RequestInput;
use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\Hooks as FieldHooks;
use Iniznet\Mahout\Fields\OptionScreen;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The editor seam. A feature's schema declares its field groups in
 * config/fields.php, one FieldPanel per (post type, group) pair, and its
 * settings screens in config/display-options.php, one OptionScreen per
 * option-context group; this provider loads both declarations, registers
 * each group on the field package's registry, and hands the collections to
 * the container under the package's Panels and OptionScreens contracts.
 * `mahout-fields`' Admin\FieldsUiProvider — registered after FieldsProvider
 * by the composition root — turns the panels into the metaboxes, the save
 * entry, the REST read bindings and the write-failure notice, and the option
 * screens into the settings pages, their save entries and their notices, and
 * attaches none of them when a declaration is empty. The empty theme
 * declares neither.
 */
final class EditorProvider implements ServiceProvider
{
    /** @var list<FieldPanel> */
    private array $panels = [];

    /** @var list<OptionScreen> */
    private array $optionScreens = [];

    public function register(Container $container): void
    {
        $declarations = require dirname(__DIR__, 2).'/config/fields.php';

        if (!\is_array($declarations)) {
            throw InvalidFieldDeclaration::forType(\get_debug_type($declarations));
        }

        $panels = [];

        foreach ($declarations as $declaration) {
            if (!$declaration instanceof FieldPanel) {
                throw InvalidFieldDeclaration::forType(\get_debug_type($declaration));
            }

            $panels[] = $declaration;
        }

        $optionDeclarations = require dirname(__DIR__, 2).'/config/display-options.php';

        if (!\is_array($optionDeclarations)) {
            throw InvalidFieldDeclaration::forOptionScreenType(\get_debug_type($optionDeclarations));
        }

        $screens = [];

        foreach ($optionDeclarations as $declaration) {
            if (!$declaration instanceof OptionScreen) {
                throw InvalidFieldDeclaration::forOptionScreenType(\get_debug_type($declaration));
            }

            $screens[] = $declaration;
        }

        $this->panels = $panels;
        $this->optionScreens = $screens;

        $collection = new FieldPanels($panels);

        // The contract ids, not the concrete classes: every consumer — this
        // theme's list columns and the package's admin UI — resolves panels
        // and option screens through the package's contracts, so there is
        // exactly one key to grep for.
        $container->set($collection, Panels::class);
        $container->set(new OptionScreens($screens), OptionScreensContract::class);

        // The stylesheet's URL is this theme's to name: the host-owned mapping
        // the field package's default resolution is the fallback for. The
        // package lives in this theme's vendor directory; a dev checkout's
        // path repository links it, and a link is not a URL.
        $resources = \wp_normalize_path(dirname(__DIR__, 2).'/vendor/iniznet/mahout-fields/resources/');
        $content = \wp_normalize_path(WP_CONTENT_DIR.'/');

        $assetUrl = static function (string $file) use ($resources, $content): ?string {
            $path = $resources.$file;

            return \is_file($path) && \str_starts_with($path, $content)
                ? \content_url(\substr($path, strlen($content)))
                : null;
        };

        $container->set(new FieldStyles(
            url: $assetUrl('fields.css'),
            scriptUrl: $assetUrl('fields.js'),
            panels: $collection,
            screens: new OptionScreens($screens),
        ));

        // The save boundary reads the submitted panel through the package's
        // RequestInput contract; the superglobal is read in Support\Request
        // and nowhere else.
        $container->set(Request::panel(), RequestInput::class);

        // register() of every provider runs before any boot(), so this
        // listener is in place when the field package's boot fires the hook.
        \add_action(
            FieldHooks::REGISTRY_LOADED,
            function (FieldRegistry $registry): void {
                foreach ($this->panels as $panel) {
                    $registry->register($panel->group);
                }

                foreach ($this->optionScreens as $screen) {
                    foreach ($screen->fieldGroups() as $group) {
                        $registry->register($group);
                    }
                }
            },
            priority: 10,
            accepted_args: 1,
        );
    }

    public function boot(Container $container): void
    {
        // The groups are registered; the admin surfaces they imply are
        // attached by the field package's own provider.
    }
}
