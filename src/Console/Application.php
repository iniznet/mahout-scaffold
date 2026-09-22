<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Console;

use Iniznet\Mahout\Scaffold\Drift\DriftCheck;
use Iniznet\Mahout\Scaffold\Exception\InvalidFlagValue;
use Iniznet\Mahout\Scaffold\Exception\InvalidInvocation;
use Iniznet\Mahout\Scaffold\Exception\InvalidSlug;
use Iniznet\Mahout\Scaffold\Exception\TargetNotEmpty;
use Iniznet\Mahout\Scaffold\Generator\Scaffold;

/**
 * The mahout-scaffold command line.
 *
 * One entry point, one dispatch table. Every command is greppable from here;
 * nothing is registered at file scope.
 *
 * Exit codes follow the family's CLI contract: 0 success, 1 failure, 2 usage
 * error decided before any work, 3 refusal decided before any statement.
 */
final readonly class Application
{
    public function __construct(
        private Scaffold $scaffold,
        private DriftCheck $drift,
    ) {
    }

    /**
     * @param list<string> $arguments
     */
    public function run(array $arguments): int
    {
        $command = $arguments[0] ?? '';

        try {
            return match ($command) {
                'new' => $this->newCommand(array_slice($arguments, 1)),
                'drift' => $this->driftCommand($arguments[1] ?? ''),
                'help', '--help', '-h' => $this->help(),
                default => $this->unknown($command),
            };
        } catch (InvalidSlug|InvalidFlagValue|InvalidInvocation $usage) {
            fwrite(STDERR, 'USAGE: '.$usage->getMessage().PHP_EOL);

            return 2;
        } catch (TargetNotEmpty $refusal) {
            fwrite(STDERR, 'REFUSED: '.$refusal->getMessage().PHP_EOL);

            return 3;
        } catch (\Throwable $failure) {
            fwrite(STDERR, 'FAIL: '.$failure->getMessage().PHP_EOL);

            return 1;
        }
    }

    /**
     * @param list<string> $arguments
     */
    private function newCommand(array $arguments): int
    {
        $slug = array_shift($arguments);

        if (null === $slug || '' === $slug || str_starts_with($slug, '--')) {
            throw InvalidInvocation::missingSlug();
        }

        $options = $this->options($arguments);

        foreach (['css', 'js', 'mode'] as $flag) {
            if ('' === ($options[$flag] ?? null)) {
                throw InvalidInvocation::emptyFlag($flag);
            }
        }

        $css = $options['css'] ?? 'native';
        $js = $options['js'] ?? 'native';
        $mode = $options['mode'] ?? 'classic';
        $namespace = ($options['namespace'] ?? '') !== '' ? $options['namespace'] : null;

        $result = $this->scaffold->generate(
            slugValue: $slug,
            css: $css,
            js: $js,
            mode: $mode,
            namespace: $namespace,
            workingDirectory: $this->cwd(),
        );

        fwrite(STDOUT, sprintf(
            'Generated %s (base + css/%s + js/%s + modes/%s, %d files).%s',
            $result->target,
            $result->layers[0],
            $result->layers[1],
            $result->layers[2],
            $result->files,
            PHP_EOL,
        ));

        return 0;
    }

    private function driftCommand(string $path): int
    {
        if ('' === $path) {
            throw InvalidInvocation::missingPath();
        }

        $report = $this->drift->compare($path);
        fwrite(0 === $report->exitCode() ? STDOUT : STDERR, $report->toTable());

        return $report->exitCode();
    }

    private function help(): int
    {
        fwrite(STDOUT, 'mahout new <slug> [--css=native|tailwind|css-modules] [--js=native|alpine|stimulus|interactivity] [--mode=classic|block] [--namespace=Segment]'.PHP_EOL);
        fwrite(STDOUT, 'mahout drift <theme-dir>   compare a generated theme against stubs/base'.PHP_EOL);

        return 0;
    }

    private function unknown(string $command): int
    {
        fwrite(STDERR, sprintf('Unknown command: %s%s', '' === $command ? '(none)' : $command, PHP_EOL));
        fwrite(STDERR, 'Usage: mahout {new|drift|help}'.PHP_EOL);

        return 2;
    }

    /**
     * @param list<string> $arguments
     *
     * @return array<string, string>
     */
    private function options(array $arguments): array
    {
        $options = [];
        foreach ($arguments as $argument) {
            if (!str_starts_with($argument, '--')) {
                continue;
            }

            $pair = substr($argument, 2);
            $separator = strpos($pair, '=');

            if (false === $separator) {
                $options[$pair] = '';

                continue;
            }

            $options[substr($pair, 0, $separator)] = (string) substr($pair, $separator + 1);
        }

        return $options;
    }

    private function cwd(): string
    {
        $cwd = getcwd();

        return false === $cwd ? '.' : $cwd;
    }
}
