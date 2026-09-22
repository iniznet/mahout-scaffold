<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

/**
 * The token replacement, applied to every copied file's bytes.
 *
 * A codec: it holds no state and resolves no collaborator, which is the
 * contract's permitted static shape. Replacements are case-sensitive and
 * ordered; none of the three slug tokens is a substring of another, and none
 * of them can reach the mahout family's own names. The display-name token is
 * scaffold vocabulary - a phrase no slug token can reach and a generated
 * theme never carries, because the replacement always consumes it.
 */
final class TokenReplacer
{
    private const string DISPLAY_NAME = 'MAHOUT THEME NAME';

    public static function replace(string $contents, TokenSet $tokens): string
    {
        return str_replace(
            ['HOWDAH', 'Howdah', 'howdah', self::DISPLAY_NAME],
            [$tokens->constantPrefix, $tokens->namespaceRoot, $tokens->slug, $tokens->themeName],
            $contents,
        );
    }
}
