import { describe, expect, it } from "vitest";
import {
	canToggle,
	defaultTab,
	pendingIn,
} from "@/Composables/Period/usePeriodShow";
import type { Category, Line } from "@/Types/Pages/Period/Period";

const line = (category: Category, over: Partial<Line> = {}): Line => ({
	id: 1,
	periodId: 1,
	category,
	name: "x",
	estimated: 100,
	actual: null,
	paid: false,
	paidAt: null,
	source: "manual",
	templateId: null,
	cardId: null,
	carriedToLineId: null,
	sort: 0,
	carried: false,
	...over,
});

describe("defaultTab", () => {
	it("picks the first category with unpaid lines, skipping ingreso", () => {
		expect(
			defaultTab([
				line("ingreso"),
				line("gasto"),
				line("servicio", { paid: true }),
			]),
		).toBe("gasto");
	});
	it("falls back to ingreso when nothing is pending", () => {
		expect(
			defaultTab([
				line("servicio", { paid: true }),
				line("gasto", { carried: true }),
			]),
		).toBe("ingreso");
		expect(defaultTab([])).toBe("ingreso");
	});
	it("counts pending per category", () => {
		expect(
			pendingIn([line("gasto"), line("gasto", { paid: true })], "gasto"),
		).toBe(1);
	});
});

describe("canToggle", () => {
	it("is disabled when carried, busy, or closed and paid", () => {
		expect(canToggle(line("gasto", { carried: true }), false, false)).toBe(
			false,
		);
		expect(canToggle(line("gasto"), false, true)).toBe(false);
		expect(canToggle(line("gasto", { paid: true }), true, false)).toBe(false);
	});
	it("allows paying in a closed period and toggling in an open one", () => {
		expect(canToggle(line("gasto"), true, false)).toBe(true);
		expect(canToggle(line("gasto", { paid: true }), false, false)).toBe(true);
	});
});
