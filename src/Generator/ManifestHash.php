<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Generator;

use Iniznet\Mahout\Scaffold\Exception\InvalidManifest;

/**
 * The digest composer computes for a manifest, over the keys a lock pins.
 *
 * A codec: it holds no state and resolves no collaborator, which is the
 * contract's permitted static shape. The key list and the encoding are the
 * ones composer's Locker uses; a probe in the publishing repository's task
 * notes verified the shape against composer's own output.
 */
final class ManifestHash
{
    private const string RELEVANT_KEYS = 'name,version,require,require-dev,conflict,replace,provide,minimum-stability,prefer-stable,repositories,extra';

    public static function of(string $composerJsonBytes): string
    {
        $content = json_decode($composerJsonBytes, true, 16, JSON_THROW_ON_ERROR);

        if (!is_array($content)) {
            throw InvalidManifest::notAnObject();
        }

        $relevantPart = [];
        foreach (explode(',', self::RELEVANT_KEYS) as $key) {
            if (array_key_exists($key, $content)) {
                $relevantPart[$key] = $content[$key];
            }
        }
        ksort($relevantPart);

        return md5((string) json_encode($relevantPart));
    }
}
