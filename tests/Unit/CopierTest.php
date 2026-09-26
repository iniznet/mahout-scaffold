<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\StubUnreadable;
use Iniznet\Mahout\Scaffold\Generator\Copier;
use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\Slug;
use Iniznet\Mahout\Scaffold\Generator\TokenSet;
use PHPUnit\Framework\TestCase;

final class CopierTest extends TestCase
{
    private string $source;

    private string $target;

    protected function setUp(): void
    {
        $this->source = sys_get_temp_dir().'/mahout-copier-src-'.uniqid();
        $this->target = sys_get_temp_dir().'/mahout-copier-dst-'.uniqid();

        mkdir($this->source.'/nested/deeper', 0777, true);
        file_put_contents($this->source.'/plain.txt', 'HOWDAH Howdah howdah');
        file_put_contents($this->source.'/nested/marker.gitkeep', '');
        file_put_contents($this->source.'/nested/deeper/stub.php', "<?php 'howdah';");
        file_put_contents($this->source.'/PRESET.md', '# never copied');
        file_put_contents($this->source.'/npm.json', '{"dependencies":{}}');
    }

    protected function tearDown(): void
    {
        foreach ([$this->source, $this->target] as $root) {
            if (is_dir($root)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST,
                );
                foreach ($iterator as $item) {
                    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
                }
                rmdir($root);
            }
        }
    }

    private function tokens(): TokenSet
    {
        return TokenSet::fromSlug(Slug::fromString('my-theme'), Host::Theme);
    }

    public function testItCopiesRecursivelyAndReplacesTokens(): void
    {
        $written = (new Copier())->copy($this->source, $this->target, $this->tokens());

        self::assertSame(3, $written);
        self::assertSame('MY_THEME MyTheme my-theme', (string) file_get_contents($this->target.'/plain.txt'));
        self::assertFileExists($this->target.'/nested/marker.gitkeep');
        self::assertSame("<?php 'my-theme';", (string) file_get_contents($this->target.'/nested/deeper/stub.php'));
    }

    public function testScaffoldVocabularyIsNeverCopied(): void
    {
        (new Copier())->copy($this->source, $this->target, $this->tokens());

        self::assertFileDoesNotExist($this->target.'/PRESET.md');
        self::assertFileDoesNotExist($this->target.'/npm.json');
    }

    public function testAnUnreadableSourceDirectoryIsALoudFailure(): void
    {
        $this->expectException(StubUnreadable::class);

        (new Copier())->copy($this->source.'/missing', $this->target, $this->tokens());
    }
}
