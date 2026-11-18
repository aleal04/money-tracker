import axios from "axios";
import { flushPromises, mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import Index from "@/Pages/Template/Index.vue";

vi.mock("@inertiajs/vue3", () => ({ Head: { render: () => null } }));

describe("Template/Index", () => {
	it("puts the checkbox back and shows the error when the toggle fails", async () => {
		const t = {
			id: 1,
			name: "Kolbi",
			category: "servicio",
			estimated: 100,
			frequency: "quincenal",
			half: null,
			active: true,
		};
		vi.spyOn(axios, "get").mockResolvedValue({ data: [t] });
		vi.spyOn(axios, "put").mockRejectedValue(new Error("boom"));
		const wrapper = mount(Index, { attachTo: document.body });
		await flushPromises();
		const box = wrapper.find<HTMLInputElement>('input[type="checkbox"]');
		expect(box.element.checked).toBe(true);
		await box.setValue(false);
		await flushPromises();
		expect(box.element.checked).toBe(true);
		expect(wrapper.find('[role="alert"]').exists()).toBe(true);
		wrapper.unmount();
	});
});
