import axios from "axios";
import { flushPromises, mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Index from "@/Pages/Dashboard/Index.vue";

vi.mock("@inertiajs/vue3", () => ({
	Head: { render: () => null },
	Link: {
		render() {
			return null;
		},
	},
	router: { visit: vi.fn() },
}));

describe("Dashboard/Index", () => {
	it("shows the welcome steps on an empty database", async () => {
		vi.spyOn(axios, "get").mockImplementation(async (url: string) => ({
			data: url.includes("spaces")
				? []
				: { current: null, cards: [], templateCount: 0 },
		}));
		const wrapper = mount(Index, {
			global: { stubs: { Head: true, Link: true } },
		});
		await flushPromises();
		expect(wrapper.text()).toContain("Bienvenido a Finanzas");
		expect(wrapper.text()).toContain("Nuevo periodo");
	});
});
