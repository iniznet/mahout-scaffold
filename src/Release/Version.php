<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Release;

use Iniznet\Mahout\Scaffold\Exception\SourceMissing;
use Iniznet\Mahout\Scaffold\Generator\Host;

/**
 * The host version, read once from the header of the file core reads identity
 * from — the stylesheet for a theme, the main plugin file for a plugin. A tree
 * whose identity file has no parsable Version header is a refused release, not a
 * guessed one.
 */
final readonly class Version
{
    private function __construct(
        private string $value,
    ) {
    }

    /**
     * @param string $slug the host slug, which names a plugin's entry file
     *
     * @throws SourceMissing when the header or the identity file is missing
     */
    public static function fromIdentity(string $sourceDirectory, Host $host, string $slug): self
    {
        $file = $host->identityFile($slug);
        $identity = $sourceDirectory.'/'.$file;

        if (!is_file($identity)) {
            throw SourceMissing::identityFile($sourceDirectory, $file);
        }

        $contents = (string) file_get_contents($identity);

        if (1 !== preg_match('/^(?:Version|version):\s*(\S+)/m', $contents, $matches)) {
            throw SourceMissing::versionHeader($sourceDirectory, $file);
        }

        return new self($matches[1]);
    }

    public function value(): string
    {
        return $this->value;
    }
}
