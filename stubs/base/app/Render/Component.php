<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Support\ClassResolver;

/**
 * The render base: one markup file, bound variables, exactly one escape per
 * output. A component renders typed props to HTML and never fetches data,
 * touches a global or fires a hook.
 */
abstract class Component
{
    public function __construct(private readonly ClassResolver $classes)
    {
    }

    /** The markup path this component renders. */
    abstract protected function markupPath(): string;

    public function render(): string
    {
        \ob_start();
        $c = $this->classes;
        require $this->markupPath();

        return (string) \ob_get_clean();
    }

    /** The class-name resolver markup references, as $c(). */
    protected function classes(): ClassResolver
    {
        return $this->classes;
    }
}
