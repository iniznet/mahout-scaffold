<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Render;

use Iniznet\Howdah\Support\ClassResolver;

/**
 * The document shell: one head, one skip link, one main landmark, one footer.
 * wp_head and wp_footer fire here and nowhere else. The opening and closing
 * halves are the same markup the header.php and footer.php shims include, so
 * a legacy caller cannot render a different shell.
 *
 * The shell is a composite of two markup halves, not a single-markup
 * component, so it does not extend Component.
 */
final readonly class Document
{
    public function __construct(private ClassResolver $classes)
    {
    }

    public function render(): string
    {
        return $this->opening().$this->closing();
    }

    /** The opening half, for the header.php compatibility shim. */
    public function opening(): string
    {
        return $this->renderMarkup(dirname(__DIR__).'/Render/markup/shell-open.php');
    }

    /** The closing half, for the footer.php compatibility shim. */
    public function closing(): string
    {
        return $this->renderMarkup(dirname(__DIR__).'/Render/markup/shell-close.php');
    }

    private function renderMarkup(string $path): string
    {
        \ob_start();
        $c = $this->classes;
        require $path;

        return (string) \ob_get_clean();
    }
}
