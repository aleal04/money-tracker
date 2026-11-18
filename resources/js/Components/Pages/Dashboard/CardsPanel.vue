<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import Icon from "@/Components/UI/Icon.vue";
import { finishing, finishingLabel } from "@/Composables/Dashboard/useDashboard";
import { formatCRC } from "@/Composables/shared/format";
import type { CardView } from "@/Types/Pages/Installment/Installment";

defineProps<{ cards: CardView[]; half: 1 | 2 }>();
</script>

<template>
	<section class="card cards-panel">
		<h2 class="cards-panel__title">Tarjetas</h2>
		<p v-if="!cards.length" class="cards-panel__empty">Sin tarjetas todavía.</p>
		<div v-for="c in cards" :key="c.id" class="cards-panel__item">
			<p class="cards-panel__name">{{ c.name }}</p>
			<div class="cards-panel__cols">
				<div><div class="th">Cuota por mes</div><div class="cards-panel__value">{{ formatCRC(c.totals.cuotaMensual) }}</div></div>
				<div><div class="th">Por quincena</div><div class="cards-panel__value">{{ formatCRC(c.totals.porQuincena[half - 1]) }}</div></div>
			</div>
			<p v-if="finishing(c).length" class="cards-panel__finishing">
				<span class="pill cards-panel__pill">Por terminar</span>
				{{ finishingLabel(c) }}
			</p>
		</div>
		<Link href="/installments" class="btn btn-ghost cards-panel__link">Ver cuotas<Icon name="right" /></Link>
	</section>
</template>
