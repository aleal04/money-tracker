import axios from "axios";
import { computed, ref } from "vue";
import { CATEGORIES } from "@/Constants/Pages/Period/categories";
import { errorMessage } from "@/Composables/shared/http";
import type { Template } from "@/Types/Pages/Template/Template";

/** "Cada quincena" / "Mensual ÷ 2" / "Mensual en I|II quincena". */
export const freqLabel = (t: Pick<Template, "frequency" | "half">): string =>
	t.frequency === "quincenal"
		? "Cada quincena"
		: t.frequency === "mensual_dividido"
			? "Mensual ÷ 2"
			: `Mensual en ${t.half === 1 ? "I" : "II"} quincena`;

/** Templates grouped by category, skipping empty categories. */
export const groupByCategory = (templates: Template[]) =>
	CATEGORIES.map((c) => ({
		...c,
		items: templates.filter((t) => t.category === c.key),
	})).filter((g) => g.items.length);

export function useTemplateIndex() {
	const templates = ref<Template[]>([]);
	const error = ref("");
	const editing = ref<number | "new" | null>(null);

	async function load() {
		editing.value = null;
		try {
			templates.value = (
				await axios.get<Template[]>("/templates/get-template-list")
			).data;
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	load();

	const groups = computed(() => groupByCategory(templates.value));
	async function toggle(t: Template) {
		try {
			await axios.put(`/templates/${t.id}/update`, { active: !t.active });
			await load();
		} catch (e) {
			error.value = errorMessage(e);
		}
	}

	return { templates, error, editing, groups, load, toggle };
}
