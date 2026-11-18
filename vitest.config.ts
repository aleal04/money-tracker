import vue from "@vitejs/plugin-vue";
import { defineConfig } from "vitest/config";

/**
 * Unit tests of the frontend (composables, helpers and Vue components) with Vue Test Utils and happy-dom.
 * The aliases mirror vite.config.js; the Laravel plugin is left out because the tests do not need the manifest.
 */
export default defineConfig({
	plugins: [vue()],
	resolve: {
		alias: {
			"@": "/resources/js",
			"@scss": "/resources/scss",
		},
	},
	test: {
		environment: "happy-dom",
		include: ["tests/js/**/*.test.ts"],
		restoreMocks: true,
		css: false,
	},
});
