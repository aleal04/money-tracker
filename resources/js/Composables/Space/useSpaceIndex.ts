import axios from "axios";
import { ref } from "vue";
import { errorMessage, fieldErrors } from "@/Composables/shared/http";
import { plainAmount } from "@/Composables/shared/format";
import { useSpace } from "@/Composables/shared/useSpace";
import type { Space } from "@/Types/Pages/Space/Space";

/** "Semanal" / "Quincenal" / "Mensual" / "Personalizada (N días)". */
export const spaceFreqLabel = (
	s: Pick<Space, "frequency" | "customDays">,
): string =>
	({
		weekly: "Semanal",
		biweekly: "Quincenal",
		monthly: "Mensual",
		custom: `Personalizada (${s.customDays} días)`,
	})[s.frequency];

export function useSpaceIndex() {
	const { spaces, loadSpaces } = useSpace();
	const error = ref("");
	const editing = ref<number | "new" | null>(null);
	const busy = ref(false);

	async function load() {
		try {
			await loadSpaces();
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	load();

	/** After a save or delete: close the form and reload. */
	async function saved() {
		editing.value = null;
		await load();
	}

	async function makePrimary(s: Space) {
		busy.value = true;
		try {
			await axios.post(`/spaces/${s.id}/make-primary`);
			await load();
		} catch (e) {
			error.value = errorMessage(e);
		} finally {
			busy.value = false;
		}
	}

	return { spaces, error, editing, busy, load, saved, makePrimary };
}

/** Custom days are only sent for "custom"; an empty input is NaN so the API flags the field. */
export const daysFor = (
	frequency: Space["frequency"],
	days: string,
): number | null =>
	frequency === "custom"
		? days.trim() === ""
			? Number.NaN
			: Number(days)
		: null;

export function useSpaceForm(props: { space?: Space }, onSaved: () => void) {
	const sp = props.space;
	const name = ref(sp?.name ?? "");
	const frequency = ref<Space["frequency"]>(sp?.frequency ?? "biweekly");
	const customDays = ref(sp?.customDays ? String(sp.customDays) : "10");
	const initialBalance = ref(sp ? plainAmount(sp.initialBalance) : "");
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
				frequency: frequency.value,
				customDays: daysFor(frequency.value, customDays.value),
				initialBalance: initialBalance.value,
			};
			return sp
				? axios.put(`/spaces/${sp.id}/update`, body)
				: axios.post("/spaces/store", body);
		});
	const remove = () => call(() => axios.delete(`/spaces/${sp?.id}/delete`));

	return {
		name,
		frequency,
		customDays,
		initialBalance,
		confirmingDelete,
		errors,
		busy,
		submit,
		remove,
	};
}
