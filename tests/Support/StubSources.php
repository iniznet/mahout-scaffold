<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Scaffold\Tests\Support;

/**
 * The family sources the stub trees are written against, read straight off disk.
 *
 * A stub is source code that only runs once it has been copied into a generated
 * tree, which means the package carrying it can be entirely green while every
 * tree inside it is broken: nothing compiles a stub, and the defects this audit
 * replaced were invisible to `composer check` in all nine repositories.
 * Resolution against the real sources is the cheapest gate that can see them -
 * no install, no database, no vendor directory - and it reads only what is
 * already on disk in any checkout of the family.
 *
 * Where the siblings are absent - a consumer checkout of the scaffold alone, or
 * a CI job over published refs - the index is empty, the audit says nothing, and
 * the caller skips rather than passes: an empty index proves nothing. The heavy
 * generated-tree run is what verifies published refs.
 *
 * Backslashes are spelled as escapes rather than as literals throughout: this
 * file's own job is reasoning about namespace separators, and a separator that
 * survives JSON and PHP string quoting unchanged is worth one constant.
 *
 * @internal
 */
final class StubSources
{
    private const string SEPARATOR = "\x5c";

    /** A namespace separator as PCRE sees it: one literal backslash. */
    private const string PATTERN_SEPARATOR = "\x5c\x5c";

    /**
     * Both roots a stub can name: the family's, which resolves against the sibling
     * packages, and the host's own, which the generator rewrites to the new
     * namespace and which therefore resolves against the tree.
     */
    private const string FAMILY = 'Iniznet'.self::PATTERN_SEPARATOR.'(?:Mahout|Howdah)'.self::PATTERN_SEPARATOR;

    /**
     * @var array<string, array{methods: list<string>, constants: list<string>, file: string}>|null
     */
    private ?array $index = null;

    private function __construct(
        private readonly string $familyRoot,
    ) {
    }

    /**
     * Prose is not a reference. A doc block that names a method to explain why a
     * provider must not call it is not a call, and a gate that cannot tell the two
     * apart gets silenced rather than read, so the source is tokenised and
     * comments and quoted strings fall out before anything matches.
     */
    private function code(string $contents): string
    {
        $code = '';
        $mode = ini_get('mbstring.func_overload');

        foreach (token_get_all($contents) as $token) {
            if (is_array($token)) {
                $code .= T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] || T_CONSTANT_ENCAPSED_STRING === $token[0] || T_ENCAPSED_AND_WHITESPACE === $token[0]
                    ? preg_replace('/[^\n]/', ' ', $token[1])
                    : $token[1];

                continue;
            }

            $code .= $token;
        }

