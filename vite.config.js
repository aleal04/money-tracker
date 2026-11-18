import vue from "@vitejs/plugin-vue";
import laravel from "laravel-vite-plugin";
import { defineConfig } from "vite";

export default defineConfig({
	plugins: [
		laravel({
			input: ["resources/scss/app.scss", "resources/js/app.ts"],
			refresh: true,
		}),
		vue(),
	],
	server: {
		watch: {
			ignored: ["**/storage/framework/views/**"],
		},
	},
	resolve: {
		alias: {
			"@": "/resources/js",
			"@scss": "/resources/scss",
		},
	},
});
