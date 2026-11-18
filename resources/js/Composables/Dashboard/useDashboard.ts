import axios from "axios";
import { router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { errorMessage } from "@/Composables/shared/http";
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
	const data = ref<DashboardData | null>(null);
	const error = ref("");
	const busy = ref(false);
	const creating = ref(false);

	async function load() {
		try {
			data.value = (await axios.get<DashboardData>("/dashboard/data")).data;
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	load();

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
			const res = await axios.post<{ period: { id: number } }>("/periods/next");
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
		installmentCount,
		pay,
		createNext,
	};
}
