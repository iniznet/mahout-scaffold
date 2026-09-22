# AGENTS.md — howdah discipline contract

This file is the working contract for anyone — human or agent — writing code in this repository. The private planning corpus behind it lives in the publishing repository; this file stands alone.

**Stack:** WordPress 7.1+ · PHP 8.4+ · Vite (build only) · PHPUnit · PHPStan max · Psalm (taint) · Rector · PHP-CS-Fixer

---

## The six laws

1. **Explicit over implicit.** Every dependency, hook, registration and side effect is greppable from the composition root.
2. **One way to do a thing.** No alternative paths kept "just in case".
3. **Fail fast and loud.** No silent fallback, no degraded mode, no environment-dependent behaviour switches.
4. **Small public surface.** `Contracts` plus a documented handful of concrete classes. Everything else is `@internal`.
5. **Tooling enforces what prose promises.** A gate existing only in a markdown file does not exist.
6. **Layer neutrality.** The theme is correct and complete with no object cache, no page cache and no CDN, and gets faster as each is added — with no configuration change and no code change. It never depends on a layer existing, never caps a site because a layer appeared, and it has no scale mode.

---

## What this is not

**The system is a modular monolith** (ARC-23). One process, one database, one deployable artefact. Module boundaries are enforced by `Contracts`, `@internal` and the architecture rules — never by a network.

Runtime distribution — services, an internal RPC layer, a message broker beyond `wp_cron`, separately deployed module processes — is a non-goal. Seven packages are a publishing and contribution model, not a deployment topology; `NAM-02` (one repository per package) and `ARC-23` (one monolith at runtime) are independent decisions.

The refusal list below is the whole of it for this repository; a pull request whose design a decision record has already rejected is closed with a link to the record.

---

## Before you write code

Ask these five questions in order:

1. **Where does this go?** Use the layer table below. Do not improvise a new directory.
2. **Will it be queried?** That decides `StorageTarget` at field declaration time.
3. **Which hook emits this?** Hooks come from Providers and Modules only.
4. **Which editor writes this?** A `Table` field is written only through the field panel or the field REST route. A `Meta` field may also be written through `register_post_meta`.
5. **Who may see it?** That decides the Surface's `Cacheability`. A `Shared` Surface carries no nonce and no per-user or per-role value, and a per-user Surface declares how it is bounded.

---

## Layers — where code goes

| Layer | Location | Owns | Must not |
|---|---|---|---|
| Domain | `app/Features/<Name>/` | queries, repositories, schema, hooks, business rules | render HTML |
| Presentation | `app/Components/`, `app/Features/*/Components/` | rendering typed props to HTML | fetch data, touch globals, fire hooks |
| Composition | `app/Render/` | resolving a request to a Surface | contain domain rules |
| Infrastructure | `app/Providers/` | hook attachment, assets, REST, admin | contain domain rules |

```
Providers --> Modules --> Repositories --> Mapper --> Data (DTO)
                              |
                              v
Surfaces --> Components --> Data (DTO)
```

Arrows point one way only. A Component never imports a Repository. A Repository never imports a Component.

### Vertical slices

The starter registers no feature modules. The tree below is the shape your first feature takes.

```
app/Features/Series/
  SeriesModule.php          # registers hooks
  SeriesSchema.php          # CPT, taxonomies, field groups
  SeriesRepository.php      # ONLY file with WP_Query
  SeriesMapper.php          # ONLY file with WP_Post
  SeriesData.php            # readonly DTO
  Surfaces/
  Components/
```

---

## The six-step feature recipe

1. Declare data in `<Feature>Schema.php`, with an explicit `StorageTarget` per field.
2. Register the module — one line in `app/Bootstrap.php`.
3. Write the query in `<Feature>Repository.php`. Prime caches here.
4. Map to a DTO in `<Feature>Mapper.php`.
5. Compose the page with a Surface and Components. Each component gets its own markup file.
6. Test: unit test every component, integration test the repository and the Surface's query ceiling.

---

## Banned — these fail the build

