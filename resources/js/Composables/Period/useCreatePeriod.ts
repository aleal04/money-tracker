import axios from "axios";
import { router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { fieldErrors } from "@/Composables/shared/http";
import { useSpace } from "@/Composables/shared/useSpace";
import type { PeriodProposal } from "@/Types/Pages/Space/Space";

export function useCreatePeriod() {
	const { selectedId, current, spaceParams } = useSpace();
	const startDate = ref("");
	const endDate = ref("");
	const errors = ref<Record<string, string>>({});
	const busy = ref(false);

	/** Prefill the dates with what the space's frequency proposes; both stay editable. */
	async function loadProposal() {
		try {
			const p = (
				await axios.get<PeriodProposal>("/periods/next-proposal", {
					params: spaceParams(),
				})
			).data;
			startDate.value = p.startDate;
			endDate.value = p.endDate;
		} catch (e) {
			errors.value = fieldErrors(e);
		}
	}
	loadProposal();
	watch(selectedId, loadProposal);

	async function create() {
		busy.value = true;
		errors.value = {};
		try {
			const res = await axios.post<{ period: { id: number } }>(
				"/periods/store",
				{
					spaceId: current.value?.id,
					startDate: startDate.value,
					endDate: endDate.value,
				},
			);
			router.visit(`/periods/${res.data.period.id}`);
		} catch (e) {
			errors.value = fieldErrors(e);
		} finally {
			busy.value = false;
		}
	}

	return { startDate, endDate, errors, busy, create };
}
