import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import Index from "@/Pages/Dashboard/Index.vue";

describe("Dashboard/Index", () => {
	it("renders the title", () => {
		expect(mount(Index).text()).toBe("Inicio");
	});
});
