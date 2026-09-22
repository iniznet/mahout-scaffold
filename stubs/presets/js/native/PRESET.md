# JavaScript preset: native

A plain TypeScript entry with no runtime dependency. The module ships as a
Script Module (ES module with an import map), so anything it imports must be
a script module too. Interactivity is written against the DOM: the entry is
where features mount, and a feature that needs shared state uses the module
graph, not a window global.
