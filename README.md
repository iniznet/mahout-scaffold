# mahout-scaffold

The mahout family's generator. It produces a theme from its stub trees and
then has no presence in the theme it produced.

```bash
composer global require iniznet/mahout-scaffold

mahout new my-theme --css=native --js=stimulus --mode=classic
mahout new my-theme --css=tailwind --js=alpine --mode=block --namespace=House
mahout drift my-theme
```

## What one generation composes

Four layers, copied in order, later layers overwriting earlier files by name:

| Layer | Values | Adds |
|---|---|---|
| base | - | the framework-neutral skeleton: composition root, providers, render shell, quality gates, CI |
| `--css` | `native`, `tailwind`, `css-modules` | the stylesheet entry and, where the preset needs one, its Vite configuration and dependencies |
| `--js` | `native`, `alpine`, `stimulus`, `interactivity` | the TypeScript entry and its runtime dependency |
| `--mode` | `classic`, `block` | the template layer: nothing for classic, a `templates/` directory of block markup for block |

Every value is a directory under `stubs/`. An unknown value is refused before
any file is written, with the available list in the message.

## The token map

Every copied byte passes through one ordered replacement:

| Token | Becomes | From `my-theme` |
|---|---|---|
| `HOWDAH` | the constant prefix | `MY_THEME` |
| `Howdah` | the namespace root | `MyTheme` |
| `howdah` | the slug | `my-theme` |
| `MAHOUT THEME NAME` | the display name | `My Theme` |

`--namespace=Segment` replaces the derived namespace root only; the slug, the
text domain and the constant prefix stay derived from the slug.

## The drift gate

`mahout drift <theme-dir>` re-reads the stub base, substitutes the theme's
declared identity - the `Text Domain` in style.css and the last segment of
the composer.json PSR-4 prefix - and compares byte for byte. Exit 0 means the
theme's framework-neutral files are exactly what the scaffold would produce
today. Exit 1 lists every drifted, missing or unexpected file.

## Exit codes

| Code | Meaning |
|---|---|
| 0 | success |
| 1 | failure |
| 2 | usage error, decided before any work |
| 3 | refusal - an occupied target - decided before any statement |

## Development

```bash
COMPOSER=composer.dev.json composer install
composer check
```

The acceptance suite that installs a generated theme and runs its full gate
set is opt-in:

```bash
composer accept
```

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
