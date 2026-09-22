import { store } from '@wordpress/interactivity';

store('howdah', {
	state: {
		mounted: false,
	},
	actions: {
		toggle(): void {
			const state = store('howdah').state;
			state.mounted = !state.mounted;
		},
	},
});
