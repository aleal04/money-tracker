import axios from "axios";
import { ref } from "vue";
import { useSpace } from "@/Composables/shared/useSpace";
import { fieldErrors } from "@/Composables/shared/http";
import { plainAmount } from "@/Composables/shared/format";
import type { Category } from "@/Types/Pages/Period/Period";
import type {
	Frequency,
	Template,
	TemplateBody,
} from "@/Types/Pages/Template/Template";

/** The day is only sent for "mensual_dia"; an empty input is NaN so the API flags the field. */
export const dayFor = (frequency: Frequency, day: string): number | null =>
	frequency === "mensual_dia"
		? day.trim() === ""
			? Number.NaN
			: Number(day)
		: null;

export function useTemplateForm(
	props: { template?: Template },
	onSaved: () => void,
) {
	const { current } = useSpace();
	const t = props.template;
	const name = ref(t?.name ?? "");
	const category = ref<Category>(t?.category ?? "servicio");
	const estimated = ref(t ? plainAmount(t.estimated) : "");
	const freq = ref<Frequency>(t?.frequency ?? "cada_periodo");
	const day = ref(t?.dayOfMonth ? String(t.dayOfMonth) : "1");
	const confirmingDelete = ref(false);
	const errors = ref<Record<string, string>>({});
	const busy = ref(false);

	async function call(fn: () => Promise<unknown>) {
		busy.value = true;
		errors.value = {};
		try {
			await fn();
			onSaved();
		} catch (e) {
			errors.value = fieldErrors(e);
			confirmingDelete.value = false;
		} finally {
			busy.value = false;
		}
	}

	const submit = () =>
		call(() => {
			const body: TemplateBody = {
				name: name.value,
				category: category.value,
				estimated: estimated.value,
				frequency: freq.value,
				dayOfMonth: dayFor(freq.value, day.value),
			};
			return t
				? axios.put(`/templates/${t.id}/update`, body)
				: axios.post("/templates/store", {
						...body,
						active: true,
						spaceId: current.value?.id,
					});
		});
	const remove = () => call(() => axios.delete(`/templates/${t?.id}/delete`));

	return {
		name,
		category,
		estimated,
		freq,
		day,
		confirmingDelete,
		errors,
		busy,
		submit,
		remove,
	};
}
