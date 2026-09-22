<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Support;

/**
 * Every hook this theme emits or observes. Names are declared once, here.
 *
 * The two core hooks are constants too: the rule that bans a raw hook name at
 * an emit site applies to a core hook as much as to a theme one.
 */
final class Hooks
{
    /**
     * Core's theme-setup hook. ThemeProvider loads the text domain and
     * declares the theme supports here, at priority 10.
     *
     * @since 1.0
     *
     * @action
     */
    public const string AFTER_SETUP_THEME = 'after_setup_theme';

    /**
     * Core's init hook. ContentProvider registers the declared content model
     * here, after every provider and module booted.
     *
     * @since 1.0
     *
     * @action
     */
    public const string INIT = 'init';

    /**
     * The cache purge seam. The theme owns the seam and emits this action
     * when a content change invalidates a fragment; the client owns the
     * endpoint that listens for it. No vendor purge API is ever called.
     *
     * Declared in this scaffold; the invalidation service that fires it is a
     * feature slice's, and nothing fires it yet.
     *
     * @since 1.0
     *
     * @action
     *
     * @param list<string> $keys the fragment keys the purge covers
     */
    public const string PURGE = 'howdah/cache/purge';
}
