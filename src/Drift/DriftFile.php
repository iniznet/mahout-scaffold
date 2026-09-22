<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Drift;

/**
 * One framework-neutral file's comparison against its stub counterpart.
 */
final readonly class DriftFile
{
    public function __construct(
        public string $path,
        public DriftStatus $status,
    ) {
    }
}
