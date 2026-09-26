<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

/**
 * What one generation produced: the target directory, the host it is, the layers
 * copied and the number of files written.
 */
final readonly class GenerationResult
{
    /**
     * @param list<string> $layers the composed layers, in copy order, named as the
     *                             invocation named them
     */
    public function __construct(
        public string $target,
        public Host $host,
        public array $layers,
        public int $files,
    ) {
    }
}
