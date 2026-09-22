<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Exception;

/**
 * The release's source tree is not what the command needs: the directory is
 * absent, or its style.css carries no parsable Version header, or the output
 * destination cannot receive the artefact.
 */
final class SourceMissing extends \RuntimeException implements ScaffoldException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function directory(string $path): self
    {
        return new self(sprintf('The release source directory does not exist: %s.', $path));
    }

    public static function stylesheet(string $directory): self
    {
        return new self(sprintf('%s/style.css does not exist.', $directory));
    }

    public static function styleHeader(string $directory): self
    {
        return new self(sprintf(
            '%s/style.css carries no parsable Version header.',
            $directory,
        ));
    }

    public static function destinationUnwritable(string $path): self
    {
        return new self(sprintf('The release output directory is not writable: %s.', $path));
    }
}
