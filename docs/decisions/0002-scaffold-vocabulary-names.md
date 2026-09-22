# ADR-0002: PRESET.md and npm.json are scaffold vocabulary, never copied

| Field | Value |
|---|---|
| Status | accepted |
| Context | a preset or mode layer needs its own documentation, and a dependency fragment the composition root merges - but the scaffold must leave no presence in what it generates |
| Decision | two names are excluded from every copy: `PRESET.md`, a layer's own documentation, and `npm.json`, a dependency fragment the generator merges into the composed `package.json`. The list is one constant, shared by the copier and the drift gate |
| Consequences | a generated theme carries neither name; a preset's documentation lives beside its code without polluting the output |
| Rejected | copying preset documentation into the theme (scaffold presence); a marker file that survives generation; a per-file allow-list in the copier |
