import axios from "axios";
import { describe, expect, it, vi } from "vitest";
import { useLineForm } from "@/Composables/Period/useLineForm";
import { useTemplateForm } from "@/Composables/Template/useTemplateForm";

vi.mock("@inertiajs/vue3", () => ({ router: { visit: vi.fn() } }));

describe("useLineForm payload", () => {
	it("sends targetSpaceId as a number, null to clear, and nothing for ingresos", async () => {
		const post = vi.spyOn(axios, "post").mockResolvedValue({});
		const f = useLineForm({ periodId: 1, category: "gasto" }, () => undefined);
		f.name.value = "x";
		f.estimated.value = "10";
		f.target.value = 2;
		await f.submit();
		expect(post.mock.calls[0][1]).toMatchObject({ targetSpaceId: 2 });
		f.target.value = "";
		await f.submit();
		expect(post.mock.calls[1][1]).toMatchObject({ targetSpaceId: null });
		const g = useLineForm(
			{ periodId: 1, category: "ingreso" },
			() => undefined,
		);
		await g.submit();
		expect(post.mock.calls[2][1]).not.toHaveProperty("targetSpaceId");
	});
});

describe("useTemplateForm payload", () => {
	it("forces a null target for ingreso", async () => {
		const post = vi.spyOn(axios, "post").mockResolvedValue({});
		const f = useTemplateForm({}, () => undefined);
		f.category.value = "servicio";
		f.target.value = 2;
		await f.submit();
		expect(post.mock.calls.at(-1)?.[1]).toMatchObject({ targetSpaceId: 2 });
		f.category.value = "ingreso";
		await f.submit();
		expect(post.mock.calls.at(-1)?.[1]).toMatchObject({ targetSpaceId: null });
	});
});
