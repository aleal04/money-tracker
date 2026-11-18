<script setup lang="ts">
import Icon from "@/Components/UI/Icon.vue";
import { GROUPS } from "@/Constants/Pages/Installment/groups";
import { ofStatus, porPeriodoLabel, signed } from "@/Composables/Installment/useInstallmentIndex";
import { formatCRC } from "@/Composables/shared/format";
import type { CardView, Installment } from "@/Types/Pages/Installment/Installment";
import InstallmentForm from "./InstallmentForm.vue";

defineProps<{ card: CardView; editing: string | null }>();
const emit = defineEmits<{ edit: [string | null]; saved: []; cancelSoon: [Installment] }>();
</script>

<template>
	<section class="card card-section">
		<h2 class="card-section__title">{{ card.name }}</h2>
		<template v-for="g in GROUPS" :key="g.status">
			<component :is="g.status === 'terminada' ? 'details' : 'div'" v-if="ofStatus(card, g.status).length" class="card-section__group" :class="`card-section__group--${g.status}`">
				<component :is="g.status === 'terminada' ? 'summary' : 'h3'" class="card-section__group-head">
					<span class="pill card-section__pill">{{ g.label }}</span><span class="card-section__count">({{ ofStatus(card, g.status).length }})</span>
				</component>
				<ul class="card-section__list">
					<li v-for="i in ofStatus(card, g.status)" :key="i.id" class="card-section__item" :class="{ 'card-section__item--soon': i.cancelSoon }">
						<div class="card-section__top">
							<div class="card-section__info">
								<span class="card-section__name">{{ i.item }}</span>
								<span v-if="i.cancelSoon" class="pill card-section__tag">Cancelar pronto</span>
								<span class="card-section__meta">{{ i.merchant }} · {{ i.type === "tasa0" ? "Tasa 0" : "Minicuotas" }}</span>
							</div>
							<button type="button" class="icon-btn" :aria-label="`Editar ${i.item}`" @click="emit('edit', String(i.id))"><Icon name="edit" /></button>
						</div>
						<div class="card-section__body">
							<dl class="card-section__cols">
								<div><dt class="th">Cuotas</dt><dd>{{ i.paidInstallments }}/{{ i.totalInstallments }}</dd></div>
								<div><dt class="th">Saldo inicial</dt><dd>{{ formatCRC(i.initialBalance) }}</dd></div>
								<div><dt class="th">Cuota</dt><dd class="card-section__strong">{{ formatCRC(i.cuota) }}</dd></div>
								<div>
									<dt class="th">Saldo faltante</dt>
									<dd>
										{{ formatCRC(i.saldo.value) }}
										<span v-if="i.saldo.diff !== null" class="card-section__meta">Δ vs calculado: {{ signed(i.saldo.diff, formatCRC) }}</span>
									</dd>
								</div>
							</dl>
							<button type="button" class="btn btn-ghost card-section__soon-btn" @click="emit('cancelSoon', i)">{{ i.cancelSoon ? 'Quitar "Cancelar pronto"' : "Cancelar pronto" }}</button>
						</div>
						<InstallmentForm v-if="editing === String(i.id)" :card-id="card.id" :installment="i" @saved="emit('saved')" @cancel="emit('edit', null)" />
					</li>
				</ul>
			</component>
		</template>
		<p v-if="!card.installments.length" class="card-section__meta">Sin cuotas.</p>
		<InstallmentForm v-if="editing === `new-${card.id}`" :card-id="card.id" @saved="emit('saved')" @cancel="emit('edit', null)" />
		<footer class="card-section__foot">
			<div><div class="th">Cuota mensual</div><span class="card-section__strong">{{ formatCRC(card.totals.cuotaMensual) }}</span></div>
			<div><div class="th">Saldo faltante</div><span>{{ formatCRC(card.totals.saldoFaltante) }}</span></div>
			<div><div class="th">Por periodo</div><span>{{ porPeriodoLabel(card.totals, formatCRC) }}</span></div>
			<button v-if="editing !== `new-${card.id}`" type="button" class="btn card-section__add" @click="emit('edit', `new-${card.id}`)"><Icon name="plus" />Cuota</button>
		</footer>
	</section>
</template>
