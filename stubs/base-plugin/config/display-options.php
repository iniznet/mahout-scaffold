<?php

/**
 * The option screens this plugin registers: one screen per option-context field
 * group, rendered by the field package's settings-page surface.
 *
 * A screen owns its whole form: a documentation page is a screen like any other, and
 * the package attaches a settings page for each one and none at all when this list is
 * empty. A screen declares its content one way - its own group, the one-line shape for
 * a plain field page, or tabs of sections, each either one group's fields or a markup
 * file this plugin ships.
 *
 * A group named only here is registered by this file's provider and read through the
 * field layer like any other, which is why both collections reach one registry.
 *
 * @return list<\Iniznet\Mahout\Fields\OptionScreen>
 */

declare(strict_types=1);

return [];
