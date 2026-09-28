# Changelog

All notable changes to this package are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

### Changed

- The generated hook reference is now two documents — `docs/reference/actions.md` and
  `docs/reference/filters.md`, replacing `docs/reference/hooks.md`. A single mixed table
  asked the reader to filter rows for the question they actually came with, which hooks
  fire and forget versus which hooks return a value, and that distinction is already
  recorded on every constant's docblock. `composer hooks:check` gates both files, and a
  package that declares none of one kind still carries the other document, so the gate
  cannot quietly stop running. Adopted from `iniznet/mahout-devtools` 2.0.1, whose
  `hooks:check` and `hooks:generate` take `--outdir=docs/reference`; the canonical command
  text lives in that package's gate manifest, and this repository's scripts are compared
  against it by `composer config:check`.
- The stub manifests under `stubs/base-theme/` and `stubs/base-plugin/` carry the same
  `--outdir` flags, because a skeleton generated with the previous spelling failed
  `composer config:check` on its first run: `StubTreeAcceptanceTest` compares a generated
  tree against the manifest and caught exactly that.

### Added

- The `mahout` binary with three commands: `new`, `drift` and `help`, with the
  family's four-code exit contract (0 success, 1 failure, 2 usage error, 3
  refusal).
- The stub composition: `stubs/base` (the framework-neutral theme skeleton),
  three CSS presets, four JS presets and two modes, every value a directory on
  disk and every unknown value refused before any file is written.
- The token map: one ordered replacement of `HOWDAH`, `Howdah`, `howdah` and
  the display name on every copied byte.
- The composed `package.json`: base owns the manifest fragment and a preset may
  contribute one, so no preset can silently drop another's dependencies.
- The drift gate: a generated theme's framework-neutral files are compared
  byte for byte against the stub tree, through the theme's own declared
  identity, and preset-owned paths are out of scope.
- The generation refusals: an invalid slug, an unknown preset value, a missing
  stub layer and an occupied target are all decided before the first file is
  written.
