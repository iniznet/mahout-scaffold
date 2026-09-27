<?php

/**
 * The declared option screens, loaded once from config/display-options.php.
 * Every admin surface the option context needs — the settings page, its save
 * entry, its write-failure notice — is derived from this collection and from
 * nothing else, so a screen that is not declared here does not exist
 * anywhere.
 *
 * The collection is the theme's and the screen is the package's: the theme
 * owns the declaration site, and the pairing every settings page is built
 * from is `mahout-fields`' `OptionScreen`. This class answers to the
 * package's OptionScreens contract, which is the only key the composition
 * root binds it under.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Fields;

use Iniznet\Mahout\Fields\Contracts\OptionScreens as OptionScreensContract;
use Iniznet\Mahout\Fields\OptionScreen;

final readonly class OptionScreens implements OptionScreensContract
{
    /** @param list<OptionScreen> $screens */
    public function __construct(private array $screens)
    {
    }

    #[\Override]
    public function isEmpty(): bool
    {
        return [] === $this->screens;
    }

    /**
     * @return \ArrayIterator<int, OptionScreen>
     */
    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->screens);
    }
}
