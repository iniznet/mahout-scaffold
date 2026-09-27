<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\E2E;

use Iniznet\Mahout\Scaffold\Drift\DriftCheck;
use Iniznet\Mahout\Scaffold\Drift\DriftStatus;
use Iniznet\Mahout\Scaffold\Exception\TargetNotEmpty;
use Iniznet\Mahout\Scaffold\Generator\Host;
use Iniznet\Mahout\Scaffold\Generator\Scaffold;
use PHPUnit\Framework\TestCase;

/**
 * One real generation against the real stub tree, into a temporary working
 * directory. The census asserts what the scaffold must never leave behind and
 * what the generated theme must always carry; the drift check proves a fresh
 * theme passes its own gate.
 *
 * The heavy acceptance run - installing the generated theme and executing its
 * full gate set against a WordPress test library - is opt-in through
 * MAHOUT_SCAFFOLD_E2E=all, because it takes minutes and needs the sibling
 * packages on disk.
 */
final class GenerationAcceptanceTest extends TestCase
{
    private string $workingDirectory;

    protected function setUp(): void
    {
        $this->workingDirectory = sys_get_temp_dir().'/mahout-scaffold-accept-'.uniqid();
        mkdir($this->workingDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $target = $this->workingDirectory.'/acceptance-theme';

        if (is_dir($target)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($target);
        }

        @rmdir($this->workingDirectory);
    }

    public function testAGeneratedThemeCarriesNoScaffoldPresence(): void
    {
        $result = (new Scaffold())->generate('acceptance-theme', Host::Theme, 'native', 'native', 'classic', null, $this->workingDirectory);

        self::assertSame($this->workingDirectory.'/acceptance-theme', $result->target);
        self::assertGreaterThan(0, $result->files);

        // No preset documentation survives the copy: the scaffold leaves no
        // presence in what it generates.
        $documents = [];
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($result->target, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $item) {
            /** @var \SplFileInfo $item */
            $path = str_replace('\\', '/', $item->getPathname());
            $relative = substr($path, strlen(str_replace('\\', '/', $result->target)) + 1);
            if ('PRESET.md' === $item->getFilename()) {
                $documents[] = $relative;
            }
            $files[] = $relative;
        }

        self::assertSame([], $documents, 'a preset description reached the generated theme');

        // No dependency fragment survives either: package.json is composed
        // from the fragments, never copied.
        self::assertFileDoesNotExist($result->target.'/npm.json');

        // The composed npm manifest carries the base scripts and the theme
        // name; the native presets add no dependency.
        $package = json_decode((string) file_get_contents($result->target.'/package.json'), true, 16, JSON_THROW_ON_ERROR);
        self::assertSame('acceptance-theme', $package['name']);
        self::assertSame('vite build', $package['scripts']['build']);
        self::assertSame(['vite' => '^7.0.0'], $package['devDependencies']);

        // The generated manifest requires the family by version, never the
        // scaffold: the generator must be absent from what it produced.
        $composer = json_decode((string) file_get_contents($result->target.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([
            'php' => '>=8.4',
            'iniznet/mahout-kernel' => '^1.0',
            'iniznet/mahout-assets' => '^1.0',
            'iniznet/mahout-content' => '^1.0',
            'iniznet/mahout-fields' => '^1.0',
            'iniznet/mahout-db' => '^1.0',
            'iniznet/mahout-ui' => '^1.0',
        ], $composer['require']);
        self::assertSame('iniznet/acceptance-theme', $composer['name']);
        self::assertSame('app/', $composer['autoload']['psr-4']['Iniznet\\AcceptanceTheme\\']);

        // The generated manifest names the theme, not the scaffold.
        self::assertStringNotContainsString('scaffold', (string) json_encode($composer, JSON_THROW_ON_ERROR));

        // The theme identity landed in style.css.
        $style = (string) file_get_contents($result->target.'/style.css');
        self::assertMatchesRegularExpression('/^Text Domain:\s*acceptance-theme\s*$/m', $style);
        self::assertMatchesRegularExpression('/^Theme Name:\s*Acceptance Theme\s*$/m', $style);

        // The composition root was rewritten.
        $bootstrap = (string) file_get_contents($result->target.'/app/Bootstrap.php');
        self::assertStringContainsString('namespace Iniznet\\AcceptanceTheme;', $bootstrap);

        // The declared hook names carry the slug, not the starter's name.
        $hooks = (string) file_get_contents($result->target.'/app/Support/Hooks.php');
        self::assertStringContainsString('acceptance-theme/cache/purge', $hooks);

        // Token replacement is total: no stub token survives anywhere.
        $survivors = [];
        foreach ($files as $relative) {
            $contents = (string) file_get_contents($result->target.'/'.$relative);
            foreach (['HOWDAH', 'Howdah', 'howdah', 'MAHOUT THEME NAME'] as $token) {
                if (str_contains($contents, $token)) {
                    $survivors[] = $relative.' still carries '.$token;
                }
            }
        }

        self::assertSame([], $survivors, "tokens survived the generation:\n".implode("\n", $survivors));

        // A fresh generation passes its own drift gate.
        $report = (new DriftCheck())->compare($result->target, Host::Theme);
        $drifted = [];
        foreach ($report->files as $file) {
            if (DriftStatus::Identical !== $file->status) {
                $drifted[] = $file->path.' is '.$file->status->value;
            }
        }

        self::assertSame([], $drifted, "a fresh generation drifted from stubs/base:\n".implode("\n", $drifted));
    }

    public function testAnExplicitNamespaceReachesTheGeneratedAutoloader(): void
    {
        $result = (new Scaffold())->generate('acceptance-theme', Host::Theme, 'tailwind', 'stimulus', 'block', 'House', $this->workingDirectory);

        $composer = json_decode((string) file_get_contents($result->target.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('app/', $composer['autoload']['psr-4']['Iniznet\\House\\']);

        // The composed manifest carries BOTH presets' dependencies: a preset
        // must never drop another's.
        $package = json_decode((string) file_get_contents($result->target.'/package.json'), true, 16, JSON_THROW_ON_ERROR);
        self::assertSame('@hotwired/stimulus', array_key_first($package['dependencies']));
        self::assertSame('^4.1.0', $package['devDependencies']['tailwindcss']);
        self::assertSame('^4.1.0', $package['devDependencies']['@tailwindcss/vite']);
        self::assertSame('^3.2.0', $package['dependencies']['@hotwired/stimulus']);

        self::assertFileExists($result->target.'/templates/index.html');
        self::assertFileExists($result->target.'/resources/css/app.css');
        self::assertFileExists($result->target.'/resources/js/controllers/hello-controller.ts');
        self::assertFileDoesNotExist($result->target.'/stubs');
    }

    public function testAnOccupiedTargetIsRefusedBeforeAnyFileIsWritten(): void
    {
        (new Scaffold())->generate('acceptance-theme', Host::Theme, 'native', 'native', 'classic', null, $this->workingDirectory);

        $this->expectException(TargetNotEmpty::class);

        (new Scaffold())->generate('acceptance-theme', Host::Theme, 'native', 'native', 'classic', null, $this->workingDirectory);
    }

    /**
     * Every one of the twelve CSS and JS combinations, in both modes, composes
     * a theme whose shell markup is identical, whose package manifest carries
     * the union of the two presets' dependencies and whose declared hook
     * reference is the slug's. The full gate run per combination is the
     * opt-in acceptance below; this test is the structural half of the same
     * criterion, and it always runs.
     *
     * @dataProvider everyCombination
     */
    public function testEveryCombinationComposes(string $css, string $js, string $mode): void
    {
        $result = (new Scaffold())->generate('combination-theme', Host::Theme, $css, $js, $mode, null, $this->workingDirectory);

        self::assertFileExists($result->target.'/composer.json');
        self::assertFileExists($result->target.'/AGENTS.md');
        self::assertFileExists($result->target.'/package.json');
        self::assertFileExists($result->target.'/resources/css/tokens.css');
        self::assertFileExists($result->target.'/resources/js/app.ts');
        self::assertFileExists($result->target.'/languages/combination-theme.pot');

        // The shell markup is preset-independent: it references $c() alone.
        $markup = (string) file_get_contents($result->target.'/app/Render/markup/shell-open.php');
        self::assertDoesNotMatchRegularExpression('/tailwind|alpine|stimulus|module\.css/', $markup);

        // Block mode adds the block template layer; classic does not.
        if ('block' === $mode) {
            self::assertFileExists($result->target.'/templates/index.html');
        } else {
            self::assertFileDoesNotExist($result->target.'/templates/index.html');
        }

        // A fresh generation of any combination passes its own drift gate.
        $report = (new DriftCheck())->compare($result->target, Host::Theme);
        $drifted = [];
        foreach ($report->files as $file) {
            if (DriftStatus::Identical !== $file->status) {
                $drifted[] = $file->path.' is '.$file->status->value;
            }
        }

        self::assertSame([], $drifted, "a fresh generation drifted:\n".implode("\n", $drifted));

        $this->removeTree($result->target);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function everyCombination(): iterable
    {
        foreach (['native', 'tailwind', 'css-modules'] as $css) {
            foreach (['native', 'alpine', 'stimulus', 'interactivity'] as $js) {
                foreach (['classic', 'block'] as $mode) {
                    yield $css.' + '.$js.' + '.$mode => [$css, $js, $mode];
                }
            }
        }
    }

    /**
     * The full acceptance run: install every combination and execute its
     * whole gate set. Opt-in because it takes minutes per combination and
     * needs the sibling packages on disk.
     */
    public function testEveryCombinationPassesTheFullGateSet(): void
    {
        if ('all' !== getenv('MAHOUT_SCAFFOLD_E2E')) {
            self::markTestSkipped('The full gate run per combination is opt-in: MAHOUT_SCAFFOLD_E2E=all composer accept');
        }

        $acceptance = sys_get_temp_dir().'/mahout-scaffold-full-'.uniqid();
        mkdir($acceptance, 0777, true);

        try {
            foreach ($this->everyCombination() as [$css, $js, $mode]) {
                $result = (new Scaffold())->generate('full-acceptance', Host::Theme, $css, $js, $mode, null, $acceptance);

                $this->installAndCheck($result->target);

                $this->removeTree($result->target);
            }

            $this->expectNotToPerformAssertions();
        } finally {
            @rmdir($acceptance);
        }
    }

    /**
     * The plugin tree, installed and gated. The theme has always had this run and
     * the plugin has never had any run at all, which is the asymmetry that let a
     * starter ship a field seam that registered nothing: a plugin's admin screens
     * are its whole reason to exist.
     *
     * One combination rather than twelve: the presets layer CSS and JS over the
     * same PHP, and it is the PHP that this tree had never executed.
     */
    public function testAGeneratedPluginPassesTheFullGateSet(): void
    {
        if ('all' !== getenv('MAHOUT_SCAFFOLD_E2E')) {
            self::markTestSkipped('The full gate run per host is opt-in: composer accept');
        }

        $result = (new Scaffold())->generate('acceptance-plugin', Host::Plugin, 'native', 'native', null, null, $this->workingDirectory);

        self::assertFileExists($result->target.'/'.Host::Plugin->identityFile('acceptance-plugin'));

        $this->installAndCheck($result->target);

        $this->removeTree($result->target);
    }

    private function installAndCheck(string $target): void
    {
        $lines = [];
        $code = 0;
        $install = 'cd '.escapeshellarg($target).' && composer install --no-interaction';
        exec($install.' 2>&1', $lines, $code);

        if (0 !== $code) {
            self::fail('composer install failed for '.$target.":\n".implode("\n", array_slice($lines, -10)));
        }

        $lines = [];
        $check = 'cd '.escapeshellarg($target).' && composer check 2>&1';
        exec($check, $lines, $code);

        if (0 !== $code) {
            self::fail("composer check failed for a generated theme:\n".implode("\n", array_slice($lines, -30)));
        }
    }

    private function removeTree(string $target): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($target);
    }
}
