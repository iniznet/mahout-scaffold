# ADR-0004: the token map is a fixed four-entry ordered replacement

| Field | Value |
|---|---|
| Status | accepted |
| Context | a generated theme needs its slug, namespace root, constant prefix and display name written into stub bytes; a template engine would add a dependency and a second language to every stub |
| Decision | one ordered, case-sensitive `str_replace` of four tokens: `HOWDAH`, `Howdah`, `howdah` and the display-name phrase `MAHOUT THEME NAME`. The display name is a phrase no slug token can reach, because a fourth case-variant of the slug cannot express spaces. Amendment (phase 7 close): the map applies to stub file NAMES as well as bytes — the POT file must be named for the generated slug, so Copier and DriftCheck run each path segment through the same replacement |
| Consequences | no stub may name a symbol after the starter slug (pinned by test); the map's totality is pinned by a census over the generated tree |
| Rejected | mustache-style placeholders (a stub carrying `{{ }}` is a third language in the tree); a regex engine (the tokens are fixed, and enumeration beats parsing) |