        return $code;
    }

    public static function fromPackageRoot(string $packageRoot): self
    {
        return new self(dirname($packageRoot));
    }

    public function isAvailable(): bool
    {
        return [] !== $this->classIndex();
    }

    /**
     * Every PHP file in the two stub trees, as absolute forward-slashed paths.
     *
     * @return list<string>
     */
    public function stubFiles(string $packageRoot): array
    {
        $files = [];

        foreach (['base-theme', 'base-plugin'] as $tree) {
            foreach ($this->phpFiles($packageRoot.'/stubs/'.$tree) as $file) {
                $files[] = $file;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * One line per reference a stub makes that nothing can satisfy.
     *
     * A stub names two kinds of class: the family's, which resolve against the
     * sibling packages, and its own host's, which resolve against the tree the file
     * belongs to. Both matter, and only the second is checked by the generated
     * project - the first is checked here because a broken family call in a starter
     * is a broken call shipped to every project, with nobody's gate in between.
     *
     * @param list<string> $files
     *
     * @return list<string>
     */
    public function audit(array $files): array
    {
        $findings = [];

        foreach ($files as $file) {
            $index = $this->resolveIndex($file);
            $contents = $this->code((string) file_get_contents($file));
            $own = $this->declaredClass($contents);

            foreach ($this->importedClasses($contents) as $alias => $fqcn) {
                // An import a file neither calls nor constructs is not a reference;
                // one it constructs with `new` is, even though it never names a member.
                if (!$this->isUsed($alias, $contents)) {
                    continue;
                }

                if (!isset($index[$fqcn])) {
                    $findings[] = $file.': '.$this->short($fqcn).' is imported from '.$this->packageOf($fqcn).', which neither the family nor this tree declares';

                    continue;
                }

                // On this machine is not the same as installed there: a reference the
                // sibling checkout satisfies but the host's own require list does not
                // is a generated project that fails on somebody else's first install.
                $uninstalled = $this->absentRequire($file, $fqcn, $this->requiresFor($file));

                if (null !== $uninstalled) {
                    $findings[] = $uninstalled;

                    continue;
                }

                foreach ($this->usedMembers($alias, $contents) as $reference) {
                    if (isset($index[$fqcn])) {
                        $findings = [...$findings, ...$this->missing($file, $fqcn, $reference[0], $reference[1])];
                    }
                }
            }

            if (null !== $own && isset($index[$own])) {
                foreach (['self', 'static'] as $keyword) {
                    foreach ($this->usedMembers($keyword, $contents) as $reference) {
                        $findings = [...$findings, ...$this->missing($file, $own, $reference[0], $reference[1])];
                    }
                }
            }
        }

        return $findings;
    }

    /**
     * The classes a stub file may name: the family's, plus its own tree's, which
     * is what a generated project will be able to autoload under its own namespace.
     *
     * @return array<string, array{methods: list<string>, constants: list<string>, file: string}>
     */
    private function resolveIndex(string $file): array
    {
        if (1 !== preg_match('#(.*/stubs/base-[a-z]+)/#', str_replace('\\', '/', $file), $root)) {
            return $this->classIndex();
        }

        return $this->classIndex() + $this->treeIndex($root[1]);
    }

    /**
     * @var array<string, array<string, array{methods: list<string>, constants: list<string>, file: string}>>
     */
    private array $trees = [];

    /**
     * @return array<string, array{methods: list<string>, constants: list<string>, file: string}>
     */
    private function treeIndex(string $tree): array
    {
        if (isset($this->trees[$tree])) {
            return $this->trees[$tree];
        }

        $declared = [];

        foreach ($this->phpFiles($tree) as $file) {
            $contents = $this->code((string) file_get_contents($file));
            $name = $this->declaredClass($contents);

            if (null !== $name) {
                $declared[$name] = [
                    'methods' => $this->names('/function ([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $contents),
                    'constants' => $this->names('/const ([A-Z_][A-Z0-9_]*)/', $contents),
                    'file' => $file,
                ];
            }
        }

        return $this->trees[$tree] = $declared;
    }

    /**
     * @return array<string, array{methods: list<string>, constants: list<string>, file: string}>
     */
    private function classIndex(): array
    {
        if (null !== $this->index) {
            return $this->index;
        }

        $declared = [];

        foreach ($this->siblingPackages() as $package) {
            foreach ($this->phpFiles($package.'/src') as $file) {
                $contents = $this->code((string) file_get_contents($file));
                $name = $this->declaredClass($contents);

                if (null !== $name) {
                    $declared[$name] = [
                        'methods' => $this->names('/function ([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $contents),
                        'constants' => $this->names('/const ([A-Z_][A-Z0-9_]*)/', $contents),
                        'file' => $file,
                    ];
                }
            }
        }

        $this->index = $this->withInheritance($declared);

        return $this->index;
    }

    /**
     * Members arrive through parents and traits as surely as through a class's own
     * body, so the index is closed over both before anything is judged missing.
     *
     * @param array<string, array{methods: list<string>, constants: list<string>, file: string}> $declared
     *
     * @return array<string, array{methods: list<string>, constants: list<string>, file: string}>
     */
    private function withInheritance(array $declared): array
    {
        foreach (array_keys($declared) as $fqcn) {
            foreach ($this->lineage((string) $fqcn, $declared) as $ancestor) {
                if (!isset($declared[$ancestor]) || $ancestor === $fqcn) {
                    continue;
                }

                $declared[$fqcn]['methods'] = array_values(array_unique([...$declared[$fqcn]['methods'], ...$declared[$ancestor]['methods']]));
                $declared[$fqcn]['constants'] = array_values(array_unique([...$declared[$fqcn]['constants'], ...$declared[$ancestor]['constants']]));
            }
        }

        return $declared;
    }

    /**
     * @param array<string, array{methods: list<string>, constants: list<string>, file: string}> $declared
     *
     * @return list<string>
     */
    private function lineage(string $fqcn, array $declared, int $depth = 0): array
    {
        if (!isset($declared[$fqcn]) || $depth > 6) {
            return [];
        }

        $contents = $this->code((string) file_get_contents($declared[$fqcn]['file']));
        $lineage = [$fqcn];
        $parent = $this->resolve($this->parentOf($contents), $contents);

        if ('' !== $parent && isset($declared[$parent])) {
            foreach ($this->lineage($parent, $declared, $depth + 1) as $further) {
                $lineage[] = $further;
            }
        }

        if (0 < preg_match_all('/^use ('.self::FAMILY.'[^;]+);/m', $contents, $traits)) {
            foreach ($traits[1] as $trait) {
                $lineage[] = (string) $trait;
            }
        }

        return $lineage;
    }

    private function parentOf(string $contents): string
    {
        return 1 === preg_match('/(?:class|interface|enum) [A-Za-z_][A-Za-z0-9_]* extends ([A-Za-z_][A-Za-z0-9_]*)/', $contents, $parent) ? (string) $parent[1] : '';
    }

    private function resolve(string $short, string $contents): string
    {
        if ('' === $short) {
            return '';
        }

        if (1 === preg_match('/^use ('.self::FAMILY.'[^;]+);/m', $contents, $match) && str_ends_with((string) $match[1], self::SEPARATOR.$short)) {
            return (string) $match[1];
        }

        return $short;
    }

    /**
     * @return list<string>
     */
    private function missing(string $file, string $fqcn, string $member, bool $isConstant): array
    {
        $entry = $this->classIndex()[$fqcn] ?? null;

        if (null === $entry) {
            return [];
        }

        return in_array($member, $isConstant ? $entry['constants'] : $entry['methods'], true)
            ? []
            : [$file.': '.$this->short($fqcn).'::'.$member.($isConstant ? '' : '()').' does not exist on '.$fqcn];
    }

    /**
     * The packages a generated host of this tree will install, read from the tree's
     * own manifest. A stub manifest is what a project starts from, so it is the
     * authority on what the code it ships may name.
     *
     * @return list<string>
     */
    private function requiresFor(string $file): array
    {
        if (1 !== preg_match('#(.*/stubs/base-[a-z]+)/#', str_replace('\\', '/', $file), $root)) {
            return [];
        }

        $manifest = $root[1].'/composer.json';

        if (!is_file($manifest)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
        $requires = is_array($decoded) && is_array($decoded['require'] ?? null) ? array_keys($decoded['require']) : [];

        return array_map(static fn (mixed $name): string => (string) $name, $requires);
    }

    /**
     * @param list<string> $requires
     */
    private function absentRequire(string $file, string $fqcn, array $requires): ?string
    {
        if ([] === $requires || !str_starts_with($fqcn, 'Iniznet'.self::SEPARATOR.'Mahout'.self::SEPARATOR)) {
            return null;
        }

        foreach ($this->packages() as $package) {
            foreach ($package['prefixes'] as $prefix) {
                if (!str_starts_with($fqcn, $prefix)) {
                    continue;
                }

                return in_array($package['name'], $requires, true) ? null : $file.': '.$fqcn.' comes from '.$package['name'].', which this tree does not require';
            }
        }

        return null;
    }

    /**
     * @var list<array{name: string, prefixes: list<string>, directory: string}>|null
     */
    private ?array $packages = null;

    /**
     * @return list<array{name: string, prefixes: list<string>, directory: string}>
     */
    private function packages(): array
    {
        if (null !== $this->packages) {
            return $this->packages;
        }

        $packages = [];

        foreach ($this->siblingPackages() as $directory) {
            $decoded = json_decode((string) file_get_contents($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $name = is_array($decoded) && is_string($decoded['name'] ?? null) ? $decoded['name'] : '';
            $prefixes = [];

            foreach ((array) ($decoded['autoload']['psr-4'] ?? []) as $prefix => $paths) {
                if (is_string($prefix) && str_ends_with($prefix, self::SEPARATOR)) {
                    $prefixes[] = substr($prefix, 0, -1);
                }
            }

            if ('' !== $name) {
                $packages[] = ['name' => $name, 'prefixes' => $prefixes, 'directory' => $directory];
            }
        }

        return $this->packages = $packages;
    }

    /**
     * Whether a name is reached for at all, by call or by construction.
     */
    private function isUsed(string $alias, string $contents): bool
    {
        $quoted = preg_quote($alias, '/');

        return 0 < preg_match('/\\b'.$quoted.'::/', $contents) || 0 < preg_match('/\\bnew\\s+'.$quoted.'\\s*\\(/', $contents);
    }

    /**
     * @return array<string, string> alias => fully qualified name
     */
    private function importedClasses(string $contents): array
    {
        $imports = [];

        if (0 >= preg_match_all('/^use ('.self::FAMILY.'[^;]+)(?: as ([A-Za-z_][A-Za-z0-9_]*))?;/m', $contents, $matches)) {
            return $imports;
        }

        foreach ($matches[1] as $position => $fqcn) {
            $alias = ($matches[2][$position] ?? '') !== '' ? (string) $matches[2][$position] : $this->short((string) $fqcn);
            $imports[$alias] = (string) $fqcn;
        }

        return $imports;
    }

    /**
     * The members an alias is actually asked for, each as [name, isConstant].
     *
     * @return list<array{string, bool}>
     */
    private function usedMembers(string $alias, string $contents): array
    {
        $members = [];
        $quoted = preg_quote($alias, '/');

        if (0 < preg_match_all('/\b'.$quoted.'::\$?([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $contents, $calls)) {
            foreach ($calls[1] as $name) {
                $members[] = [(string) $name, false];
            }
        }

        if (0 < preg_match_all('/\b'.$quoted.'::([A-Z_][A-Z0-9_]*)\b(?!\s*\()/', $contents, $constants)) {
            foreach ($constants[1] as $name) {
                $members[] = [(string) $name, true];
            }
        }

        return $members;
    }

    /**
     * The packages a stub may reference, discovered through their own manifests
     * rather than by naming them, so a family that gains a member needs no change
     * here.
     *
     * @return list<string>
     */
    private function siblingPackages(): array
    {
        $packages = [];

        foreach ((array) glob($this->familyRoot.'/*', GLOB_ONLYDIR) as $candidate) {
            if (!is_string($candidate) || !is_dir($candidate.'/src') || !is_file($candidate.'/composer.json')) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($candidate.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded) && isset($decoded['name']) && is_string($decoded['name']) && str_starts_with($decoded['name'], 'iniznet/mahout-')) {
                $packages[] = str_replace(self::SEPARATOR, '/', $candidate);
            }
        }

        return $packages;
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $item) {
            if ($item->isFile() && 'php' === $item->getExtension()) {
                $files[] = str_replace(self::SEPARATOR, '/', $item->getPathname());
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function names(string $pattern, string $contents): array
    {
        $found = [];

        if (0 < preg_match_all($pattern, $contents, $matches)) {
            $found = array_map(static fn (mixed $name): string => (string) $name, $matches[1]);
        }

        return array_values(array_unique($found));
    }

    private function short(string $fqcn): string
    {
        return substr($fqcn, (int) strrpos($fqcn, self::SEPARATOR) + 1);
    }

    private function packageOf(string $fqcn): string
    {
        return implode(self::SEPARATOR, array_slice(explode(self::SEPARATOR, $fqcn), 0, 3));
    }

    private function declaredClass(string $contents): ?string
    {
        if (1 !== preg_match('/^namespace ([^;]+);/m', $contents, $namespace) || 1 !== preg_match('/^(?:final\\s+|abstract\\s+)?(?:readonly\\s+)?(?:class|interface|trait|enum)\\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $declared)) {
            return null;
        }

        return $namespace[1].self::SEPARATOR.$declared[1];
    }
}
