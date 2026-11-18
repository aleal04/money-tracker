import axios from "axios";
import { computed, ref, watch } from "vue";
import { useSpace } from "@/Composables/shared/useSpace";
import { CATEGORIES } from "@/Constants/Pages/Period/categories";
import { errorMessage } from "@/Composables/shared/http";
import type { Template } from "@/Types/Pages/Template/Template";

/** "Cada periodo" / "Mensual repartido" / "Mensual el día N". */
export const freqLabel = (
	t: Pick<Template, "frequency" | "dayOfMonth">,
): string =>
	t.frequency === "cada_periodo"
		? "Cada periodo"
		: t.frequency === "mensual_repartido"
			? "Mensual repartido"
			: `Mensual el día ${t.dayOfMonth}`;

/** Templates grouped by category, skipping empty categories. */
export const groupByCategory = (templates: Template[]) =>
	CATEGORIES.map((c) => ({
		...c,
		items: templates.filter((t) => t.category === c.key),
	})).filter((g) => g.items.length);

export function useTemplateIndex() {
	const { selectedId, spaceParams } = useSpace();
	const templates = ref<Template[]>([]);
	const error = ref("");
	const editing = ref<number | "new" | null>(null);

	async function load() {
		try {
			templates.value = (
				await axios.get<Template[]>("/templates/get-template-list", {
					params: spaceParams(),
				})
			).data;
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	load();
	watch(selectedId, load);

	/** After a save or delete: close the form and reload. */
	async function saved() {
		editing.value = null;
		await load();
	}

	const groups = computed(() => groupByCategory(templates.value));
	/** Optimistic: flip now so the checkbox binding changes, flip back (and show the error) if the PUT fails. */
	async function toggle(t: Template) {
		t.active = !t.active;
		try {
			await axios.put(`/templates/${t.id}/update`, { active: t.active });
		} catch (e) {
			t.active = !t.active;
			error.value = errorMessage(e);
		}
	}

	return { templates, error, editing, groups, load, saved, toggle };
}
