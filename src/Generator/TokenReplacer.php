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

    private const string HOST_TYPE = 'MAHOUT HOST TYPE';

    private const string HOST = 'MAHOUT HOST';

    public static function replace(string $contents, TokenSet $tokens): string
    {
        return str_replace(
            // Ordered: the type token is a prefix of the host token, so the longer
            // one is consumed first and the shorter cannot eat it.
            ['HOWDAH', 'Howdah', 'howdah', self::DISPLAY_NAME, self::HOST_TYPE, self::HOST],
            [$tokens->constantPrefix, $tokens->namespaceRoot, $tokens->slug, $tokens->displayName, $tokens->composerType, $tokens->host],
            $contents,
        );
    }
}
