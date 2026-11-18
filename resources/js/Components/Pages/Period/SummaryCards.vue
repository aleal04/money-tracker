<script setup lang="ts">
import { formatCRC } from "@/Composables/shared/format";
import type { Summary } from "@/Types/Pages/Period/Period";

defineProps<{ summary: Summary }>();
</script>

<template>
	<section class="summary-cards">
		<div class="card summary-cards__item">
			<div class="th">Ingreso</div>
			<div class="summary-cards__value">{{ formatCRC(summary.ingreso) }}</div>
		</div>
		<div class="card summary-cards__item">
			<div class="th">Egreso</div>
			<div class="summary-cards__value">{{ formatCRC(summary.egreso) }}</div>
		</div>
		<div class="card summary-cards__item">
			<div class="th">Disponible</div>
			<div class="summary-cards__value" :class="summary.disponible < 0 ? 'summary-cards__value--negative' : 'summary-cards__value--positive'">
				{{ formatCRC(summary.disponible) }}
			</div>
		</div>
		<div class="card summary-cards__item">
			<div class="th">Falta por pagar</div>
			<div class="summary-cards__value">{{ formatCRC(summary.faltaPorPagar) }}</div>
		</div>
	</section>
</template>
