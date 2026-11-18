import { mount } from "@vue/test-utils";
import { VueDatePicker } from "@vuepic/vue-datepicker";
import { describe, expect, it } from "vitest";
import DatePicker from "@/Components/UI/Inputs/DatePicker.vue";
import SelectInput from "@/Components/UI/Inputs/SelectInput.vue";

describe("DatePicker", () => {
	it("passes the Y-m-d string down with the es locale, dd/MM/yyyy input and Monday start", () => {
		const picker = mount(DatePicker, {
			props: { modelValue: "2026-11-18", inputId: "d1" },
		}).findComponent(VueDatePicker);
		expect(picker.props("modelValue")).toBe("2026-11-18");
		expect(picker.props("modelType")).toBe("yyyy-MM-dd");
		expect(picker.props("weekStart")).toBe(1);
		expect(picker.props("formats")).toEqual({ input: "dd/MM/yyyy" });
		expect((picker.props("locale") as { code: string }).code).toBe("es");
	});

	it("emits the Y-m-d string (and null when cleared)", async () => {
		const wrapper = mount(DatePicker, {
			props: { modelValue: null, inputId: "d2" },
		});
		const picker = wrapper.findComponent(VueDatePicker);
		picker.vm.$emit("update:modelValue", "2026-12-01");
		picker.vm.$emit("update:modelValue", null);
		expect(wrapper.emitted("update:modelValue")).toEqual([
			["2026-12-01"],
			[null],
		]);
	});
});

describe("SelectInput", () => {
	const options = [
		{ label: "Ninguno", value: "" },
		{ label: "Ahorros", value: 7 },
	];

	it("shows the selected label and emits the option value, numbers staying numbers", async () => {
		const wrapper = mount(SelectInput, {
			attrs: {
				modelValue: "",
				options,
				"onUpdate:modelValue": (v: unknown) =>
					wrapper.setProps({ modelValue: v } as never),
			},
			attachTo: document.body,
		});
		expect(wrapper.text()).toContain("Ninguno");
		await wrapper.find(".dropdown-icon").trigger("click");
		const items = wrapper.findAll(".menu-option");
		await items[1]?.trigger("click");
		expect(wrapper.text()).toContain("Ahorros");
		wrapper.unmount();
	});

	it("emits null for a null option", async () => {
		let emitted: unknown = "unset";
		const wrapper = mount(SelectInput, {
			attrs: {
				modelValue: 7,
				options: [
					{ label: "Ninguno", value: null },
					{ label: "Ahorros", value: 7 },
				],
				"onUpdate:modelValue": (v: unknown) => {
					emitted = v;
				},
			},
			attachTo: document.body,
		});
		await wrapper.find(".dropdown-icon").trigger("click");
		await wrapper.findAll(".menu-option")[0]?.trigger("click");
		expect(emitted).toBeNull();
		wrapper.unmount();
	});
});
