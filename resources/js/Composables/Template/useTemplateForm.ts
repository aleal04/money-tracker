import axios from "axios";
import { ref } from "vue";
import { fieldErrors } from "@/Composables/shared/http";
import { plainAmount } from "@/Composables/shared/format";
import type { Category } from "@/Types/Pages/Period/Period";
import type {
	Frequency,
	Template,
	TemplateBody,
} from "@/Types/Pages/Template/Template";

/** The form has one frequency dropdown: "mensual_en_quincena" is split by half ("..._1" / "..._2"). */
export const toChoice = (t?: Pick<Template, "frequency" | "half">): string =>
	t
		? t.frequency === "mensual_en_quincena"
			? `mensual_en_quincena_${t.half}`
			: t.frequency
		: "quincenal";

export function fromChoice(choice: string): {
	frequency: Frequency;
	half: 1 | 2 | null;
} {
	const m = /^mensual_en_quincena_([12])$/.exec(choice);
	return m
		? { frequency: "mensual_en_quincena", half: Number(m[1]) as 1 | 2 }
		: { frequency: choice as Frequency, half: null };
}

export function useTemplateForm(
	props: { template?: Template },
	onSaved: () => void,
) {
	const t = props.template;
	const name = ref(t?.name ?? "");
	const category = ref<Category>(t?.category ?? "servicio");
	const estimated = ref(t ? plainAmount(t.estimated) : "");
	const freq = ref(toChoice(t));
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
				...fromChoice(freq.value),
			};
			return t
				? axios.put(`/templates/${t.id}/update`, body)
				: axios.post("/templates/store", { ...body, active: true });
		});
	const remove = () => call(() => axios.delete(`/templates/${t?.id}/delete`));

	return {
		name,
		category,
		estimated,
		freq,
		confirmingDelete,
		errors,
		busy,
		submit,
		remove,
	};
}
