<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Release;

use Iniznet\Mahout\Scaffold\Exception\SourceMissing;

/**
 * The theme version, read once from the style.css header the release names
 * the artefact after. A stylesheet without a parsable Version header is a
 * refused release, not a guessed one.
 */
final readonly class Version
{
    private function __construct(
        private string $value,
    ) {
    }

    /** @throws SourceMissing when the header or the stylesheet is missing */
    public static function fromStylesheet(string $sourceDirectory): self
    {
        $style = $sourceDirectory.'/style.css';

        if (!is_file($style)) {
            throw SourceMissing::stylesheet($sourceDirectory);
        }

        $contents = (string) file_get_contents($style);

        if (1 !== preg_match('/^(?:Version|version):\s*(\S+)/m', $contents, $matches)) {
            throw SourceMissing::styleHeader($sourceDirectory);
        }

        return new self($matches[1]);
    }

    public function value(): string
    {
        return $this->value;
    }
}
