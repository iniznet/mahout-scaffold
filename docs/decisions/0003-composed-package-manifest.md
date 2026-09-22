# ADR-0003: package.json is composed, never copied

| Field | Value |
|---|---|
| Status | accepted |
| Context | a CSS preset and a JS preset may both need npm dependencies; a later layer copying its own `package.json` silently drops the earlier layer's |
| Decision | the base layer carries an `npm.json` fragment, a preset may carry one, and the generator merges the fragments in layer order and writes `package.json` once. No preset ships a `package.json` |
| Consequences | every preset combination gets every dependency; the merge is deterministic (base, then CSS, then JS, later wins per key); the drift gate never compares `package.json`, because it is composed data, not a framework-neutral byte file |
| Rejected | byte-overwrite (drops dependencies); a JSON merge inside the copier (makes the generated manifest unprovable); asking the developer to install a preset's dependencies (the generated theme must build as generated) |
