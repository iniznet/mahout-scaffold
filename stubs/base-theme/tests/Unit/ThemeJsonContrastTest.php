<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Every declared theme.json token pairing meets WCAG AA. The pairings are
 * explicit: settings.custom.mahout.contrastPairings names the palette slugs a
 * pairing uses and whether the pairing is large text, which lowers the
 * threshold to 3:1. An undeclared pairing is an unchecked claim, so the list
 * starts empty on purpose and every pairing a feature declares lands here.
 */
final class ThemeJsonContrastTest extends TestCase
{
    private const float AA_NORMAL = 4.5;

    private const float AA_LARGE = 3.0;

    public function testEveryDeclaredPairingMeetsContrastAA(): void
    {
        $theme = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/theme.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($theme);

        $palette = $this->palette($theme);

        $pairings = $theme['settings']['custom']['mahout']['contrastPairings'] ?? [];

        self::assertIsArray($pairings, 'the declared pairing list must be a list');

        $failures = [];
        foreach ($pairings as $pairing) {
            self::assertIsArray($pairing);

            $foreground = $this->colour($palette, $pairing, 'foreground');
            $background = $this->colour($palette, $pairing, 'background');

            if (null === $foreground || null === $background) {
                $failures[] = sprintf(
                    'a pairing names a palette slug that is not declared (%s on %s)',
                    (string) ($pairing['foreground'] ?? '?'),
                    (string) ($pairing['background'] ?? '?'),
                );

                continue;
            }

            $ratio = $this->contrastRatio($foreground, $background);
            $threshold = true === ($pairing['large'] ?? false) ? self::AA_LARGE : self::AA_NORMAL;

            if ($ratio < $threshold) {
                $failures[] = sprintf('%s on %s is %.2f:1, below the %.1f:1 threshold', $foreground, $background, $ratio, $threshold);
            }
        }

        self::assertSame([], $failures, "Declared token pairings below WCAG AA:\n".implode("\n", $failures));
    }

    /**
     * @param array<mixed> $theme
     *
     * @return array<string, string> palette slug to hex colour
     */
    private function palette(array $theme): array
    {
        $colours = [];

        $entries = $theme['settings']['color']['palette'] ?? [];

        self::assertIsArray($entries);

        foreach ($entries as $entry) {
            self::assertIsArray($entry);

            $slug = $entry['slug'] ?? null;
            $colour = $entry['color'] ?? null;

            if (is_string($slug) && is_string($colour)) {
                $colours[$slug] = $colour;
            }
        }

        return $colours;
    }

    /**
     * @param array<string, string> $palette
     * @param array<mixed>          $pairing
     */
    private function colour(array $palette, array $pairing, string $key): ?string
    {
        $slug = $pairing[$key] ?? null;

        if (!is_string($slug)) {
            return null;
        }

        return $palette[$slug] ?? null;
    }

    private function contrastRatio(string $foreground, string $background): float
    {
        $first = $this->luminance($foreground);
        $second = $this->luminance($background);
        $lighter = max($first, $second);
        $darker = min($first, $second);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (3 === strlen($hex)) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $value = (int) hexdec($hex);

        $channel = static function (int $shift) use ($value): float {
            $normalised = (($value >> $shift) & 0xFF) / 255;

            return $normalised <= 0.03928
                ? $normalised / 12.92
                : (($normalised + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel(16) + 0.7152 * $channel(8) + 0.0722 * $channel(0);
    }
}
