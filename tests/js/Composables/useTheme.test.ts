import { beforeEach, describe, expect, it, vi } from "vitest";

function mockSystem(dark: boolean) {
	window.matchMedia = vi
		.fn()
		.mockReturnValue({ matches: dark, addEventListener: vi.fn() });
}

async function load() {
	vi.resetModules();
	return (await import("@/Composables/shared/useTheme")).useTheme();
}

describe("useTheme", () => {
	beforeEach(() => {
		localStorage.clear();
		document.documentElement.classList.remove("dark");
	});

	it("follows the system when nothing is stored", async () => {
		mockSystem(true);
		const { theme } = await load();
		expect(theme.value).toBe("dark");
		expect(document.documentElement.classList.contains("dark")).toBe(true);
	});

	it("toggle persists the choice and flips the html class", async () => {
		mockSystem(false);
		const { theme, toggleTheme } = await load();
		toggleTheme();
		expect(theme.value).toBe("dark");
		expect(localStorage.getItem("theme")).toBe("dark");
		expect(document.documentElement.classList.contains("dark")).toBe(true);
	});

	it("stored choice wins over the system", async () => {
		mockSystem(true);
		localStorage.setItem("theme", "light");
		const { theme } = await load();
		expect(theme.value).toBe("light");
	});

	it("survives localStorage throwing", async () => {
		mockSystem(false);
		vi.spyOn(Storage.prototype, "getItem").mockImplementation(() => {
			throw new Error("blocked");
		});
		vi.spyOn(Storage.prototype, "setItem").mockImplementation(() => {
			throw new Error("blocked");
		});
		const { theme, toggleTheme } = await load();
		expect(theme.value).toBe("light");
		expect(() => toggleTheme()).not.toThrow();
		expect(theme.value).toBe("dark");
	});
});
