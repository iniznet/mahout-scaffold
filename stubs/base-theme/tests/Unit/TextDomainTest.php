<?php

/**
 * The text domain is declared once and used everywhere. style.css declares it
 * to WordPress; every translation call site carries it; the composition root
 * loads it. A theme whose declaration and call-site domains disagree fails
 * THIS test, on the empty project, before any feature exists.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TextDomainTest extends TestCase
{
    private const string DOMAIN = 'howdah';

    private const string ROOT = __DIR__.'/../..';

    /** @var list<string> */
    private const array TRANSLATION_FUNCTIONS = [
        '__', '_e', '_n', '_x', '_nx', '_ex',
        'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e',
    ];

    public function testStyleCssDeclaresTheTextDomain(): void
    {
        $header = (string) file_get_contents(self::ROOT.'/style.css');

        self::assertMatchesRegularExpression(
            '/^Text Domain:\s*'.preg_quote(self::DOMAIN, '/').'\s*$/m',
            $header,
            'style.css must declare the theme text domain the call sites use.',
        );
    }

    public function testEveryTranslationCallSiteCarriesTheDeclaredDomain(): void
    {
        $offences = [];

        foreach ($this->phpFiles(self::ROOT.'/app') as $file) {
            $tokens = token_get_all((string) file_get_contents($file));

            foreach ($this->translationDomains($tokens) as $found) {
                if (self::DOMAIN !== $domain = $this->stripQuotes($found)) {
                    $offences[] = $file.': '.$domain;
                }
            }
        }

        self::assertSame([], $offences, "Translation call sites must use the declared text domain.\n".implode("\n", $offences));
    }

    public function testTheCompositionRootLoadsTheDeclaredDomain(): void
    {
        $offences = [];

        foreach ($this->phpFiles(self::ROOT.'/app') as $file) {
            $tokens = token_get_all((string) file_get_contents($file));
            $count = count($tokens);

            foreach ($tokens as $index => $token) {
                if (!is_array($token) || T_STRING !== $token[0] || 'load_theme_textdomain' !== $token[1]) {
                    continue;
                }

                $found = $this->firstStringArgument($tokens, $index, $count);

                if (null === $found || self::DOMAIN !== $this->stripQuotes($found)) {
                    $offences[] = $file.': '.($found ?? 'no domain argument');
                }
            }
        }

        self::assertSame([], $offences, 'load_theme_textdomain() must use the declared text domain.');
    }

    /**
     * The domain is the LAST string literal inside each translation call's
     * parentheses, which is where every function in the set places it.
     *
     * @param list<mixed> $tokens
     *
     * @return list<string> raw quoted literals, one per call site
     */
    private function translationDomains(array $tokens): array
    {
        $count = count($tokens);
        $found = [];

        foreach ($tokens as $index => $token) {
            if (!is_array($token) || T_STRING !== $token[0] || !in_array($token[1], self::TRANSLATION_FUNCTIONS, true)) {
                continue;
            }

            $domain = $this->lastStringArgument($tokens, $index, $count);

            if (null !== $domain) {
                $found[] = $domain;
            }
        }

        return $found;
    }

    /**
     * @param list<mixed> $tokens
     */
    private function lastStringArgument(array $tokens, int $from, int $count): ?string
    {
        $depth = 0;
        $last = null;

        for ($i = $from + 1; $i < $count; ++$i) {
            $token = $tokens[$i];

            if ('(' === $token) {
                ++$depth;

                continue;
            }

            if (')' === $token) {
                if (0 === $depth) {
                    return $last;
                }

                --$depth;

                continue;
            }

            if (0 === $depth && is_array($token) && T_CONSTANT_ENCAPSED_STRING === $token[0]) {
                $last = (string) $token[1];
            }
        }

        return $last;
    }

    /**
     * @param list<mixed> $tokens
     */
    private function firstStringArgument(array $tokens, int $from, int $count): ?string
    {
        $depth = 0;

        for ($i = $from + 1; $i < $count; ++$i) {
            $token = $tokens[$i];

            if ('(' === $token) {
                ++$depth;

                continue;
            }

            if (')' === $token) {
                if (0 === $depth) {
                    return null;
                }

                --$depth;

                continue;
            }

            if (0 === $depth && is_array($token) && T_CONSTANT_ENCAPSED_STRING === $token[0]) {
                return (string) $token[1];
            }
        }

        return null;
    }

    private function stripQuotes(string $quoted): string
    {
        return trim($quoted, "'\"");
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $entries = scandir($directory);

        if (false === $entries) {
            return [];
        }

        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_dir($path)) {
                $files = [...$files, ...$this->phpFiles($path)];

                continue;
            }

            if (str_ends_with($entry, '.php')) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
