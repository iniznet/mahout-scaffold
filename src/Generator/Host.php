<?php

/**
 * The kind of host the scaffold generates: a theme or a plugin.
 *
 * A host is a flag, not a fork. The two assemblies share the opcode-cache and
 * analyzer configuration, the licence and conduct files, the issue templates and
 * the quality workflow, the npm dependencies and the preload file — so those live
 * once, in `stubs/common`. What differs is the package manifest, because a plugin
 * has no need of the render pipeline and a theme has no activation hook, and the
 * entry point, because WordPress reads a theme's identity from `style.css` and a
 * plugin's from its main-file header. Those live in the host layer, and nothing is
 * duplicated between them.
 *
 * Template modes belong to the theme layer alone. `modes/classic` and `modes/block`
 * change how the hierarchy resolves a page, which is a question only a theme can be
 * asked; a plugin that renders does so through a block or a route, and no mode
 * overlay would apply to it. Passing `--mode` with `--host=plugin` is refused rather
 * than ignored, because a flag that silently changes nothing is a flag that lies.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;

enum Host: string
{
    case Theme = 'theme';
    case Plugin = 'plugin';

    /**
     * @throws InvalidFlagValue when the value names no host
     */
    public static function fromFlag(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidFlagValue::forValue('host', $value, ['theme', 'plugin']);
    }

    /**
     * The host layer of the stub tree: the files that exist because of what kind of
     * installation this is.
     */
    public function baseLayer(): string
    {
        return match ($this) {
            self::Theme => 'base-theme',
            self::Plugin => 'base-plugin',
        };
    }

    /**
     * The Composer type WordPress's installer reads, and the one value in the
     * manifest no host can guess at.
     */
    public function composerType(): string
    {
        return match ($this) {
            self::Theme => 'wordpress-theme',
            self::Plugin => 'wordpress-plugin',
        };
    }

    /**
     * Whether the template-hierarchy mode layers apply to this host at all.
     */
    public function usesModes(): bool
    {
        return self::Theme === $this;
    }

    /**
     * The file core reads the name, the text domain and the version from: the
     * stylesheet for a theme, the main plugin file for a plugin.
     */
    public function identityFile(string $slug): string
    {
        return match ($this) {
            self::Theme => 'style.css',
            // The entry file is named after the slug, which is also the text domain;
            // core's get_plugin_data() reads the header from it.
            self::Plugin => $slug.'.php',
        };
    }

    /**
     * A template mode is the hierarchy a theme owns, so a plugin neither defaults to
     * one nor silently discards the one it was given.
     *
     * @throws InvalidInvocation when the flag and the host disagree
     */
    public function checkMode(?string $mode): void
    {
        if (!$this->usesModes() && null !== $mode) {
            throw InvalidInvocation::modeWithoutTheme($this->value);
        }
    }
}
