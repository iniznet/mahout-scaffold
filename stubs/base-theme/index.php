<?php

/**
 * The only hierarchy template. Every request resolves through the
 * composition root's render path; mode overlays change the template layer,
 * never this entry point.
 */

declare(strict_types=1);

Iniznet\Howdah\Bootstrap::render();
