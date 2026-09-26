# ADR-0007 — A host is a flag, not a fork

Status: accepted

## Context

The scaffold composed one tree: `stubs/base`, described in its own layout as "the
framework-neutral theme skeleton". It was not framework-neutral; it was a theme, with
the two files core reads a theme's identity from (`style.css` and `theme.json`) mixed in
with the licence, the analyser configuration and the quality workflow, none of which care
what kind of installation they are in.

Adding a plugin host to that tree has one obvious and wrong answer: copy `stubs/base` to
`stubs/base-plugin` and edit thirty files. The two trees would then disagree in silence —
the drift gate compares one directory, the artefact set would be maintained twice, and a
fix to the quality workflow would land in one tree and not the other. That is not a second
kind of host; it is a fork with a flag pasted on top.

The two assemblies genuinely do differ, and the differences are not cosmetic:

| Fact | Theme | Plugin |
|---|---|---|
| Where core reads name, text domain and version | `style.css` | the main plugin file header |
| Composer type | `wordpress-theme` | `wordpress-plugin` |
| URL of its own build directory | `get_theme_file_uri('build')` | `plugin_dir_url($mainFile).'build'` — and that function takes a **file**, not a directory |
| When the schema first installs | `after_switch_theme`, because a theme has no activation hook | `register_activation_hook()`, which core resolves by plugin basename |
| What it can render | the request: `index.php`, the dispatch table, `theme.json` | a block, a route, a screen — never the hierarchy |

## Decision

`--host=theme|plugin`. The stub tree is `common` plus one host layer, and `Host` carries
exactly the three facts no other layer can decide: the layer name, the Composer type and
which file holds identity.

- `stubs/common/` — analyser configuration, artefact files, issue templates, quality
  workflow, npm manifest, preload, phpunit configuration, languages, the two build
  adapters (`ClassResolver`, `FileManifest`) and their test.
- `stubs/base-theme/` — the hierarchy entry points, `theme.json`, `style.css`, the render
  pipeline, the theme's own exception marker and its providers.
- `stubs/base-plugin/` — the header entry, `uninstall.php`, `Bootstrap::run(string
  $pluginFile)`, the injected `PluginPaths`, the content/field/entry declarations, the
  plugin's exception marker.

Drift and release take the same `--host`. Deriving the host from what is on disk was
rejected: the gate would then choose its own rules by inspecting the tree it is auditing,
and a half-migrated installation — both identity files present, or neither — would read as
a clean one. A refusal is the correct answer to that state, and a refusal needs an
expected value to compare against.

`--mode` composes for a theme only and is refused for a plugin rather than ignored.
Silently dropping a flag is how a plugin ends up claiming a template mode it never used.

## Consequences

A generated theme is byte-for-byte what the pre-split scaffold produced, asserted by
diffing a fresh generation against a captured baseline: the restructure moved files
between layers without moving a byte, which is the only way this decision can be made
without also changing every theme in existence.

The plugin layer is a skeleton, not a worked example — the same shape the theme layer
ships, whose `app/Features/` is a `.gitkeep`. The family's worked example is the
installation built from it: `wp-content/themes/howdah` carries `Series`, and the
installed plugin carries its own feature.

Two host layers is the whole of the duplication this decision accepts, and it is
duplication of files that must differ: an exception marker, an entry point and a provider
that resolves a URL. Nothing that is host-neutral lives in a host layer, and the drift
gate is what keeps it that way — a shared file edited in a generated host reports as
drift.
