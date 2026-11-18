import { describe, expect, it } from "vitest";
import { diffLabel, formatCRC, plainAmount } from "@/Composables/shared/format";

describe("formatCRC", () => {
	it("formats cents as colones with . grouping and , decimals", () => {
		expect(formatCRC(1086500)).toBe("₡10.865,00");
		expect(formatCRC(55763200)).toBe("₡557.632,00");
		expect(formatCRC(590000)).toBe("₡5.900,00");
		expect(formatCRC(0)).toBe("₡0,00");
		expect(formatCRC(-3090000)).toBe("-₡30.900,00");
		expect(formatCRC(10000000000)).toBe("₡100.000.000,00");
		expect(formatCRC(-5)).toBe("-₡0,05");
		expect(formatCRC(100000)).toBe("₡1.000,00");
		expect(formatCRC(99999)).toBe("₡999,99");
	});
});

describe("diffLabel", () => {
	it("is null when actual is null or equal", () => {
		expect(diffLabel(100, null)).toBeNull();
		expect(diffLabel(100, 100)).toBeNull();
	});
	it("signs the difference", () => {
		expect(diffLabel(250, 100)?.startsWith("-")).toBe(true);
		expect(diffLabel(100000, 250000)).toBe("+₡1.500,00");
	});
});

describe("plainAmount", () => {
	it("is an editable number", () => {
		expect(plainAmount(1086500)).toBe("10865");
		expect(plainAmount(1086550)).toBe("10865,50");
	});
});
