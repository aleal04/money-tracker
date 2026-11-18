import { describe, expect, it } from "vitest";
import { spaceFreqLabel } from "@/Composables/Space/useSpaceIndex";

describe("spaceFreqLabel", () => {
	it("labels every frequency", () => {
		expect(spaceFreqLabel({ frequency: "weekly", customDays: null })).toBe(
			"Semanal",
		);
		expect(spaceFreqLabel({ frequency: "monthly", customDays: null })).toBe(
			"Mensual",
		);
		expect(spaceFreqLabel({ frequency: "custom", customDays: 10 })).toBe(
			"Personalizada (10 días)",
		);
	});
});
