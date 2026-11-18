import axios from "axios";
import { ref } from "vue";
import { fieldErrors } from "@/Composables/shared/http";
import { plainAmount } from "@/Composables/shared/format";
import type {
	Installment,
	InstallmentBody,
	InstallmentType,
} from "@/Types/Pages/Installment/Installment";

/** Empty or non-numeric becomes NaN (sent as null), which the API rejects on the right field. */
export const toInt = (s: string): number => (s.trim() === "" ? NaN : Number(s));

export function useInstallmentForm(
	props: { cardId: number; installment?: Installment },
	onSaved: () => void,
) {
	const i = props.installment;
	const merchant = ref(i?.merchant ?? "");
	const item = ref(i?.item ?? "");
	const type = ref<InstallmentType>(i?.type ?? "tasa0");
	const total = ref(i ? String(i.totalInstallments) : "");
	const paid = ref(i ? String(i.paidInstallments) : "0");
	const initial = ref(i ? plainAmount(i.initialBalance) : "");
	const cuotaAmount = ref(
		i?.installmentAmount != null ? plainAmount(i.installmentAmount) : "",
	);
	const override = ref(
		i?.balanceOverride != null ? plainAmount(i.balanceOverride) : "",
	);
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
			const body: InstallmentBody = {
				cardId: props.cardId,
				merchant: merchant.value,
				item: item.value,
				type: type.value,
				totalInstallments: toInt(total.value),
				paidInstallments: toInt(paid.value),
				initialBalance: initial.value,
				installmentAmount:
					type.value === "minicuotas" ? cuotaAmount.value : null,
				balanceOverride: override.value.trim() ? override.value : null,
			};
			return i
				? axios.put(`/installments/${i.id}/update`, body)
				: axios.post("/installments/store", body);
		});
	const remove = () =>
		call(() => axios.delete(`/installments/${i?.id}/delete`));

	return {
		merchant,
		item,
		type,
		total,
		paid,
		initial,
		cuotaAmount,
		override,
		confirmingDelete,
		errors,
		busy,
		submit,
		remove,
	};
}
