<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Icon from "@/Components/UI/Icon.vue";
import { formatCRC } from "@/Composables/shared/format";
import type { Line } from "@/Types/Pages/Period/Period";

defineProps<{ periodId: number; pending: Line[]; busy: boolean }>();
const emit = defineEmits<{ pay: [Line] }>();
</script>

<template>
	<section class="card pending-panel">
		<div class="pending-panel__head">
			<h2 class="pending-panel__title">Pendientes de pagar</h2>
			<span class="th">{{ pending.length }}</span>
		</div>
		<p v-if="!pending.length" class="pending-panel__empty">Todo pagado en esta quincena.</p>
		<ul v-else class="pending-panel__list">
			<li v-for="l in pending" :key="l.id" class="pending-panel__item">
				<button type="button" :disabled="busy" class="pending-panel__check" :aria-label="`Marcar como pagado: ${l.name}`" @click="emit('pay', l)">
					<span class="pending-panel__box" />
				</button>
				<span class="pending-panel__name">{{ l.name }}</span>
				<span class="num pending-panel__amount">{{ formatCRC(l.actual ?? l.estimated) }}</span>
			</li>
		</ul>
		<Link :href="`/periods/${periodId}`" class="btn btn-ghost pending-panel__link">Ver quincena<Icon name="right" /></Link>
	</section>
</template>
