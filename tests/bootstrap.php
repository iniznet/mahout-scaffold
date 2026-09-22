<?php

/**
 * mahout-scaffold test bootstrap.
 *
 * The generator never touches WordPress, so this suite needs no test library:
 * the autoloader is the only requirement. The acceptance run is opt-in through
 * MAHOUT_SCAFFOLD_E2E because it installs a generated theme and runs its full
 * gate set, which takes minutes and needs the five sibling packages on disk.
 */

declare(strict_types=1);

require_once dirname(__DIR__).'/vendor/autoload.php';
