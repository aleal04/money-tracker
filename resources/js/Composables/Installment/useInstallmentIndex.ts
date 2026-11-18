import axios from "axios";
import { ref } from "vue";
import { errorMessage, fieldErrors } from "@/Composables/shared/http";
import type {
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

export function useInstallmentIndex() {
	const cards = ref<CardView[]>([]);
	const error = ref("");
	/** "new-<cardId>" while adding an installment, "<installmentId>" while editing one. */
	const editing = ref<string | null>(null);
	const newCard = ref(false);
	const cardName = ref("");
	const cardError = ref("");

	async function load() {
		editing.value = null;
		try {
			cards.value = (
				await axios.get<CardView[]>("/installments/get-card-list")
			).data;
			error.value = "";
		} catch (e) {
			error.value = errorMessage(e);
		}
	}
	load();

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
			await axios.post("/cards/store", { name: cardName.value });
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
		cancelSoon,
		saveCard,
	};
}
