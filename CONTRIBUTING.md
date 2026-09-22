# Contributing

## The short path

1. Install the toolchain: `COMPOSER=composer.dev.json composer install`.
2. Make the change.
3. Run `composer check` until it is green. A gate never passes silently; a
   missing prerequisite fails loudly.
4. Open a pull request against this repository.

The pull request is licensed under GPL-2.0-or-later by the act of opening it.
No contributor licence agreement is required and no copyright assignment is
requested.

## The change-routing rule

Route a change by the kind of change, not by the file path.

| Kind of change | Repository |
|---|---|
| A `Contracts/` interface, a hook constant, or a documented public concrete class | the package that declares it |
| `Internal/` code no contract names | the package that owns it |
| An architecture rule, the self-generated stubs, the test bootstrap, `doctor`, or a CI job shared by every repository | `iniznet/mahout-devtools` |
| A scaffold flag, preset tree, stub, the CLI or the drift gate | `iniznet/mahout-scaffold` |
| A field type, sanitiser, control, save lifecycle or REST route | `iniznet/mahout-fields` |
| A table, index, migration or transaction gateway | `iniznet/mahout-fields` |
| A content type, taxonomy, rewrite rule or REST helper | `iniznet/mahout-content` |
| Asset resolution, the build manifest or entry contexts | `iniznet/mahout-assets` |
| Boot order, the service map, diagnostics or hook registration | `iniznet/mahout-kernel` |
| A Surface, Component, feature repository, mapper, DTO, CSS, JS, admin registration or the request adapter | `iniznet/howdah` |
| A licence, contribution, conduct, security or template document | every repository, in one coordinated change |

**A change that cannot be made in one repository is a contract change.** It is
filed as one change per repository and is not merged until every consumer is on
the new surface.

## What this repository refuses

- A scaffold that appears in what it generated: the copier's excluded names are
  the whole story, and a generated manifest never requires this package.
- A generation that writes before it refuses: every refusal is decided before
  the first file.
- A second way to generate: one CLI, one composition point, one dispatch table.
- A registry of presets: the directories on disk are the registry.

## Pull requests

- `composer check` green, with no baseline and no ignores.
- One pull request, one decision. A new behaviour that a caller can observe
  needs a row in `docs/decisions/` in the same change.
- The acceptance suite that installs a generated theme and runs its full gate
  set is opt-in (`composer accept`); the CI run covers the unit and
  generation suites on every push.
