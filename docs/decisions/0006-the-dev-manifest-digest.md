# ADR-0006 — the dev manifest carries a content digest on disk

## Status

Accepted (phase 7).

## Context

The generated theme's developer tooling lives in `composer.dev.json`, a manifest that points at the
sibling mahout packages by path repository and installs the analyzer toolchain. Anything that points
at a path can drift: a package moves, a file is hand-edited, a merge drops a dependency. A future
cache or doctor check needs to tell a coherent manifest from a hand-edited one without parsing PHP
or resolving the repositories.

Parsing the manifest at every gate costs a full package resolution. Storing nothing means the first
signal of drift is a failed install minutes later.

## Decision

`composer dev:hash` writes `composer.dev.lock` — a small on-disk record of the dev manifest's
content hash at the time it was last verified. The scaffold writes it at generation time;
`ManifestHash::verify` recomputes and compares on demand.

A mismatch throws `InvalidManifest` and the operation stops. The digest is not advisory in the
silent sense: the file exists so a tool can detect divergence in constant time, and a detected
divergence is refused, not ignored. Regenerating the lock is an explicit act (`composer dev:hash`),
never a side effect of a check.

## Consequences

- The dev manifest's integrity is checkable in O(file size), with no Composer runtime.
- A legitimate edit to `composer.dev.json` requires regenerating `composer.dev.lock` — one command,
  the same discipline as composer.lock itself.
- The lock file is generated, versioned with the theme, and carries no dependency data — it is a
  fingerprint, not a lockfile in Composer's sense. The name is the discipline it borrows, not the
  mechanism it reimplements.
- The generated theme ships the command; the scaffold package owns the implementation.