| Banned | Why |
|---|---|
| `extract()` | Variables appear from nowhere |
| `meta_query` in a public API | No `meta_value` index; one join per clause |
| `posts_per_page => -1` | Unbounded cost |
| Raw hook-name strings | Bypasses the `Hooks` constants |
| `get_post_meta()` / `get_user_meta()` on a registered field | Fields are read through the field layer only |
| Components calling repositories | Breaks the layer contract |
| Components referencing `WP_*` types | Components must be testable without WordPress |
| `template_include` routing | WordPress owns resolution |
| Reflection, service locators, facades | Untraceable dependencies |
| Static access to a service — repository, field reader, field query builder, registry, container | A value constructor is not a service locator; inject the collaborator |
| `__get`, `__set`, `__call`, dynamic properties | Invisible to static analysis |
| Trait properties, or `$this->` from a trait calling undeclared members | Concealed dependencies |
| `new \Exception(...)` or a public exception constructor | Untraceable, message drift |
| `error_log()` outside `Diagnostics` | Production noise |
| `$_GET` / `$_POST` / `$_REQUEST` / `$_SERVER` / `$_FILES` / `$_COOKIE` outside `Iniznet\Howdah\Support\Request` | No request boundary |
| Inline `<script>` or `<style>` echo | CSP hygiene and cacheability |
| PHPStan baselines, `@phpstan-ignore` without a reason | Hides problems instead of fixing them |
| `mixed` where a union is expressible | Static analysis stops working |
| `START TRANSACTION`, `COMMIT` or `ROLLBACK` outside `mahout-db`'s gateway | One owner for the transaction boundary |
| `wp_cache_flush()`, and any `wp_cache_flush_group()` outside the gated cache service | Core returns `false` and emits `_doing_it_wrong()` when the backend reports no support; the fallback is a salt bump. |
| `setcookie()` or `setrawcookie()` | The theme sets no cookie |
| `WP_List_Table` subclasses, and any quick-edit or bulk-edit field write | Neither path can carry the save lifecycle, and neither has a post lock |
| A canonical, `robots` or `description` meta tag, and any hand-set security header | Another party owns those |
| Reflection in production | The fragment key is supplied explicitly; reflection hides the key's contents |
| A dispatch arm with no `Cacheability` declaration, or an `Uncacheable` arm with no stated reason | The argument has no default; an undeclared arm fails a test and fails the reference gate |
| A nonce, a per-user value or a per-role value in a `Shared` Surface | A shared cache would replay one visitor's token to another |
| A cache key whose parts are not enumerable from the site's own content graph | A key space an anonymous visitor can invent is a cache an anonymous visitor can fill |
| A `Cache-Control`, `Vary` or validator header outside the policy §2 | One policy, one owner, one place |
| A vendor purge API call, or any attempt by the theme to purge a page cache or a CDN | The theme owns the seam and the client owns the endpoint §5 |
| A `$wpdb` statement against a howdah table with neither a `LIMIT` nor a primary-key equality | An unbounded statement is a scan §3 |
| A schema query such as `information_schema` on a request path | A schema fact is read once into a non-autoloaded option |
| `LIKE` with a leading wildcard over `post_title`, `post_excerpt` or `post_content` | Measured at 150× to 450× the indexed path §4 |
| `sleep()`, `usleep()`, `set_time_limit()`, or a wait-for-lock loop on a request path | A waiting PHP worker is a worker unavailable to every other request |
| A per-request log line, or query logging, in production | A cost that grows linearly with traffic §6 |

---

## Conventions

### DTOs

`final readonly class`, promoted and fully typed, `list<T>` in docblocks, enums for closed sets, value objects for domain scalars. No `__get`, no `ArrayAccess`, no `toArray()`, no `JsonSerializable`.

Mapping from `WP_Post` happens in a Mapper, never in a DTO and never in a Component.

### Value objects

Enforce invariants with PHP 8.4 property hooks. Use `public private(set)` when a value is externally readable but not externally writable.

### Exceptions

`final`, private constructor, static named constructors, extend the most specific SPL exception, implement the package marker interface, carry typed context getters. Name the condition, not the throw site.

Expected absence returns `?T`. Broken invariants throw.

### Shells and shapes

`final` by default. Interfaces named for the role with no `Interface` suffix. `#[Override]` on every override. Named arguments for optional parameters. No boolean flags.

---

## Storage

> `wp_postmeta` is a load-with-the-entity store, not a query store.

| Need | `StorageTarget` |
|---|---|
| Read with the entity, never filtered | `Meta` |
| Filtered, sorted, aggregated or counted | `Table` |
| Repeater, display-only | `Meta`, versioned JSON |
| Repeater, queried or unbounded | `Table` items table |
| Option-context field | `Meta` always |

`storage` is required on every field. No default.

Repeater payloads are versioned (`{"v":1,"items":[...]}`) and encoded by a dedicated codec, never by the DTO. `JSON_THROW_ON_ERROR` always.

**Limitation:** a `Table` field cannot be bound as a block attribute. Block editor meta binding goes through `register_post_meta`, which only sees meta. If it must live in the editor's meta sidebar, it must be `Meta`. Its write path is the field panel, specified.

**Sensitive values** go in neither target. Constants or environment only.

---

## Admin and editor

