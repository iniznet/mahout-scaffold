# Mode: classic

The classic mode is the base stub tree itself: `index.php` resolves every
request through `Bootstrap::render()`, and no `templates/` directory ships.
This directory is intentionally empty of theme files; it exists so the preset
model is uniform and so the mode name validates against a directory.
