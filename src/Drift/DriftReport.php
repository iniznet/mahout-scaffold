<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Drift;

/**
 * The per-file comparison of a theme against stubs/base, with the exit code
 * the drift gate reports.
 */
final readonly class DriftReport
{
    /**
     * @param list<DriftFile> $files
     */
    public function __construct(public array $files)
    {
    }

    public function exitCode(): int
    {
        foreach ($this->files as $file) {
            if (DriftStatus::Identical !== $file->status) {
                return 1;
            }
        }

        return 0;
    }

    public function toTable(): string
    {
        $lines = ['Path | Status'];
        $lines[] = '--- | ---';
        foreach ($this->files as $file) {
            $lines[] = sprintf('%s | %s', $file->path, $file->status->value);
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