- **The admin UI is not optional.** `Table` fields cannot be bound as block attributes, so the field panel and the field REST route are the only write path for `Table` storage.
- `mahout-fields` renders every field control and owns the save lifecycle, the editor registry and the field route. The theme registers screens, renders the shell around them, and styles the controls.
- Registration is greppable: `AdminProvider` names every metabox, menu page, notice and list column. No admin registration at file scope.
- **The save lifecycle order is fixed.** Autosave guard, revision guard, foreign-form guard, post lock, capability, nonce, field shape, sanitise, store, record. It runs in that order on both the classic and the REST path, because core checks the post lock on the classic path and not on the REST one.
- A nonce failure never calls `wp_die()` inside `save_post`; core has already written the post by then.
- `Meta` fields use `register_post_meta` with `show_in_rest`. `Table` fields are read through `register_rest_field` and written through the field route.
- No `WP_List_Table` subclass, and no quick-edit or bulk-edit field write.
- No admin screen without a capability, and no capability compared by role.

---

## Data integrity

- **The table rows and the revision mirror are one transaction.** `START TRANSACTION`, `COMMIT` and `ROLLBACK` appear only inside `mahout-db`'s gateway, because `$wpdb` has no transaction API.
- Every howdah table declares `ENGINE=InnoDB`. A non-transactional engine makes the boundary decorative, and `get_charset_collate()` does not set the engine.
- The gateway never nests a transaction. A plugin must not open one around a field save.
- The lost-update guard is the post lock plus an `expected_hash` comparison inside the transaction. Nothing is merged automatically.
- **A failed field write rolls back everything and notifies.** Nothing is partially applied, nothing is substituted, nothing is retried, and the post is not compensated.
- Every migration implements `up()` and `down()`. An irreversible reversal throws and blocks the whole rollback run before any statement executes.
- Migrations run from `wp mahout migrate`, from `after_switch_theme`, or lazily on `admin_init` for a user with `manage_options`. Never on the front end, never under AJAX or cron.
- The revision mirror is a registered meta key with `revisions_enabled => true`; core copies and restores it, and the field layer rehydrates the table afterwards.

---

## Hooks

Names are `public const` on a `Hooks` class. Never inline.

```
mahout/{package}/{event}      # library
howdah/{domain}/{event}       # theme
```

- Actions never return. Filters always return the first argument.
- Filters pass values and arrays, never mutable WordPress objects. Pass `QueryContext`, not `$wp_query`. Pass `SeriesData`, not `WP_Post`.
- Emit only from Providers and Modules.
- `wp_head` and `wp_footer` fire inside the `Document` component. Do not remove them.

| Priority | Meaning |
|---|---|
| 5 | pre-empt |
| 10 | default |
| 20 | post-process |
| `PHP_INT_MAX` | enforcement |

New hooks are documented first, then added to the inventory. `composer hooks:check` fails if the generated reference is stale.

---

## Cacheability and throughput

- **Every Surface declares its cacheability.** `Cacheability` (`Shared`, `Private`, `Uncacheable`) and `FragmentScope` are required arguments in the dispatch arm, with no default. A `Shared` surface is wrapped in a `CachedFragment`; an `Uncacheable` one states its reason.
- **No layer is required, and no layer changes the code.** Correct at layer 0, faster at layer 5, one path.
- **The theme owns the purge seam; the client owns the endpoint.** Invalidation emits `howdah/cache/purge`; no vendor API is called.
- **Every statement is bounded.** A `LIMIT` or a primary-key equality, always. A sweep is chunked by key, resumable and runtime-capped, and never runs on a request path.
- **Search is an index, not a scan.** The `FULLTEXT` index on `wp_posts` is owned by `mahout-db`; the theme passes only its own tokeniser's output, and falls back loudly to core's query when the index is absent.

---

## Performance

Required, not optional:

```php
update_meta_cache('post', $ids);
update_object_term_cache($ids, $taxonomies);
```

Before mapping any result set. Then one `get_post_meta($id)` per post, not one per field.

Repository query defaults:

```php
'no_found_rows'          => true,   // unless paginating
'update_post_meta_cache' => false,  // we prime ourselves
'update_post_term_cache' => false,  // we prime ourselves
'ignore_sticky_posts'    => true,
'fields'                 => 'ids',  // when only IDs are needed
```

Every new Surface gets a query-ceiling test. Options over ~1 KB are stored `autoload='no'`.

Production prerequisites, asserted by `doctor` and not optional for the declared capacity to hold: OPcache enabled with `validate_timestamps=0` and a preload file, an autoloader generated with `--classmap-authoritative --optimize`, and an `innodb_buffer_pool_size` sized to the working set. None of them is required for correctness; all of them are required for the numbers to be true.

---

## Static access

**Permitted:** named constructors and codecs that hold no state and resolve no collaborator — `Slug::fromString()`, `Media::fromAttachmentId()`, `SeriesStatus::from()`, `CreditsCodec::encode()`, `PercentageOutOfRange::fromInput()` — plus enums and `*::class` constants.

