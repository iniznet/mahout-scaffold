import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

// The entry list is the theme's config/entries.php; the empty starter builds
// nothing yet. The tailwind plugin is this preset's one addition.
export default defineConfig({
	plugins: [tailwindcss()],
	build: {
		outDir: 'build',
		manifest: true,
		emptyOutDir: true,
		rollupOptions: {
			input: {},
		},
	},
});
