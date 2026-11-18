import axios from "axios";
import { describe, expect, it, vi } from "vitest";
import { reloadOnSessionExpiry } from "@/Support/sessionExpiry";

describe("reloadOnSessionExpiry", () => {
	it("reloads on 419 only", async () => {
		const reload = vi.fn();
		vi.stubGlobal("window", { location: { reload } });
		reloadOnSessionExpiry();
		const run = (status: number) =>
			axios
				.get("/x", {
					adapter: async (config) =>
						Promise.reject({ config, response: { status } }),
				})
				.catch(() => {});
		await run(500);
		expect(reload).not.toHaveBeenCalled();
		await run(419);
		expect(reload).toHaveBeenCalledOnce();
		vi.unstubAllGlobals();
	});
});
