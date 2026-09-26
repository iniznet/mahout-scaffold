# AGENTS.md — mahout-scaffold

The generator package of the mahout family. One CLI binary, one stub tree,
one token map. The full discipline contract lives in the howdah theme
repository; this file states only what is true here and stands alone.

## What this package is

`mahout new <slug>` composes four stub layers - base, a CSS preset, a JS
preset and a mode - into an empty target directory, replacing the token map
on every copied byte. `mahout drift <theme-dir>` verifies that a generated
theme's framework-neutral files still match the stub tree, after substituting
the theme's declared identity back into the stub. The package has no runtime
dependency beyond PHP, and a generated theme never mentions it.

## The laws that bite here

- **Refuse before writing.** Every refusal - an invalid slug, an unknown
  preset, an occupied target - is decided before the first file is created.
  A half-written theme is impossible by construction, and the tests pin it.
- **One way to generate.** The CLI is the only entry point; the `Scaffold`
  class is the only composition point; `Application::run()` is the only
  dispatch table.
- **No presence.** The scaffold's composer manifest requires nothing at
  runtime, the copier never copies a preset's own documentation
  (`PRESET.md`), and the generated manifest's `require` names the mahout
  family, never this package.
- **Exit codes are the contract.** 0 success, 1 failure, 2 usage error
  decided before any work, 3 refusal decided before any statement.

## Layout

```
bin/mahout               entry point; reads argv, composes, exits
src/Console/             the dispatch table
src/Generator/           Slug, Host, TokenSet, StubTree, Copier, Scaffold
src/Drift/               the drift gate and its report
src/Exception/           one condition per class, package marker interface
stubs/common/            the host-neutral skeleton, both analyser configs, the quality workflow, the shared adapters
stubs/base-theme/        the theme layer: entry, hierarchy, theme.json, style.css, render pipeline
stubs/base-plugin/       the plugin layer: entry header, activation, content and field declarations, uninstall
stubs/presets/css/       native | tailwind | css-modules
stubs/presets/js/        native | alpine | stimulus | interactivity
stubs/modes/             classic | block — a theme layer's overlay, refused for a plugin
tests/Unit/              the value objects and the copier
tests/E2E/               one real generation against the real stub tree
```

## A host is a flag, not a fork

`--host=theme|plugin` composes `common` plus one host layer. The split is drawn by
what actually differs: a theme's identity is in `style.css` and a plugin's is in the
main-file header that `plugin_dir_url()` reads as a *file*, so the package manifest,
the entry point and the identity source are per-host, while the analyser configuration,
the artefact set, the npm dependencies and the build adapters are common and exist
once.

`Host` carries the three facts no other layer can decide — the stub layer name, the
Composer type and which file holds identity — and `--mode` is refused rather than
ignored when the host has no template hierarchy to overlay. Drift and release take the
same `--host`; neither derives it from what is on disk, because a gate that chooses its
own rules by looking at the tree reads a half-finished migration as a clean one.

## Presets are directories, not packages

A preset's accepted values are exactly the directories on disk; anything else
is refused before any file is written, with the available list in the
message. A `PRESET.md` inside a preset or mode directory is that preset's own
documentation and is never copied - it is the copier's single excluded name.

## Before you commit

```bash
COMPOSER=composer.dev.json composer install
composer check
```

`composer check` runs the format, PHPStan, Psalm, Rector, PHPUnit, the hook
and translation reference checks, the doctor and the divergence gate. There
are no baselines and no ignores.
