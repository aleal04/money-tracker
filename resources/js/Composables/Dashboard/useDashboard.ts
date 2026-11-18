import axios from "axios";
import { router } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useSpace } from "@/Composables/shared/useSpace";
import { errorMessage } from "@/Composables/shared/http";
import { spaceRows } from "@/Composables/Dashboard/spacesOverview";
import type { DashboardData } from "@/Types/Pages/Dashboard/Dashboard";
import type { Line } from "@/Types/Pages/Period/Period";
import type {
	CardView,
	Installment,
} from "@/Types/Pages/Installment/Installment";

/** Greeting by hour of day. */
export const greeting = (hour: number): string =>
	hour < 12 ? "Buenos días" : hour < 19 ? "Buenas tardes" : "Buenas noches";

/** Still to pay: expense lines, unpaid and not carried on (carried lines already moved on). */
export const pendingLines = (lines: Line[]): Line[] =>
	lines.filter((l) => l.category !== "ingreso" && !l.paid && !l.carried);

/** Installments of a card about to finish. */
export const finishing = (c: CardView): Installment[] =>
	c.installments.filter((i) => i.status === "por_terminar");

/** "Item (remaining cuotas), ...". */
export const finishingLabel = (c: CardView): string =>
	finishing(c)
		.map((i) => `${i.item} (${i.totalInstallments - i.paidInstallments})`)
		.join(", ");

export function useDashboard() {
	const { spaces, selectedId, current, spaceParams, ensureSpaces, setSpace } =
		useSpace();
	const data = ref<DashboardData | null>(null);
	const error = ref("");
	const busy = ref(false);
	const creating = ref(false);

	async function load() {
		try {
			data.value = (
				await axios.get<DashboardData>("/dashboard/data", {
					params: spaceParams(),
				})
			).data;
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	const overview = ref<Record<number, DashboardData | undefined>>({});
	/** Latest period of every space for the "Tus espacios" panel (one dashboard call each). */
	async function loadOverview() {
		const entries = await Promise.all(
			spaces.value.map(
				async (s) =>
					[
						s.id,
						await axios
							.get<DashboardData>("/dashboard/data", {
								params: { space: s.id },
							})
							.then((r) => r.data)
							.catch(() => undefined),
					] as const,
			),
		);
		overview.value = Object.fromEntries(entries);
	}
	const rows = computed(() => spaceRows(spaces.value, overview.value));
	// Wait for the space list first so a stale stored id is dropped before the first request.
	ensureSpaces().then(async () => {
		await load();
		watch(selectedId, load);
		loadOverview();
	});

	const pending = computed(() =>
		pendingLines(data.value?.current?.lines ?? []),
	);
	const installmentCount = computed(
		() => data.value?.cards.reduce((n, c) => n + c.installments.length, 0) ?? 0,
	);

	async function pay(l: Line) {
		busy.value = true;
		error.value = "";
		try {
			await axios.post(`/lines/${l.id}/toggle-paid`);
			await load();
			loadOverview();
		} catch (e) {
			error.value = errorMessage(e);
		} finally {
			busy.value = false;
		}
	}
	async function createNext() {
		if (!data.value?.current) {
			creating.value = true;
			return;
		}
		busy.value = true;
		try {
			const res = await axios.post<{ period: { id: number } }>(
				"/periods/next",
				{ spaceId: current.value?.id },
			);
			router.visit(`/periods/${res.data.period.id}`);
		} catch (e) {
			error.value = errorMessage(e);
		} finally {
			busy.value = false;
		}
	}

	return {
		data,
		error,
		busy,
		creating,
		pending,
		rows,
		current,
		setSpace,
		installmentCount,
		pay,
		createNext,
	};
}
