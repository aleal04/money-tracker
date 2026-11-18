import axios from "axios";
import { flushPromises } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import {
	finishingLabel,
	greeting,
	pendingLines,
} from "@/Composables/Dashboard/useDashboard";
import {
	ofStatus,
	signed,
} from "@/Composables/Installment/useInstallmentIndex";
import {
	freqLabel,
	groupByCategory,
} from "@/Composables/Template/useTemplateIndex";
import { dayFor } from "@/Composables/Template/useTemplateForm";
import { quincenaDates } from "@/Composables/Period/periodDates";
import { toInt } from "@/Composables/Installment/useInstallmentForm";
import { formatCRC } from "@/Composables/shared/format";
import type {
	CardView,
	Installment,
} from "@/Types/Pages/Installment/Installment";
import type { Line } from "@/Types/Pages/Period/Period";
import type { Template } from "@/Types/Pages/Template/Template";

const inst = (p: Partial<Installment>) =>
	({
		item: "X",
		totalInstallments: 12,
		paidInstallments: 10,
		status: "activa",
		...p,
	}) as Installment;
const card = (installments: Installment[]) =>
	({ id: 1, name: "TC", installments }) as CardView;
const line = (p: Partial<Line>) =>
	({ id: 1, category: "servicio", paid: false, carried: false, ...p }) as Line;
const tpl = (p: Partial<Template>) =>
	({
		category: "servicio",
		frequency: "cada_periodo",
		dayOfMonth: null,
		...p,
	}) as Template;

describe("dashboard", () => {
	it("greets by hour", () => {
		expect([greeting(8), greeting(12), greeting(18), greeting(19)]).toEqual([
			"Buenos días",
			"Buenas tardes",
			"Buenas tardes",
			"Buenas noches",
		]);
	});
	it("pending skips income, paid and carried lines", () => {
		const lines = [
			line({ id: 1 }),
			line({ id: 2, category: "ingreso" }),
			line({ id: 3, paid: true }),
			line({ id: 4, carried: true }),
		];
		expect(pendingLines(lines).map((l) => l.id)).toEqual([1]);
	});
	it("labels finishing installments with remaining cuotas", () => {
		const c = card([
			inst({
				item: "Reloj",
				status: "por_terminar",
				totalInstallments: 24,
				paidInstallments: 22,
			}),
			inst({ item: "Otro" }),
		]);
		expect(finishingLabel(c)).toBe("Reloj (2)");
	});
});

describe("installments", () => {
	it("groups by status", () => {
		const c = card([inst({ id: 1 }), inst({ id: 2, status: "terminada" })]);
		expect(ofStatus(c, "terminada").map((i) => i.id)).toEqual([2]);
	});
	it("signs the saldo diff", () => {
		expect(signed(100, formatCRC)).toBe("+₡1,00");
		expect(signed(-100, formatCRC)).toBe("-₡1,00");
	});
	it("empty count is NaN so the API flags the field", () => {
		expect(toInt(" ")).toBeNaN();
		expect(toInt("12")).toBe(12);
	});
});

describe("templates", () => {
	it("labels frequency", () => {
		expect(freqLabel(tpl({}))).toBe("Cada periodo");
		expect(freqLabel(tpl({ frequency: "mensual_repartido" }))).toBe(
			"Mensual repartido",
		);
		expect(freqLabel(tpl({ frequency: "mensual_dia", dayOfMonth: 16 }))).toBe(
			"Mensual el día 16",
		);
	});
	it("sends the day only for mensual_dia", () => {
		expect(dayFor("mensual_dia", "16")).toBe(16);
		expect(dayFor("mensual_dia", " ")).toBeNaN();
		expect(dayFor("cada_periodo", "16")).toBeNull();
	});
	it("maps a quincena to its dates", () => {
		expect(quincenaDates(2026, 10, 1)).toEqual({
			startDate: "2026-10-01",
			endDate: "2026-10-15",
		});
		expect(quincenaDates(2028, 2, 2)).toEqual({
			startDate: "2028-02-16",
			endDate: "2028-02-29",
		});
	});
	it("groups by category, skipping empty ones", () => {
		const g = groupByCategory([
			tpl({ id: 1 }),
			tpl({ id: 2, category: "ahorro" }),
		]);
		expect(g.map((x) => x.key)).toEqual(["servicio", "ahorro"]);
	});
});

describe("template toggle failure", () => {
	it("reverts the flag and shows the error", async () => {
		const stored = [
			{
				id: 1,
				name: "A",
				category: "servicio",
				estimated: 100,
				frequency: "cada_periodo",
				dayOfMonth: null,
				active: true,
			},
		];
		vi.spyOn(axios, "get").mockResolvedValue({ data: stored });
		vi.spyOn(axios, "put").mockRejectedValue(new Error("boom"));
		const { useTemplateIndex } = await import(
			"@/Composables/Template/useTemplateIndex"
		);
		const idx = useTemplateIndex();
		await flushPromises();
		await idx.toggle(idx.templates.value[0]);
		expect(idx.templates.value[0].active).toBe(true);
		expect(idx.error.value).not.toBe("");
	});
});
