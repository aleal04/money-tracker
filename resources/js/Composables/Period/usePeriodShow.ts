import axios from "axios";
import { router } from "@inertiajs/vue3";
import { computed, type Ref, ref, watch } from "vue";
import { CATEGORIES } from "@/Constants/Pages/Period/categories";
import { errorMessage, fieldErrors } from "@/Composables/shared/http";
import { useSpace } from "@/Composables/shared/useSpace";
import type { Category, Line, PeriodDetail } from "@/Types/Pages/Period/Period";

export const isCategory = (v: unknown): v is Category =>
	CATEGORIES.some((c) => c.key === v);

/** Unpaid, non-carried lines of an expense category (income never counts as pending). */
export const pendingIn = (lines: Line[], c: Category): number =>
	c === "ingreso"
		? 0
		: lines.filter((l) => l.category === c && !l.paid && !l.carried).length;

/** First category with something to pay, else Ingresos. */
export const defaultTab = (lines: Line[]): Category =>
	CATEGORIES.find((c) => pendingIn(lines, c.key))?.key ?? "ingreso";

/** Paying is allowed in a closed period; un-paying there would leave an unpaid line that is never carried. */
export const canToggle = (l: Line, closed: boolean, busy: boolean): boolean =>
	!(l.carried || busy || (closed && l.paid));

export function usePeriodShow(periodId: Ref<number>) {
	const { current, adoptSpace } = useSpace();
	const data = ref<PeriodDetail | null>(null);
	const error = ref("");
	const busy = ref(false);
	const closing = ref(false);
	const queryTab = new URLSearchParams(window.location.search).get("tab");
	const chosenTab = ref<Category | null>(
		isCategory(queryTab) ? queryTab : null,
	);

	async function load() {
		try {
			data.value = (
				await axios.get<PeriodDetail>(`/periods/${periodId.value}/data`)
			).data;
			error.value = "";
			// The period on screen decides the space the topbar shows.
			adoptSpace(data.value.period.spaceId);
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	/** Runs a mutation with buttons disabled, then reloads; errors (e.g. 409) are shown inline. */
	async function run(fn: () => Promise<unknown>) {
		busy.value = true;
		error.value = "";
		try {
			await fn();
			await load();
		} catch (e) {
			error.value = errorMessage(e);
		} finally {
			busy.value = false;
		}
	}
	watch(periodId, load, { immediate: true });

	const linesOf = (c: Category) =>
		data.value?.lines.filter((l) => l.category === c) ?? [];
	const tab = computed<Category>(
		() => chosenTab.value ?? defaultTab(data.value?.lines ?? []),
	);
	function setTab(c: Category) {
		chosenTab.value = c;
		const url = new URL(window.location.href);
		url.searchParams.set("tab", c);
		// Keep Inertia's history state; only the URL changes.
		window.history.replaceState(window.history.state, "", url);
	}

	const toggle = (l: Line) =>
		run(() => axios.post(`/lines/${l.id}/toggle-paid`));
	const reopen = () =>
		run(() => axios.post(`/periods/${periodId.value}/reopen`));
	const go = (id: number | null) =>
		id !== null && router.visit(`/periods/${id}`);
	async function createNext() {
		error.value = "";
		busy.value = true;
		try {
			const res = await axios.post<{ period: { id: number } }>(
				"/periods/next",
				{
					spaceId: data.value?.period.spaceId ?? current.value?.id,
				},
			);
			router.visit(`/periods/${res.data.period.id}`);
		} catch (e) {
			error.value = errorMessage(e);
		} finally {
			busy.value = false;
		}
	}
	const editingDates = ref(false);
	const startDate = ref("");
	const endDate = ref("");
	const dateErrors = ref<Record<string, string>>({});
	function editDates() {
		startDate.value = data.value?.period.startDate ?? "";
		endDate.value = data.value?.period.endDate ?? "";
		dateErrors.value = {};
		editingDates.value = true;
	}
	async function saveDates() {
		busy.value = true;
		dateErrors.value = {};
		try {
			await axios.put(`/periods/${periodId.value}/update`, {
				startDate: startDate.value,
				endDate: endDate.value,
			});
			editingDates.value = false;
			await load();
		} catch (e) {
			dateErrors.value = fieldErrors(e);
		} finally {
			busy.value = false;
		}
	}
	const closed = () => {
		closing.value = false;
		load();
	};

	return {
		data,
		error,
		busy,
		closing,
		tab,
		setTab,
		linesOf,
		load,
		toggle,
		reopen,
		go,
		createNext,
		closed,
		editingDates,
		startDate,
		endDate,
		dateErrors,
		editDates,
		saveDates,
	};
}
