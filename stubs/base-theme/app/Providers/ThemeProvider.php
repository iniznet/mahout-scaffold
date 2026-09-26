<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Providers;

use Iniznet\Howdah\Support\Hooks;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The theme's own facts: the text domain and the theme supports. Text-domain
 * loading runs on after_setup_theme at priority 10 - after registration,
 * before any Surface renders - and no translated string is produced during
 * boot or schema declaration.
 */
final class ThemeProvider implements ServiceProvider
{
    private const string TEXT_DOMAIN = 'howdah';

    public function register(Container $container): void
    {
        // The theme declares no services of its own yet; features add theirs
        // through their modules.
    }

    public function boot(Container $container): void
    {
        \add_action(Hooks::AFTER_SETUP_THEME, $this->setup(...), priority: 10, accepted_args: 0);
    }

    private function setup(): void
    {
        \load_theme_textdomain(
            self::TEXT_DOMAIN,
            dirname(__DIR__, 2).'/languages',
        );

        \add_theme_support('title-tag');
        \add_theme_support('automatic-feed-links');
        \add_theme_support('align-wide');
        \add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    }
}
