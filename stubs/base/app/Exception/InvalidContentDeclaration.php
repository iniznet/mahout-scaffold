<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Exception;

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;

/**
 * A content-model declaration is not one of the three declarative shapes the
 * content package defines.
 */
final class InvalidContentDeclaration extends \InvalidArgumentException implements ThemeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forType(string $debugType): self
    {
        return new self(sprintf(
            'config/content-types.php must list %s, %s or %s entries; got %s.',
            PostType::class,
            Taxonomy::class,
            RestRoute::class,
            $debugType,
        ));
    }
}
