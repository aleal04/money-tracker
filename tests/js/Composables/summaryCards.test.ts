import { describe, expect, it } from "vitest";
import { summaryCards } from "@/Composables/Period/summaryCards";
import { dateRange } from "@/Composables/shared/format";
import type { Summary } from "@/Types/Pages/Period/Period";

const summary = (restante: number) =>
	({
		saldoAnterior: 1,
		entro: 2,
		gastado: 3,
		restante,
		faltaPorPagar: 5,
	}) as Summary;

describe("summaryCards", () => {
	it("maps the five cards in order", () => {
		const cards = summaryCards(summary(4));
		expect(cards.map((c) => [c.label, c.value])).toEqual([
			["Saldo anterior", 1],
			["Entró", 2],
			["Gastado", 3],
			["Restante", 4],
			["Falta por pagar", 5],
		]);
	});
	it("marks Restante negative only below zero", () => {
		const tone = (n: number) =>
			summaryCards(summary(n)).find((c) => c.key === "restante")?.tone;
		expect(tone(-1)).toBe("negative");
		expect(tone(0)).toBe("positive");
	});
});

describe("dateRange", () => {
	it("shows the year once when both dates share it", () => {
		expect(dateRange("2026-11-12", "2026-11-26")).toBe("12 nov – 26 nov 2026");
	});
	it("shows both years across a year change", () => {
		expect(dateRange("2026-12-30", "2027-01-12")).toBe(
			"30 dic 2026 – 12 ene 2027",
		);
	});
});
