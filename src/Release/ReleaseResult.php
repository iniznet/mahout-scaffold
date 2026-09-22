<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Release;

/**
 * What a release build produced: the artefact's path and the file count it
 * shipped, so the command can report both and a test can assert both.
 */
final readonly class ReleaseResult
{
    public function __construct(
        public string $path,
        public int $files,
    ) {
    }
}
