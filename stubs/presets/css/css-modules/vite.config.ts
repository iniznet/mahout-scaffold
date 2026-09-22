import { writeFileSync, mkdirSync } from 'node:node:fs';
import { dirname, join } from 'node:path';
import { defineConfig, type Plugin } from 'vite';

/*
 * PHP renders markup at runtime and cannot import a hashed class name, so the
 * build must emit the mapping. The css-modules transform exposes the mapping
 * as the module's default export; this plugin captures it and writes
 * build/classmap.json for the theme's $c() resolver.
 */
function emitClassmap(): Plugin {
	const mappings = new Map<string, Record<string, string>>();

	return {
		name: 'mahout-classmap',
		transform(code, id) {
			if (!id.endsWith('.module.css')) {
				return null;
			}
			const match = /export default ([\s\S]+);?\s*$/.exec(code);
			if (!match) {
				return null;
			}
			try {
				mappings.set(id, JSON.parse(match[1] as string) as Record<string, string>);
			} catch {
				return null;
			}
			return null;
		},
		writeBundle() {
			const map: Record<string, string> = {};
			for (const mapping of mappings.values()) {
				Object.assign(map, mapping);
			}
			mkdirSync('build', { recursive: true });
			writeFileSync(join('build', 'classmap.json'), JSON.stringify(map, null, '\t'));
		},
	};
}

export default defineConfig({
	plugins: [emitClassmap()],
	build: {
		outDir: 'build',
		manifest: true,
		emptyOutDir: true,
		rollupOptions: {
			input: {},
		},
	},
});
