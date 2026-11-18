import axios from "axios";
import { beforeEach, describe, expect, it, vi } from "vitest";
import type { Space } from "@/Types/Pages/Space/Space";

vi.mock("@inertiajs/vue3", () => ({ router: { visit: vi.fn() } }));

const sp = (id: number, isPrimary = false): Space => ({
	id,
	name: `E${id}`,
	frequency: "biweekly",
	customDays: null,
	initialBalance: 0,
	isPrimary,
	hasPeriods: false,
});

async function load(list: Space[] = [sp(1, true), sp(2)]) {
	vi.resetModules();
	vi.spyOn(axios, "get").mockResolvedValue({ data: list });
	const s = (await import("@/Composables/shared/useSpace")).useSpace();
	await s.loadSpaces();
	return s;
}

describe("useSpace", () => {
	beforeEach(() => localStorage.clear());

	it("falls back to the primary when nothing is stored", async () => {
		const { current, spaceParams } = await load();
		expect(current.value?.id).toBe(1);
		expect(spaceParams()).toEqual({});
	});

	it("persists the chosen space and sends it as ?space=", async () => {
		const s = await load();
		s.setSpace(2);
		expect(localStorage.getItem("space")).toBe("2");
		expect(s.current.value?.id).toBe(2);
		expect(s.spaceParams()).toEqual({ space: 2 });
		expect((await load()).current.value?.id).toBe(2);
	});

	it("drops a stored space that no longer exists", async () => {
		localStorage.setItem("space", "9");
		const s = await load();
		expect(s.current.value?.id).toBe(1);
		expect(s.selectedId.value).toBeNull();
		// Forgotten for good, so the next visit doesn't send it again.
		expect(localStorage.getItem("space")).toBeNull();
	});

	it("survives a throwing localStorage", async () => {
		vi.spyOn(Storage.prototype, "getItem").mockImplementation(() => {
			throw new Error("blocked");
		});
		vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
			throw new Error("blocked");
		});
		const s = await load();
		expect(s.current.value?.id).toBe(1);
		s.setSpace(2);
		expect(s.current.value?.id).toBe(2);
	});
});
