# JavaScript preset: alpine

Adds `alpinejs` as a dependency and starts it from the entry. Alpine
behaviour lives in markup (`x-data`), so a theme that starts on this preset
and later needs a different runtime must edit markup to remove it - the same
documented consequence the tailwind CSS preset carries.
