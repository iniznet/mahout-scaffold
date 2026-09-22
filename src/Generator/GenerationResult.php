<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

/**
 * What one generation produced: the target directory, the layers copied and
 * the number of files written.
 */
final readonly class GenerationResult
{
    /**
     * @param list<string> $layers
     */
    public function __construct(
        public string $target,
        public array $layers,
        public int $files,
    ) {
    }
}
