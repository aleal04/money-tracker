import { describe, expect, it } from "vitest";
import { spaceRows } from "@/Composables/Dashboard/spacesOverview";
import {
	canSendToSpace,
	lineKind,
	splitPreview,
	transferBadge,
} from "@/Composables/Period/lineKind";
import { canToggle } from "@/Composables/Period/usePeriodShow";
import { daysFor } from "@/Composables/Space/useSpaceIndex";
import type { Line } from "@/Types/Pages/Period/Period";
import type { Space } from "@/Types/Pages/Space/Space";

const line = (over: Partial<Line> = {}): Line => ({
	id: 1,
	periodId: 1,
	category: "gasto",
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
	targetSpaceId: null,
	transferFromLineId: null,
	transferFromSpaceName: null,
	...over,
});
const sp = (id: number, name: string): Space => ({
	id,
	name,
	frequency: "biweekly",
	customDays: null,
	initialBalance: 0,
	isPrimary: id === 1,
});
const spaces = [sp(1, "Salario"), sp(2, "Ahorro")];

describe("lineKind", () => {
	it("tells the five row kinds apart", () => {
		expect(lineKind(line())).toBe("normal");
		expect(lineKind(line({ targetSpaceId: 2 }))).toBe("origin");
		expect(
			lineKind(line({ source: "transfer", transferFromSpaceName: "Salario" })),
		).toBe("destination");
		expect(lineKind(line({ source: "carried" }))).toBe("arrastre");
		expect(lineKind(line({ source: "carried", targetSpaceId: 2 }))).toBe(
			"transferCarried",
		);
	});
	it("builds the badges", () => {
		expect(transferBadge(line(), spaces)).toBeNull();
		expect(transferBadge(line({ targetSpaceId: 2 }), spaces)).toBe("→ Ahorro");
		expect(
			transferBadge(line({ source: "carried", targetSpaceId: 2 }), spaces),
		).toBe("↩ → Ahorro");
		expect(
			transferBadge(
				line({ source: "transfer", transferFromSpaceName: "Salario" }),
				spaces,
			),
		).toBe("↘ desde Salario");
	});
	it("destination lines cannot be toggled; the select is for manual/template egresos only", () => {
		expect(canToggle(line({ source: "transfer" }), false, false)).toBe(false);
		expect(canSendToSpace("ingreso")).toBe(false);
		expect(canSendToSpace("gasto")).toBe(true);
		expect(canSendToSpace("gasto", line({ source: "carried" }))).toBe(false);
		expect(canSendToSpace("gasto", line({ source: "installments" }))).toBe(
			false,
		);
	});
});

describe("splitPreview", () => {
	it("splits arrastre and transfers with subtotals", () => {
		const s = splitPreview([
			line({ id: 1, estimated: 100 }),
			line({ id: 2, estimated: 50, actual: 70, targetSpaceId: 2 }),
			line({ id: 3, estimated: 30 }),
		]);
		expect(s.arrastre.map((l) => l.id)).toEqual([1, 3]);
		expect(s.transfers.map((l) => l.id)).toEqual([2]);
		expect([s.arrastreTotal, s.transfersTotal]).toEqual([130, 70]);
	});
});

describe("spaceRows", () => {
	it("maps restante or null (sin periodos)", () => {
		const data = {
			1: {
				current: { summary: { restante: -500 } },
				cards: [],
				templateCount: 0,
			},
		} as never;
		const rows = spaceRows(spaces, data);
		expect(rows[0]).toMatchObject({
			id: 1,
			name: "Salario",
			frequency: "Quincenal",
			restante: -500,
		});
		expect(rows[1].restante).toBeNull();
	});
});

describe("daysFor", () => {
	it("only sends days for custom; empty is NaN", () => {
		expect(daysFor("biweekly", "10")).toBeNull();
		expect(daysFor("custom", "10")).toBe(10);
		expect(daysFor("custom", " ")).toBeNaN();
	});
});
