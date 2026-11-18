<script setup lang="ts">
import axios from "axios";
import { computed, onMounted, ref } from "vue";
import { splitPreview } from "@/Composables/Period/lineKind";
import { formatCRC } from "@/Composables/shared/format";
import { errorMessage } from "@/Composables/shared/http";
import type { ClosePreview } from "@/Types/Pages/Period/Period";

const props = defineProps<{ periodId: number }>();
const emit = defineEmits<{ closed: []; cancel: [] }>();
const preview = ref<ClosePreview | null>(null);
const split = computed(() => splitPreview(preview.value?.lines ?? []));
const error = ref("");
const busy = ref(false);

onMounted(async () => {
	try {
		preview.value = (await axios.get<ClosePreview>(`/periods/${props.periodId}/close-preview`)).data;
	} catch (e) {
		error.value = errorMessage(e);
	}
});

async function confirm() {
	busy.value = true;
	try {
		await axios.post(`/periods/${props.periodId}/close`);
		emit("closed");
	} catch (e) {
		error.value = errorMessage(e);
	} finally {
		busy.value = false;
	}
}
</script>

<template>
	<div class="close-dialog" role="dialog" aria-modal="true" aria-label="Cerrar periodo">
		<div class="close-dialog__panel">
			<h2 class="close-dialog__title">Cerrar periodo</h2>
			<p class="close-dialog__hint">Esto pasa al próximo periodo:</p>
			<ul v-if="preview" class="close-dialog__list">
				<li v-if="!preview.lines.length" class="close-dialog__row close-dialog__row--empty">Nada pendiente.</li>
				<template v-if="split.arrastre.length">
					<li class="close-dialog__row close-dialog__row--group"><span>Va al arrastre</span><span class="num">{{ formatCRC(split.arrastreTotal) }}</span></li>
					<li v-for="l in split.arrastre" :key="l.id" class="close-dialog__row"><span>{{ l.name }}</span><span class="num">{{ formatCRC(l.actual ?? l.estimated) }}</span></li>
				</template>
				<template v-if="split.transfers.length">
					<li class="close-dialog__row close-dialog__row--group"><span>Pasan como transferencia</span><span class="num">{{ formatCRC(split.transfersTotal) }}</span></li>
					<li v-for="l in split.transfers" :key="l.id" class="close-dialog__row"><span>{{ l.name }}</span><span class="num">{{ formatCRC(l.actual ?? l.estimated) }}</span></li>
				</template>
				<li class="close-dialog__row close-dialog__row--total"><span>Total</span><span class="num">{{ formatCRC(preview.total) }}</span></li>
			</ul>
			<p v-if="error" role="alert" class="alert">{{ error }}</p>
			<div class="close-dialog__actions">
				<button type="button" :disabled="busy || !preview" class="btn btn-primary" @click="confirm">Confirmar</button>
				<button type="button" :disabled="busy" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
			</div>
		</div>
	</div>
</template>