**Banned:** static access to anything that queries, caches, mutates or resolves a collaborator — repositories, field readers, field query builders, registries, containers.

**Boundary and composition-root exceptions, and nothing else:** `Request::fromSuperglobals()`, `Bootstrap::run()`, `Bootstrap::render()`, `Bootstrap::services()`, `Surfaces::resolve()`. The test is not "is it static" but "does it resolve a collaborator".

---

## Errors and security

- **Failures are loud; output is defined.** A thrown exception is recorded through `Diagnostics` at `critical`. Development rethrows it. Production renders the Error Surface with status `500`. No silent fallback, no substituted data, no white screen. The error boundary is a defined render, not a degraded mode — law 3 still governs data and behaviour.
- **Request input has one boundary.** Superglobals are read only inside `Iniznet\Howdah\Support\Request`, which is injected through constructors. State-changing requests use POST, a verified nonce, and a capability check. A REST route without an explicit `permission_callback` fails the build.
- **Sanitize on write, escape on read.** Exactly one escape per output; double escaping is a defect, not defensiveness.
- **User data does not live in a theme-owned table.** Anything that must survive a theme switch belongs in a plugin.

---

## Gates

```bash
composer format     # PHP-CS-Fixer, @PSR12 + @Symfony
composer stan       # PHPStan, max level, no baseline
composer psalm      # Psalm taint
composer arch       # architecture rules
composer rector     # Rector dry-run
composer test       # PHPUnit
composer hooks:check
composer i18n:check # generated POT is current
composer doctor     # installation assembly
composer config:check # divergence: analyzer config and architecture rules are referenced, not copied
composer check      # all of the above
```

`composer check` must pass before every commit. No exceptions, no `--no-verify`.

---

## Contributing

Seven package repositories and one theme. Before opening a pull request, read the change-routing rule in CONTRIBUTING.md — it answers “which repository does this change belong in?” by kind of change, not by file path.

- A change that cannot be made in one repository is a **contract change**. It follows the procedure in 19 §4: add the new surface, deprecate the old one, tag a package minor, adopt in consumers in dependency order (kernel, then assets, db and content, then fields, then the theme), and remove the old surface only at the next major.
- `Contracts/` is a public promise (19 §7). Semantic versioning governs it; an `@internal` class may change in a patch release.
- A new decision needs a file in `docs/decisions/` in the same change, and a changed rule corrects this file in the same change.
- A fork pull request runs the same `composer check`, because `mahout-devtools` resolves over VCS with no secret. The quality workflow is triggered by `pull_request`, never `pull_request_target` (19 §5).
- Every repository references the analyzer configuration and architecture rules from `mahout-devtools`; a repository carrying its own copy has diverged (19 §6).

---

## Required tests

| Must have a test |
|---|
| Every component's rendered output |
| Every value object's invariant, including rejection |
| Every exception's named constructor |
| Every repository query shape |
| Field round-trip on `Meta` and on `Table` |
| `meta` to `table` and `table` to `meta` migration |
| Every Surface's query ceiling |
| Every dispatch arm's declared `Cacheability` and `FragmentScope`, and a stated reason on every `Uncacheable` arm |
| The layer-0 suite: every front-end test passing with no object cache, no page cache and no CDN |
| Byte-identical output for two anonymous visitors on a `Shared` Surface |
| Single-flight: concurrent misses on one key cause exactly one regeneration |
| The orphan sweep's constant statement count per chunk and strictly advancing cursor |
| The search index's presence and exact column list, and result-set parity between the indexed and core paths |
| A malformed search term never reaching `AGAINST` |
| `doctor` failing on opcache off, a missing preload file, or a non-authoritative autoloader |
| Index presence after migration |
| Zero orphans after a post delete |
| A Surface that throws produces the error Surface in production and rethrows in development |
| A user-scoped field's export and erase paths |
| Contrast of every declared `theme.json` token pairing |

No coverage target. Coverage rewards testing getters.

---

## Simplicity rules

- If a class has one public method and one responsibility, that is correct, not a smell.
- Four repeated lines of `render()` boilerplate are cheaper than a trait that breaks `__DIR__`.
- If `functions.php` grows past eight lines, the design is wrong.
- If `Bootstrap.php` needs a config file, the indirection is wrong.
- If you need `@phpstan-ignore`, you need a different design.
- If a name needs explaining at the call site, name it better.

---

## The planning corpus

The reasoning behind every row — the rejected alternatives, the economics, the delivery roadmap — lives in the
publishing repository’s private planning corpus and is not published with this theme. This file
stands alone: every rule a contributor can break is stated above, and the gates enforce them.
