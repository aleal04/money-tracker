import { createApp, type DefineComponent, h } from "vue";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { reloadOnSessionExpiry } from "@/Support/sessionExpiry";
import AppLayout from "@/Layouts/AppLayout.vue";

reloadOnSessionExpiry();

createInertiaApp({
	resolve: async (name) => {
		const page = await resolvePageComponent<DefineComponent>(
			`./Pages/${name}.vue`,
			import.meta.glob<DefineComponent>("./Pages/**/*.vue"),
		);

		// Use AppLayout by default for every Inertia page.
		const module = page as unknown as { default: { layout?: unknown } };
		module.default.layout = module.default.layout || AppLayout;

		return page;
	},
	setup({ el, App, props, plugin }) {
		createApp({ render: () => h(App, props) })
			.use(plugin)
			.mount(el);
	},
});
