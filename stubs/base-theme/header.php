<?php

/**
 * Compatibility shim: a caller of get_header() receives the shell's opening
 * half, resolved through the composition root like every other render.
 */

declare(strict_types=1);

echo Iniznet\Howdah\Bootstrap::services()->get(Iniznet\Howdah\Render\Document::class)->opening();
