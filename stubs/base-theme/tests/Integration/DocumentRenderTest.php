<?php

declare(strict_types=1);

namespace Iniznet\Howdah\Tests\Integration;

use Iniznet\Howdah\Render\Document;
use Iniznet\Mahout\Ui\ClassResolver;

/**
 * The document shell's rendered structure. The shell fires wp_head and
 * wp_footer, so only WordPress can prove what it renders; a byte golden
 * cannot exist before core does.
 */
final class DocumentRenderTest extends \WP_UnitTestCase
{
    private function document(): Document
    {
        return new Document(ClassResolver::fromClassmapFile(dirname(__DIR__).'/fixtures/classmap-empty.json'));
    }

    public function testTheShellRendersOneHeadOneMainAndOneSkipLink(): void
    {
        $markup = $this->document()->render();

        self::assertSame(1, substr_count($markup, '<main'), 'exactly one main element');
        self::assertSame(1, substr_count($markup, '<h1'), 'exactly one h1, supplied by Document');
        self::assertSame(1, substr_count($markup, 'href="#main"'), 'the skip link targets main');
        self::assertSame(1, substr_count($markup, 'Skip to content'), 'exactly one skip link, translatable');
    }

    public function testTheShellEscapesEveryClassAttributeItWrites(): void
    {
        $markup = $this->document()->render();

        self::assertMatchesRegularExpression('/<body class="[^"]*"/', $markup, 'body_class renders inside a quoted attribute');
        self::assertMatchesRegularExpression('/<a class="[^"]*" href="#main"/', $markup, 'the skip link class is escaped');
        self::assertMatchesRegularExpression('/<main id="main" class="[^"]*"/', $markup, 'the main class is escaped');
        self::assertMatchesRegularExpression('/<h1 class="[^"]*">/', $markup, 'the h1 class is escaped');
    }

    public function testTheShellFiresHeadAndFooterExactlyOnce(): void
    {
        $head = 0;
        $footer = 0;

        add_action('wp_head', static function () use (&$head): void {
            ++$head;
        });
        add_action('wp_footer', static function () use (&$footer): void {
            ++$footer;
        });

        $this->document()->render();

        self::assertSame(1, $head, 'wp_head fires exactly once');
        self::assertSame(1, $footer, 'wp_footer fires exactly once');
    }

    public function testTheOpeningAndClosingHalvesComposeTheWholeShell(): void
    {
        $document = $this->document();

        self::assertSame($document->render(), $document->opening().$document->closing(), 'the shims render the same shell');
    }
}
