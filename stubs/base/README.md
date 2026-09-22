# The theme

A WordPress theme generated from the mahout scaffold: a modular monolith with
one composition root, a typed render pipeline, fields with declared storage
targets, and a quality gate set that runs on the empty theme.

## Installation

The theme is a WordPress theme directory, not a composer package. Its runtime
dependencies come from composer:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-kernel.git" },
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-assets.git" },
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-content.git" },
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-fields.git" },
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-db.git" }
    ]
}
```

```bash
composer install
npm install
npm run build
```

## The composition root

`functions.php` boots the kernel through `Iniznet\Howdah\Bootstrap::run()` and
is eight lines. Every provider and module is named in `app/Bootstrap.php`, in
order, and nowhere else. The declared service graph is readable from
`Bootstrap::services()` by the contract ids the packages resolve.

| Contract | Package | Role |
|---|---|---|
| `Mahout\Kernel\Container` | kernel | the service graph |
| `Mahout\Assets\AssetsConfig` | assets | what the build produced and where it is served from |
| `Mahout\Db\Contracts\SqlConnection` | db | the one `$wpdb` boundary |
| `Mahout\Db\Contracts\TableGateway` | db | bounded statements against theme tables |
| `Mahout\Content\Contracts\Registrar` | content | post types, taxonomies, REST routes |
| `Mahout\Fields\Contracts\FieldRegistry` | fields | field groups and their storage targets |
| `Mahout\Fields\Contracts\FieldReader` | fields | reads a declared field's value |
| `Mahout\Fields\Contracts\FieldWriter` | fields | the save lifecycle's store step |
| `Howdah\Support\ClassResolver` | theme | `$c()` in markup, backed by the build classmap |

## A minimal extension

A feature is a vertical slice under `app/Features/`: a schema, a module, a
repository, a mapper, a DTO, surfaces and components. The five questions in
[AGENTS.md](AGENTS.md) decide every file's home before it is written.

```php
final class SeriesModule
{
    public function register(Container $container): void
    {
        \add_action(Hooks::INIT, function (): void {
            $this->services->get(Registrar::class)->registerPostType(
                PostType::of('series')->withPublic()->withSupports(['title', 'editor']),
            );
        });
    }
}
```

## Compatibility

PHP 8.4, WordPress 7.1. The theme's own classes are versioned with the theme;
the packages' `Contracts/` surfaces are stable within a major and their
`Internal/` code is unguaranteed.

## Architecture

The reasoning behind every rule in [AGENTS.md](AGENTS.md) - the rejected
alternatives, the economics, the delivery roadmap - lives in the publishing
repository's planning corpus and is not published with this theme.

## Licence

GPL-2.0-or-later. See [LICENSE](LICENSE).
