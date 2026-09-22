# CSS preset: css-modules

Component styles live in `*.module.css` files beside the markup that names
them; Vite scopes every class and emits `build/classmap.json`, which the
theme's `$c()` resolver reads at render time. PHP cannot import a hashed
name, so the build emitting the classmap is what keeps markup preset-agnostic.
