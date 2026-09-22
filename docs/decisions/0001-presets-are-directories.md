# ADR-0001: presets are directories on disk, not packages

| Field | Value |
|---|---|
| Status | accepted |
| Context | the generator must offer CSS, JS and template-layer choices without a package per choice, and the accepted values must be greppable from the tree |
| Decision | a preset value is a directory under `stubs/`. The accepted values are exactly the directories on disk; an unknown value is refused before any file is written, with the available list in the message |
| Consequences | adding a preset is copying a directory and writing its files; there is no registry, no config file and no package per preset. The refusal message is generated from the disk, so it cannot drift |
| Rejected | an enum of presets (hard-codes the set and forks the truth from the tree); a composer package per preset (a package per stylesheet is ceremony, not design) |
