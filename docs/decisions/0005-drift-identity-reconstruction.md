# ADR-0005: the drift gate reconstructs identity from the theme's own declarations

| Field | Value |
|---|---|
| Status | accepted |
| Context | a generated theme carries the stub's files with the tokens replaced; a byte comparison against the raw stub would fail on every fresh generation, and the scaffold must have no presence the theme could declare its generation from |
| Decision | the drift gate reads the two facts the theme declares about itself - the `Text Domain` header in style.css and the last segment of the composer.json PSR-4 prefix - reconstructs the token set from them, substitutes it into the stub and compares byte for byte. A base path that a preset or mode overrides is preset-owned and out of scope |
| Consequences | a fresh generation passes its own gate (pinned by test); a theme that edited a base file drifts loudly; a theme that declares no identity is a usage error, not a drift finding |
| Rejected | a scaffold-written generation manifest inside the theme (scaffold presence); a hash file the scaffold leaves behind (the same defect); comparing against every preset combination (quadratic, and no more honest) |
