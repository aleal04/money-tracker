import axios from "axios";
import { ref, watch } from "vue";
import { useSpace } from "@/Composables/shared/useSpace";
import { errorMessage, fieldErrors } from "@/Composables/shared/http";
import type {
	CardTotals,
	CardView,
	Installment,
	InstallmentStatus,
} from "@/Types/Pages/Installment/Installment";

/** Installments of a card in one status. */
export const ofStatus = (c: CardView, s: InstallmentStatus): Installment[] =>
	c.installments.filter((i) => i.status === s);

/** "+₡..." / "-₡..." label of the gap between the statement balance and the calculated one. */
export const signed = (n: number, fmt: (c: number) => string): string =>
	(n > 0 ? "+" : "") + fmt(n);

/** Amounts per period of the space ("a / b"); old payloads only have porQuincena. */
export const porPeriodoLabel = (
	t: Pick<CardTotals, "porQuincena" | "porPeriodo">,
	fmt: (c: number) => string,
): string => (t.porPeriodo ?? t.porQuincena).map(fmt).join(" / ");

export function useInstallmentIndex() {
	const { selectedId, current, spaceParams } = useSpace();
	const cards = ref<CardView[]>([]);
	const error = ref("");
	/** "new-<cardId>" while adding an installment, "<installmentId>" while editing one. */
	const editing = ref<string | null>(null);
	const newCard = ref(false);
	const cardName = ref("");
	const cardError = ref("");

	async function load() {
		try {
			cards.value = (
				await axios.get<CardView[]>("/installments/get-card-list", {
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

	async function cancelSoon(i: Installment) {
		try {
			await axios.post(`/installments/${i.id}/toggle-cancel-soon`);
			await load();
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	async function saveCard() {
		cardError.value = "";
		try {
			await axios.post("/cards/store", {
				name: cardName.value,
				spaceId: current.value?.id,
			});
			newCard.value = false;
			cardName.value = "";
			await load();
		} catch (e) {
			const errs = fieldErrors(e);
			cardError.value = errs.name ?? errs.form ?? "";
		}
	}

	return {
		cards,
		error,
		editing,
		newCard,
		cardName,
		cardError,
		load,
		saved,
		cancelSoon,
		saveCard,
	};
}
