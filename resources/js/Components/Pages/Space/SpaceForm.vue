<script setup lang="ts">
import { onMounted, useTemplateRef } from "vue";
import { useSpaceForm } from "@/Composables/Space/useSpaceIndex";
import type { Space } from "@/Types/Pages/Space/Space";

const props = defineProps<{ space?: Space }>();
const emit = defineEmits<{ saved: []; cancel: [] }>();
const first = useTemplateRef<HTMLInputElement>("first");
onMounted(() => first.value?.focus());
const { name, frequency, customDays, initialBalance, confirmingDelete, errors, busy, submit, remove } = useSpaceForm(props, () => emit("saved"));
</script>

<template>
	<form class="form-panel" @submit.prevent="submit">
		<label class="field">Nombre
			<input ref="first" v-model="name" class="input" />
			<span v-if="errors.name" role="alert" class="err">{{ errors.name }}</span>
		</label>
		<label class="field">Frecuencia
			<select v-model="frequency" :disabled="space?.hasPeriods" aria-describedby="space-frequency-hint" class="input">
				<option value="weekly">Semanal</option>
				<option value="biweekly">Quincenal</option>
				<option value="monthly">Mensual</option>
				<option value="custom">Personalizada</option>
			</select>
			<span v-if="space?.hasPeriods" id="space-frequency-hint" class="field-hint">No se puede cambiar: el espacio ya tiene periodos.</span>
			<span v-if="errors.frequency" role="alert" class="err">{{ errors.frequency }}</span>
		</label>
		<label v-if="frequency === 'custom'" class="field">Días por periodo
			<input v-model="customDays" :disabled="space?.hasPeriods" type="number" min="1" max="366" inputmode="numeric" class="input" />
			<span v-if="errors.customDays" role="alert" class="err">{{ errors.customDays }}</span>
		</label>
		<label class="field">Saldo inicial
			<input v-model="initialBalance" inputmode="decimal" class="input" />
			<span v-if="errors.initialBalance" role="alert" class="err">{{ errors.initialBalance }}</span>
		</label>
		<p v-if="errors.form" role="alert" class="err form-wide">{{ errors.form }}</p>
		<div class="form-actions form-wide">
			<button :disabled="busy" class="btn btn-primary">Guardar</button>
			<button type="button" :disabled="busy" class="btn btn-ghost" @click="emit('cancel')">Cancelar</button>
		</div>
		<template v-if="space">
			<button v-if="!confirmingDelete" type="button" :disabled="busy" class="btn btn-danger form-wide" @click="confirmingDelete = true">Eliminar</button>
			<div v-else class="form-confirm form-wide">
				<span>¿Eliminar este espacio?</span>
				<button type="button" :disabled="busy" class="btn btn-danger-solid" @click="remove">Sí, eliminar</button>
				<button type="button" :disabled="busy" class="btn btn-ghost" @click="confirmingDelete = false">No</button>
			</div>
		</template>
	</form>
</template>
