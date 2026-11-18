import axios from "axios";
import { ref } from "vue";
import { fieldErrors } from "@/Composables/shared/http";
import { plainAmount } from "@/Composables/shared/format";
import type { Category, Line } from "@/Types/Pages/Period/Period";

export function useLineForm(
	props: { periodId: number; category: Category; line?: Line },
	onSaved: () => void,
) {
	const name = ref(props.line?.name ?? "");
	const estimated = ref(props.line ? plainAmount(props.line.estimated) : "");
	const actual = ref(
		props.line?.actual != null ? plainAmount(props.line.actual) : "",
	);
	const paid = ref(false); // create only; editing uses the row toggle
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
			const body = {
				name: name.value,
				estimated: estimated.value,
				actual: actual.value.trim() ? actual.value : null,
			};
			return props.line
				? axios.put(`/lines/${props.line.id}/update`, body)
				: axios.post(`/periods/${props.periodId}/lines/store`, {
						...body,
						category: props.category,
						paid: paid.value,
					});
		});
	const remove = () =>
		call(() => axios.delete(`/lines/${props.line?.id}/delete`));

	return {
		name,
		estimated,
		actual,
		paid,
		confirmingDelete,
		errors,
		busy,
		submit,
		remove,
	};
}
