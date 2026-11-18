<script setup lang="ts">
import { summaryCards } from "@/Composables/Period/summaryCards";
import { formatCRC } from "@/Composables/shared/format";
import type { Summary } from "@/Types/Pages/Period/Period";

defineProps<{ summary: Summary }>();
</script>

<template>
	<section class="summary-cards">
		<div v-for="c in summaryCards(summary)" :key="c.key" class="card summary-cards__item">
			<div class="th">{{ c.label }}</div>
			<div class="summary-cards__value" :class="c.tone && `summary-cards__value--${c.tone}`">{{ formatCRC(c.value) }}</div>
		</div>
	</section>
</template>
