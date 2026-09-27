<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

use Iniznet\Mahout\Fields\FieldPanel;
use Iniznet\Mahout\Fields\OptionScreen;

/**
 * A field declaration is not the entry its config file promises. The
 * declarations' own invariants — a panel names a post type, an option screen
 * names the option context — are the field package's, and the package throws
 * {@see \Iniznet\Mahout\Fields\Exception\InvalidPanelDeclaration} for them; this
 * exception answers only the question the theme's config files raise, which
 * is what their entries are at all.
 */
final class InvalidFieldDeclaration extends \InvalidArgumentException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forType(string $debugType): self
    {
        return new self(sprintf(
            'config/fields.php must list %s entries; got %s.',
            FieldPanel::class,
            $debugType,
        ));
    }

    /** The option screens' declaration file lists something that is not one. */
    public static function forOptionScreenType(string $debugType): self
    {
        return new self(sprintf(
            'config/display-options.php must list %s entries; got %s.',
            OptionScreen::class,
            $debugType,
        ));
    }
}
