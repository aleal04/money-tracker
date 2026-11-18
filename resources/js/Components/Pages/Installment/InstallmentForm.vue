<script setup lang="ts">
import SelectInput from "@/Components/UI/Inputs/SelectInput.vue";
import { useInstallmentForm } from "@/Composables/Installment/useInstallmentForm";
import type { Installment } from "@/Types/Pages/Installment/Installment";

const props = defineProps<{ cardId: number; installment?: Installment }>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const typeOptions = [
	{ label: "Tasa 0", value: "tasa0" },
	{ label: "Minicuotas", value: "minicuotas" },
];
const { merchant, item, type, total, paid, initial, cuotaAmount, override, confirmingDelete, errors, busy, submit, remove } = useInstallmentForm(props, () => emit("saved"));
</script>

<template>
	<form class="form-panel" @submit.prevent="submit">
		<label class="field">Comercio
			<input v-model="merchant" class="input" />
			<span v-if="errors.merchant" role="alert" class="err">{{ errors.merchant }}</span>
		</label>
		<label class="field">Artículo
			<input v-model="item" class="input" />
			<span v-if="errors.item" role="alert" class="err">{{ errors.item }}</span>
		</label>
		<label class="field">Forma de pago
			<SelectInput v-model="type" :options="typeOptions" />
			<span v-if="errors.type" role="alert" class="err">{{ errors.type }}</span>
		</label>
		<label class="field">Cuotas totales
			<input v-model="total" inputmode="numeric" class="input" />
			<span v-if="errors.totalInstallments" role="alert" class="err">{{ errors.totalInstallments }}</span>
		</label>
		<label class="field">Cuotas pagadas (hasta el mes pasado)
			<input v-model="paid" inputmode="numeric" class="input" />
			<span v-if="errors.paidInstallments" role="alert" class="err">{{ errors.paidInstallments }}</span>
		</label>
		<label class="field">Saldo inicial
			<input v-model="initial" inputmode="decimal" class="input" />
			<span v-if="errors.initialBalance" role="alert" class="err">{{ errors.initialBalance }}</span>
		</label>
		<label v-if="type === 'minicuotas'" class="field">Monto cuota
			<input v-model="cuotaAmount" inputmode="decimal" class="input" />
			<span v-if="errors.installmentAmount" role="alert" class="err">{{ errors.installmentAmount }}</span>
		</label>
		<label class="field">Saldo real del estado de cuenta (opcional)
			<input v-model="override" inputmode="decimal" class="input" />
			<span v-if="errors.balanceOverride" role="alert" class="err">{{ errors.balanceOverride }}</span>
		</label>
		<p v-if="errors.form || errors.cardId" role="alert" class="err form-wide">{{ errors.form ?? errors.cardId }}</p>
		<div class="form-actions form-wide">
			<button :disabled="busy" class="btn btn-primary">Guardar</button>
			<button type="button" :disabled="busy" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
		</div>
		<template v-if="installment">
			<button v-if="!confirmingDelete" type="button" :disabled="busy" class="btn btn-danger form-wide" @click="confirmingDelete = true">Eliminar</button>
			<div v-else class="form-confirm form-wide">
				<span>¿Eliminar esta cuota?</span>
				<button type="button" :disabled="busy" class="btn btn-danger-solid" @click="remove">Sí, eliminar</button>
				<button type="button" :disabled="busy" class="btn btn-ghost" @click="confirmingDelete = false">No</button>
			</div>
		</template>
	</form>
</template>
