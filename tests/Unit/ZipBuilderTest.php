<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Unit;

use Iniznet\Mahout\Scaffold\Exception\SourceMissing;
use Iniznet\Mahout\Scaffold\Release\Version;
use Iniznet\Mahout\Scaffold\Release\ZipBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The release target's contract: the artefact carries the slug's own
 * directory, the version comes from the style.css header, and the dev tree
 * -- tests, planning docs, node_modules, source maps -- never ships.
 */
final class ZipBuilderTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        $this->source = sys_get_temp_dir().'/mahout-release-'.uniqid();
        @mkdir($this->source.'/app', 0777, true);
        @mkdir($this->source.'/tests/Unit', 0777, true);
        @mkdir($this->source.'/docs/planning', 0777, true);
        @mkdir($this->source.'/node_modules/pkg', 0777, true);

        file_put_contents($this->source.'/style.css', "/*\nTheme Name: Fixture\nVersion: 2.4.1\n*/\n");
        file_put_contents($this->source.'/index.php', '<?php // shipped ');
        file_put_contents($this->source.'/app/Component.php', '<?php // shipped ');
        file_put_contents($this->source.'/app/app.js.map', '{ }');
        file_put_contents($this->source.'/tests/Unit/SecretTest.php', '<?php // never shipped ');
        file_put_contents($this->source.'/node_modules/pkg/index.js', '// never shipped ');
        file_put_contents($this->source.'/docs/planning/00-overview.md', '# never shipped ');
    }

    protected function tearDown(): void
    {
        $zip = $this->source.'/build/fixture-2.4.1.zip';

        if (is_file($zip)) {
            unlink($zip);
        }

        self::rrmdir($this->source);
    }

    public function testTheArtefactExcludesTheDevTreeAndCarriesTheSlugDirectory(): void
    {
        $result = $this->builder()->build();

        self::assertFileExists($result->path);

        $zip = new \ZipArchive();
        $zip->open($result->path);
        $names = [];

        for ($index = 0; $index < $zip->numFiles; ++$index) {
            $names[] = (string) $zip->getNameIndex($index);
        }

        $zip->close();

        self::assertContains('fixture/index.php', $names);
        self::assertContains('fixture/app/Component.php', $names);
        self::assertNotContains('fixture/tests/Unit/SecretTest.php', $names);
        self::assertNotContains('fixture/app/app.js.map', $names);
        self::assertNotContains('fixture/node_modules/pkg/index.js', $names);
        self::assertNotContains('fixture/docs/planning/00-overview.md', $names);
    }

    public function testTheVersionComesFromTheStyleHeader(): void
    {
        self::assertSame('2.4.1', Version::fromStylesheet($this->source)->value());
    }

    public function testAMissingVersionHeaderIsARefusal(): void
    {
        file_put_contents($this->source.'/style.css', 'no version header here');

        $this->expectException(SourceMissing::class);
        $this->expectExceptionMessage('Version header');

        Version::fromStylesheet($this->source);
    }

    public function testAMissingSourceTreeIsARefusal(): void
    {
        $this->expectException(SourceMissing::class);
        $this->expectExceptionMessage('does not exist');

        new ZipBuilder($this->source.'/no-such-dir', 'fixture', '1.0.0', $this->source.'/build')->build();
    }

    private function builder(): ZipBuilder
    {
        return new ZipBuilder($this->source, 'fixture', '2.4.1', $this->source.'/build');
    }

    private static function rrmdir(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
