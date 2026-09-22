import { Controller } from '@hotwired/stimulus';

export default class HelloController extends Controller {
	static targets = ['name'] as const;

	declare readonly nameTarget: HTMLElement | null;

	connect(): void {
		const name = this.nameTarget;

		if (null !== name) {
			name.textContent = 'Stimulus is mounted.';
		}
	}
}
