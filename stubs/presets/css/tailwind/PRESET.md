# CSS preset: tailwind

Adds `tailwindcss` and `@tailwindcss/vite` as build-time dependencies and
points the Vite config at the plugin. The utility classes are compiled out of
`resources/css/app.css`; no runtime dependency is added. This is the one
preset where leaving it later means editing markup, because utilities are
permitted in markup - a documented consequence of the choice.
