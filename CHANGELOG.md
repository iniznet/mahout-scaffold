# Changelog

All notable changes to this package are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

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
